<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\CierreCaja;
use App\Services\CajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function __construct(private CajaService $caja) {}

    /**
     * Historial de cierres de caja (el admin ve todos, el vendedor los suyos).
     */
    public function index(Request $request): View
    {
        $query = CierreCaja::query()->with('usuario')->latest('fecha_cierre');

        if (! Gate::allows('ver todos los cierres')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($fecha = $request->query('fecha')) {
            $query->whereDate('fecha_cierre', $fecha);
        }

        return view('caja.index', [
            'cierres' => $query->paginate(15)->withQueryString(),
        ]);
    }

    /**
     * Vista previa del cierre de hoy: resumen del turno antes de confirmar.
     */
    public function create(Request $request): View
    {
        return view('caja.create', [
            'resumen' => $this->caja->resumenDelDia($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        $cierre = $this->caja->cerrar($request->user(), $datos['observacion'] ?? null);

        return redirect()
            ->route('caja.index')
            ->with('status', "Cierre de caja registrado: {$cierre->cantidad_ventas} venta(s) por Bs ".number_format($cierre->total_ventas, 2).'.');
    }
}
