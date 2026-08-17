<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\PedidoService;
use App\Support\Carrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Pedidos en línea: el cliente hace el pedido desde el catálogo público y
 * el pedido cae en la bandeja interna, donde el vendedor lo confirma y
 * lo convierte en venta (descuento de stock incluido).
 */
class PedidoController extends Controller
{
    public function __construct(private PedidoService $pedidos) {}

    // ---------- Flujo público ----------

    public function checkout(): View|RedirectResponse
    {
        if (Carrito::estaVacio()) {
            return redirect()->route('catalogo.index')->with('status-error', 'Tu carrito está vacío. Agregá repuestos antes de continuar.');
        }

        return view('catalogo.checkout', [
            'items' => Carrito::detalle(),
            'total' => Carrito::total(),
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
        ]);

        try {
            $pedido = $this->pedidos->crearPedido($datos, Carrito::items());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        Carrito::vaciar();

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
            'pedido_id' => ['required', 'integer', 'exists:pedidos,id'],
            'telefono' => ['required', 'string', 'max:30'],
        ]);

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

    // ---------- Bandeja interna ----------

    public function index(Request $request): View
    {
        $query = Pedido::with('items')->latest();

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        return view('pedidos.index', [
            'pedidos' => $query->paginate(15)->withQueryString(),
            'estadoActivo' => $request->query('estado'),
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

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('status', "Pedido #{$pedido->id} confirmado — venta #{$venta->id} generada y stock descontado.");
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
