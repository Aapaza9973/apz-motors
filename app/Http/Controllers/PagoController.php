<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;

class PagoController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Inicia el cobro en línea de una venta (Stripe o PayPal) y redirige
     * a la pasarela, o al flujo de simulación si no hay claves configuradas.
     */
    public function iniciar(Request $request, Venta $venta): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', 'in:Stripe,PayPal'],
        ]);

        if ($venta->estado === 'Pagado') {
            return back()->with('status', 'La venta ya está pagada.');
        }

        if ($venta->estado === 'Cancelada') {
            return back()->withErrors(['error' => 'No se puede cobrar una venta cancelada.']);
        }

        try {
            $url = $this->paymentService->crearCheckout($venta, $datos['metodo']);

            return redirect()->away($url);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'error' => 'No se pudo iniciar el pago en línea. Verifica la configuración de la pasarela o inténtalo de nuevo.',
            ]);
        }
    }

    /**
     * URL de retorno tras completar el pago en la pasarela.
     * - Stripe: confirma con la sesión de checkout (el webhook es respaldo).
     * - PayPal: captura la orden aprobada.
     */
    public function retorno(Request $request, Venta $venta): RedirectResponse
    {
        try {
            if ($request->query('token')) {
                // PayPal: token de la orden aprobada.
                $this->paymentService->capturarPayPal($venta, (string) $request->query('token'));
            } elseif ($request->query('session_id')) {
                // Stripe: consultar la sesión para confirmar el pago.
                $sesion = (new StripeClient(config('services.stripe.secret')))
                    ->checkout->sessions->retrieve((string) $request->query('session_id'));

                if ($sesion instanceof StripeSession && ($sesion->payment_status ?? null) === 'paid') {
                    $this->paymentService->confirmar($venta, 'Stripe', $sesion->payment_intent ?? $sesion->id);
                }
            }

            if ($venta->fresh()->estado === 'Pagado') {
                return redirect()->route('ventas.show', $venta)
                    ->with('status', '¡Pago confirmado! La venta fue marcada como Pagada.');
            }

            return redirect()->route('ventas.show', $venta)
                ->with('status', 'El pago está pendiente de confirmación por la pasarela.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('ventas.show', $venta)
                ->withErrors(['error' => 'No se pudo confirmar el pago. Revisa el estado en la pasarela.']);
        }
    }

    /**
     * Modo simulación: se usa cuando no hay claves API configuradas.
     * Registra el pago como completado para poder probar el flujo completo.
     */
    public function simular(Venta $venta, string $metodo): RedirectResponse
    {
        try {
            $this->paymentService->confirmar($venta, $metodo, 'SIM-'.strtoupper($venta->id.'-'.now()->timestamp));

            return redirect()->route('ventas.show', $venta)
                ->with('status', "Pago simulado ({$metodo}) registrado — la venta quedó Pagada.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('ventas.show', $venta)
                ->withErrors(['error' => 'No se pudo registrar el pago simulado.']);
        }
    }

    /**
     * Webhook de Stripe (checkout.session.completed) como respaldo
     * de confirmación asíncrona.
     */
    public function webhookStripe(Request $request)
    {
        try {
            $this->paymentService->manejarWebhookStripe(
                $request->getContent(),
                (string) $request->header('Stripe-Signature')
            );

            return response()->json(['status' => 'ok']);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json(['error' => 'Firma inválida'], 400);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Error interno'], 500);
        }
    }
}
