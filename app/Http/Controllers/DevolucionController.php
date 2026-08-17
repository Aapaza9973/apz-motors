<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\DevolucionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DevolucionController extends Controller
{
    public function __construct(private DevolucionService $devolucionService) {}

    public function index(Request $request): View
    {
        $query = Devolucion::with('venta.cliente', 'producto', 'usuario')->latest();

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        return view('devoluciones.index', [
            'devoluciones' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, Venta $venta): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $devolucion = $this->devolucionService->crear($venta, $datos, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('devoluciones.index')
            ->with('status', "Devolución #{$devolucion->id} registrada y pendiente de aprobación.");
    }

    public function aprobar(Request $request, Devolucion $devolucion): RedirectResponse
    {
        $datos = $request->validate([
            'monto_reembolso' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->devolucionService->aprobar(
                $devolucion,
                isset($datos['monto_reembolso']) ? (float) $datos['monto_reembolso'] : null
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('devoluciones.index')
            ->with('status', "Devolución #{$devolucion->id} aprobada — stock repuesto y reembolso de Bs {$devolucion->fresh()->monto_reembolso}.");
    }

    public function rechazar(Request $request, Devolucion $devolucion): RedirectResponse
    {
        $datos = $request->validate([
            'motivo_rechazo' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->devolucionService->rechazar($devolucion, $datos['motivo_rechazo'] ?? null);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('devoluciones.index')
            ->with('status', "Devolución #{$devolucion->id} rechazada.");
    }
}
