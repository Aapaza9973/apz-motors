<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function __construct(private VentaService $ventaService) {}

    public function index(Request $request): View
    {
        $query = Venta::with('cliente', 'usuario');

        if ($desde = $request->query('desde')) {
            $query->whereDate('fecha', '>=', $desde);
        }

        if ($hasta = $request->query('hasta')) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        return view('ventas.index', [
            'ventas' => $query->latest('fecha')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('ventas.create', [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::with('categoria')->where('stock', '>', 0)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'pago_monto' => ['nullable', 'numeric', 'min:0'],
            'pago_metodo' => ['nullable', 'in:Efectivo,Tarjeta,Transferencia,Stripe,PayPal,Otro'],
        ]);

        try {
            $venta = $this->ventaService->crearVenta(
                ['cliente_id' => $datos['cliente_id'] ?? null, 'estado' => 'Pendiente'],
                $datos['items'],
                $request->user(),
                $datos['pago_monto'] ?? null
                    ? ['monto' => $datos['pago_monto'], 'metodo' => $datos['pago_metodo'] ?? 'Efectivo']
                    : null
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('ventas.show', $venta)
            ->with('status', "Venta #{$venta->id} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        return view('ventas.show', [
            'venta' => $venta->load('detalles.producto', 'cliente', 'usuario', 'pagos'),
        ]);
    }
}
