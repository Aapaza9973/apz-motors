<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'clientes' => Cliente::withSum('puntos as puntos_total', 'puntos')->orderBy('nombre')->get(),
            'productos' => Producto::with('categoria')->where('stock', '>', 0)->orderBy('nombre')->get(),
            'imprimirComprobante' => auth()->user()->pref_imprimir_pos,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'puntos_canje' => ['nullable', 'integer', 'min:0'],
            'pago_monto' => ['nullable', 'numeric', 'min:0'],
            'pago_metodo' => ['nullable', 'in:Efectivo,Tarjeta,Transferencia,Stripe,PayPal,Otro'],
        ]);

        try {
            $venta = $this->ventaService->crearVenta(
                [
                    'cliente_id' => $datos['cliente_id'] ?? null,
                    'estado' => 'Pendiente',
                    'puntos_canje' => (int) ($datos['puntos_canje'] ?? 0),
                ],
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
            ->route('ventas.show', [
                'venta' => $venta,
                'imprimir' => $request->boolean('imprimir_comprobante') ? 1 : null,
            ])
            ->with('status', "Venta #{$venta->id} registrada correctamente.");
    }

    public function show(Request $request, Venta $venta): View
    {
        return view('ventas.show', [
            'venta' => $venta->load('detalles.producto', 'cliente', 'usuario', 'pagos', 'pedido', 'puntos'),
            'papelComprobante' => $request->user()->pref_papel_comprobante,
        ]);
    }

    /**
     * Registra en la auditoría (Telescope) la impresión de un comprobante.
     * El RequestWatcher de Telescope captura la petición con el tag del
     * usuario; el origen (POS o manual) viaja en la URL (?origen=pos|manual)
     * para filtrarlo desde la pestaña Requests.
     */
    public function imprimir(Request $request, Venta $venta): JsonResponse
    {
        $origen = in_array($request->query('origen', 'manual'), ['pos', 'manual'], true)
            ? $request->query('origen')
            : 'manual';

        Log::info('Comprobante impreso', [
            'venta_id' => $venta->id,
            'usuario_id' => $request->user()->id,
            'usuario' => $request->user()->name,
            'origen' => $origen,
            'papel' => $request->user()->pref_papel_comprobante,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Guarda las preferencias de impresión del usuario autenticado
     * (tamaño de papel del comprobante y autoimpresión desde el POS).
     */
    public function guardarPreferencias(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'papel_comprobante' => ['sometimes', 'in:termico,carta'],
            'imprimir_pos' => ['sometimes', 'boolean'],
        ]);

        $usuario = $request->user();

        if (array_key_exists('papel_comprobante', $datos)) {
            $usuario->pref_papel_comprobante = $datos['papel_comprobante'];
        }

        if (array_key_exists('imprimir_pos', $datos)) {
            $usuario->pref_imprimir_pos = (bool) $datos['imprimir_pos'];
        }

        $usuario->save();

        return response()->json(['ok' => true]);
    }
}
