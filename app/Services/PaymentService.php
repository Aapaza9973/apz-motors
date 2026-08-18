<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

class PaymentService
{
    public function __construct(private PuntosService $puntos) {}

    /** Moneda configurable (por defecto Bolivianos; operadores pueden usar USD). */
    public function moneda(): string
    {
        return config('app.currency', 'BOB');
    }

    /**
     * ¿El método de pago está operativo? Sin claves API configuradas se usa
     * un modo de simulación (útil en desarrollo y para pruebas).
     */
    public function estaDisponible(string $metodo): bool
    {
        return $metodo === 'Stripe' || $metodo === 'PayPal';
    }

    public function usaSimulacion(string $metodo): bool
    {
        return match ($metodo) {
            'Stripe' => blank(config('services.stripe.secret')),
            'PayPal' => blank(config('services.paypal.client_id')) || blank(config('services.paypal.secret')),
            default => true,
        };
    }

    /**
     * Inicia el cobro en línea y devuelve la URL a la que redirigir al cliente.
     */
    public function crearCheckout(Venta $venta, string $metodo): string
    {
        if ($this->usaSimulacion($metodo)) {
            return $this->rutaSimulacion($venta, $metodo);
        }

        return match ($metodo) {
            'Stripe' => $this->crearCheckoutStripe($venta),
            'PayPal' => $this->crearCheckoutPayPal($venta),
            default => throw new \InvalidArgumentException("Método de pago no soportado: {$metodo}"),
        };
    }

    /**
     * Confirma un pago: registra el Pago y marca la venta como Pagada.
     * Es idempotente: si la venta ya está pagada o el pago ya existe, no duplica.
     */
    public function confirmar(Venta $venta, string $metodo, ?string $referencia = null): Venta
    {
        if ($venta->estado === 'Cancelada') {
            throw new \DomainException('No se puede cobrar una venta cancelada.');
        }

        $existe = $venta->pagos()
            ->where('estado', 'Completado')
            ->where('metodo', $metodo)
            ->exists();

        if (! $existe) {
            $venta->pagos()->create([
                'monto' => $venta->total,
                'metodo' => $metodo,
                'estado' => 'Completado',
                'referencia' => $referencia,
            ]);
        }

        if ($venta->estado !== 'Pagado') {
            $venta->update(['estado' => 'Pagado']);

            // Fidelización: la venta recién pagada acredita puntos al cliente.
            $this->puntos->acumularPorVenta($venta);
        }

        return $venta->load('pagos');
    }

    // ----------------------------------------------------------------------
    // Pagos de pedidos en línea (catálogo público)
    // ----------------------------------------------------------------------

    /**
     * Inicia el cobro de un pedido del catálogo y devuelve la URL a la que
     * redirigir al cliente (pasarela real o simulación si no hay claves).
     */
    public function crearCheckoutPedido(Pedido $pedido, string $metodo): string
    {
        if ($this->usaSimulacion($metodo)) {
            return route('pedidos.pago.simular', ['pedido' => $pedido, 'metodo' => $metodo]);
        }

        return match ($metodo) {
            'Stripe' => $this->crearCheckoutStripePedido($pedido),
            'PayPal' => $this->crearCheckoutPayPalPedido($pedido),
            default => throw new \InvalidArgumentException("Método de pago no soportado: {$metodo}"),
        };
    }

    /**
     * Marca el pedido como pagado con su referencia. Idempotente: si ya
     * estaba pagado con el mismo método, no duplica la marca.
     */
    public function confirmarPedido(Pedido $pedido, string $metodo, ?string $referencia = null): Pedido
    {
        if ($pedido->estado === 'Cancelado') {
            throw new \DomainException('No se puede cobrar un pedido cancelado.');
        }

        if (! $pedido->estaPagado()) {
            $pedido->update([
                'estado_pago' => 'Pagado',
                'metodo_pago' => $metodo,
                'referencia_pago' => $referencia ?? $pedido->referencia_pago,
            ]);
        }

        return $pedido->fresh();
    }

    /** Captura una orden de PayPal aprobada para un pedido del catálogo. */
    public function capturarPayPalPedido(Pedido $pedido, string $ordenId): Pedido
    {
        $this->capturarPayPalOrden($ordenId);

        return $this->confirmarPedido($pedido, 'PayPal', $ordenId);
    }

    /**
     * Consulta una sesión de Stripe y, si está pagada, confirma el pedido.
     */
    public function confirmarStripePedido(Pedido $pedido, string $sessionId): Pedido
    {
        $sesion = (new StripeClient(config('services.stripe.secret')))
            ->checkout->sessions->retrieve($sessionId);

        if (! ($sesion instanceof StripeSession) || ($sesion->payment_status ?? null) !== 'paid') {
            throw new \RuntimeException('La sesión de Stripe no está pagada.');
        }

        return $this->confirmarPedido($pedido, 'Stripe', $sesion->payment_intent ?? $sesion->id);
    }

