<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\PaymentService;
use App\Services\PedidoService;
use App\Support\Carrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pedidos en línea: el cliente hace el pedido desde el catálogo público
 * (con pago en línea o al recibir) y el pedido cae en la bandeja interna,
 * donde el vendedor lo confirma y lo convierte en venta.
 */
class PedidoController extends Controller
{
    public function __construct(
        private PedidoService $pedidos,
        private PaymentService $payments,
    ) {}

    // ---------- Flujo público ----------

    public function checkout(): View|RedirectResponse
    {
        if (Carrito::estaVacio()) {
            return redirect()->route('catalogo.index')->with('status-error', 'Tu carrito está vacío. Agregá repuestos antes de continuar.');
        }

        return view('catalogo.checkout', [
            'items' => Carrito::detalle(),
            'total' => Carrito::total(),
            'pagoSimulacion' => array_fill_keys(['Stripe', 'PayPal', 'Tarjeta', 'Yape'], true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_cliente' => ['required', 'string', 'max:120'],
            'telefono' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:500'],
            'metodo_pago' => ['required', 'in:Efectivo,Stripe,PayPal,Tarjeta,Yape'],
        ]);

        try {
            $pedido = $this->pedidos->crearPedido($datos, Carrito::items());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        Carrito::vaciar();

        if ($datos['metodo_pago'] !== 'Efectivo') {
            try {
                $url = $this->payments->crearCheckoutPedido($pedido, $datos['metodo_pago']);

                return redirect()->away($url);
            } catch (\Throwable $e) {
                report($e);

                return redirect()
                    ->route('pedidos.confirmacion', $pedido)
                    ->with('status-error', 'No se pudo iniciar el pago en línea. Tu pedido quedó registrado: pagá al recibir o intentá de nuevo.');
            }
        }

        return redirect()
            ->route('pedidos.confirmacion', $pedido)
            ->with('status', "Pedido #{$pedido->id} recibido. Te contactaremos para coordinar la entrega.");
    }

    public function confirmacion(Pedido $pedido): View
    {
        return view('catalogo.confirmacion', [
            'pedido' => $pedido->load('items'),
        ]);
    }

    /**
     * Modo simulación de pago (solo si la pasarela no tiene claves API):
     * marca el pedido como Pagado para poder probar el flujo completo.
     */
    public function pagoSimular(Pedido $pedido, string $metodo): RedirectResponse
    {            if (! in_array($metodo, ['Stripe', 'PayPal', 'Tarjeta', 'Yape'], true) || ! $this->payments->usaSimulacion($metodo)) {
            abort(404);
        }

        try {
            $this->payments->confirmarPedido($pedido, $metodo, 'SIM-'.strtoupper($pedido->id.'-'.now()->timestamp));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('pedidos.confirmacion', $pedido)
                ->with('status-error', 'No se pudo registrar el pago simulado.');
        }

        return redirect()->route('pedidos.confirmacion', $pedido)
            ->with('status', "Pago simulado ({$metodo}) confirmado — tu pedido quedó Pagado.");
    }

    /**
     * Retorno de la pasarela tras pagar un pedido del catálogo:
     * PayPal captura la orden aprobada y Stripe confirma la sesión pagada.
     */
    public function pagoRetorno(Request $request, Pedido $pedido, string $metodo): RedirectResponse
    {
        try {
            if ($metodo === 'PayPal' && $request->query('token')) {
                $this->payments->capturarPayPalPedido($pedido, (string) $request->query('token'));
            } elseif ($metodo === 'Stripe' && $request->query('session_id')) {
                $this->payments->confirmarStripePedido($pedido, (string) $request->query('session_id'));
            } else {
                throw new \RuntimeException('Falta el parámetro de confirmación de la pasarela.');
            }
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('pedidos.confirmacion', $pedido)
                ->with('status-error', 'No se pudo confirmar el pago. Tu pedido quedó registrado: pagá al recibir o revisá la pasarela.');
        }

        return redirect()->route('pedidos.confirmacion', $pedido)
            ->with('status', "Pago confirmado ({$metodo}) — tu pedido quedó Pagado.");
    }

    /**
     * Consulta pública de estado: el cliente ingresa número de pedido y
     * teléfono, y puede seguir el estado de su pedido sin estar autenticado.
     */
    public function consultarForm(): View
    {
        return view('catalogo.consultar');
    }

    public function consultar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'pedido_id' => ['required', 'integer'],
            'telefono' => ['required', 'string', 'max:30'],
        ]);

        // Sin la regla `exists` a propósito: un id inexistente y un teléfono
        // incorrecto devuelven el mismo mensaje genérico, para que un tercero
        // no pueda enumerar qué números de pedido existen.
        $pedido = Pedido::where('id', $datos['pedido_id'])
            ->where('telefono', $datos['telefono'])
            ->first();

        if ($pedido === null) {
            return back()
                ->withErrors(['pedido' => 'No encontramos un pedido con ese número y teléfono. Revisá los datos e intentá de nuevo.'])
                ->withInput();
        }

        return redirect()->route('pedidos.estado', [
            'pedido' => $pedido,
            'telefono' => $pedido->telefono,
        ]);
    }

    /**
     * Página pública de estado. Se valida el teléfono para que el número
     * de pedido solo sea visible para quien conozca el contacto del cliente.
     */
    public function estado(Request $request, Pedido $pedido): View
    {
        if ($request->query('telefono') !== $pedido->telefono) {
            abort(404);
        }

        return view('catalogo.estado', [
            'pedido' => $pedido->load('items', 'venta'),
        ]);
    }

    /**
     * El cliente confirma su pedido desde el correo (enlace con token).
     */
    public function confirmarCliente(Pedido $pedido, string $token): RedirectResponse
    {
        try {
            $this->pedidos->confirmarPorCliente($pedido, $token);
        } catch (\DomainException $e) {
            abort(404);
        } catch (ValidationException $e) {
            return redirect()->route('pedidos.estado', ['pedido' => $pedido, 'telefono' => $pedido->telefono])
                ->with('status-error', $e->errors()['pedido'][0] ?? 'El pedido ya fue procesado.');
        }

        return redirect()->route('pedidos.estado', ['pedido' => $pedido, 'telefono' => $pedido->telefono])
            ->with('status', 'Gracias por confirmar tu pedido. El taller lo va a procesar en el orden de llegada.');
    }

    /**
     * El cliente cancela su pedido desde el correo (enlace con token).
     */
    public function cancelarCliente(Pedido $pedido, string $token): RedirectResponse
    {
        try {
            $this->pedidos->cancelarPorCliente($pedido, $token);
        } catch (\DomainException $e) {
            abort(404);
        } catch (ValidationException $e) {
            return redirect()->route('pedidos.estado', ['pedido' => $pedido, 'telefono' => $pedido->telefono])
                ->with('status-error', $e->errors()['pedido'][0] ?? 'El pedido ya fue procesado.');
        }

        return redirect()->route('pedidos.estado', ['pedido' => $pedido, 'telefono' => $pedido->telefono])
            ->with('status', 'Tu pedido fue cancelado. No se descontó stock y no hay cargos pendientes.');
    }

    // ---------- Bandeja interna ----------

    public function index(Request $request): View
    {
        $query = Pedido::with('items')->latest();

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($estadoPago = $request->query('estado_pago')) {
            $query->where('estado_pago', $estadoPago);
        }

        return view('pedidos.index', [
            'pedidos' => $query->paginate(15)->withQueryString(),
            'estadoActivo' => $request->query('estado'),
            'estadoPagoActivo' => $request->query('estado_pago'),
            'pendientes' => Pedido::where('estado', 'Pendiente')->count(),
        ]);
    }

    public function show(Pedido $pedido): View
    {
        return view('pedidos.show', [
            'pedido' => $pedido->load('items.producto', 'usuario', 'venta'),
        ]);
    }

    public function confirmar(Request $request, Pedido $pedido): RedirectResponse
    {
        try {
            $venta = $this->pedidos->confirmar($pedido, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $mensaje = $pedido->estaPagado()
            ? "Pedido #{$pedido->id} confirmado — venta #{$venta->id} generada, pagada y stock descontado."
            : "Pedido #{$pedido->id} confirmado — venta #{$venta->id} generada y stock descontado.";

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('status', $mensaje);
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        try {
            $this->pedidos->cancelar($pedido, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('pedidos.index')
            ->with('status', "Pedido #{$pedido->id} cancelado.");
    }
}