    private function crearCheckoutStripePedido(Pedido $pedido): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $sesion = (new StripeClient(config('services.stripe.secret')))->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($this->moneda()),
                    'unit_amount' => $this->aCentavos($pedido->total),
                    'product_data' => ['name' => "Pedido #{$pedido->id} — APZ Motor's"],
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['pedido_id' => $pedido->id],
            'success_url' => route('pedidos.pago.retorno', ['pedido' => $pedido, 'metodo' => 'Stripe']),
            'cancel_url' => route('pedidos.confirmacion', $pedido),
        ]);

        return $sesion->url;
    }

    private function crearCheckoutPayPalPedido(Pedido $pedido): string
    {
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $orden = Http::withToken($token)
            ->post("{$base}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => "pedido-{$pedido->id}",
                    'description' => "Pedido #{$pedido->id} — APZ Motor's",
                    'amount' => [
                        'currency_code' => $this->moneda(),
                        'value' => number_format((float) $pedido->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('pedidos.pago.retorno', ['pedido' => $pedido, 'metodo' => 'PayPal']),
                    'cancel_url' => route('pedidos.confirmacion', $pedido),
                ],
            ])
            ->throw()
            ->json();

        foreach ($orden['links'] ?? [] as $enlace) {
            if (($enlace['rel'] ?? '') === 'approve') {
                return $enlace['href'];
            }
        }

        throw new \RuntimeException('PayPal no devolvió un enlace de aprobación.');
    }

    private function capturarPayPalOrden(string $ordenId): void
    {
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $respuesta = Http::withToken($token)
            ->post("{$base}/v2/checkout/orders/{$ordenId}/capture")
            ->throw()
            ->json();

        if (($respuesta['status'] ?? null) !== 'COMPLETED') {
            throw new \RuntimeException('PayPal no completó la captura (estado: '.($respuesta['status'] ?? 'desconocido').').');
        }
    }

    /**
     * Verifica y procesa el webhook de Stripe (checkout.session.completed).
     */
    public function manejarWebhookStripe(string $payload, string $firma): void
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (blank($webhookSecret)) {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET no está configurado.');
        }

        try {
            $evento = Webhook::constructEvent($payload, $firma, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Firma de webhook de Stripe inválida.', ['error' => $e->getMessage()]);
            throw $e;
        }

        if ($evento->type === 'checkout.session.completed') {
            /** @var StripeSession $session */
            $session = $evento->data->object;
            $ventaId = $session->metadata['venta_id'] ?? null;

            if ($ventaId === null) {
                return;
            }

            $venta = Venta::findOrFail((int) $ventaId);
            $this->confirmar($venta, 'Stripe', $session->payment_intent ?? $session->id);
        }
    }

    private function crearCheckoutStripe(Venta $venta): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $sesion = (new StripeClient(config('services.stripe.secret')))->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($this->moneda()),
                    'unit_amount' => $this->aCentavos($venta->total),
                    'product_data' => ['name' => "Venta #{$venta->id} — APZ Motor's"],
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['venta_id' => $venta->id],
            'success_url' => $this->urlRetorno($venta),
            'cancel_url' => route('ventas.show', $venta),
        ]);

        return $sesion->url;
    }

    private function crearCheckoutPayPal(Venta $venta): string
    {
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $orden = Http::withToken($token)
            ->post("{$base}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string) $venta->id,
                    'description' => "Venta #{$venta->id} — APZ Motor's",
                    'amount' => [
                        'currency_code' => $this->moneda(),
                        'value' => number_format((float) $venta->total, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $this->urlRetorno($venta),
                    'cancel_url' => route('ventas.show', $venta),
                ],
            ])
            ->throw()
            ->json();

        foreach ($orden['links'] ?? [] as $enlace) {
            if (($enlace['rel'] ?? '') === 'approve') {
                return $enlace['href'];
            }
        }

        throw new \RuntimeException('PayPal no devolvió un enlace de aprobación.');
    }

    /** Captura una orden de PayPal ya aprobada (llamada desde la URL de retorno). */
    public function capturarPayPal(Venta $venta, string $ordenId): Venta
    {
        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $token = Http::asForm()
            ->withBasicAuth(config('services.paypal.client_id'), config('services.paypal.secret'))
            ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials'])
            ->throw()
            ->json('access_token');

        $respuesta = Http::withToken($token)
            ->post("{$base}/v2/checkout/orders/{$ordenId}/capture")
            ->throw()
            ->json();

        $estado = $respuesta['status'] ?? null;

        if ($estado !== 'COMPLETED') {
            throw new \RuntimeException("PayPal no completó la captura (estado: {$estado}).");
        }

        return $this->confirmar($venta, 'PayPal', $ordenId);
    }

    private function rutaSimulacion(Venta $venta, string $metodo): string
    {
        return route('pagos.simular', ['venta' => $venta, 'metodo' => $metodo]);
    }

    private function urlRetorno(Venta $venta): string
    {
        return route('pagos.retorno', $venta);
    }

    private function aCentavos(string|float $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }
}
