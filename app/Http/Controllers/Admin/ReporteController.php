<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CsvExporter;
use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Resuelve el rango de fechas según el filtro del reporte.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangoFechas(Request $request): array
    {
        $rango = $request->query('rango', 'hoy');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        return match ($rango) {
            'semana' => [now()->startOfWeek(), now()->endOfWeek()],
            'mes' => [now()->startOfMonth(), now()->endOfMonth()],
            'personalizado' => [
                $desde ? Carbon::parse($desde)->startOfDay() : now()->subDays(30)->startOfDay(),
                $hasta ? Carbon::parse($hasta)->endOfDay() : now()->endOfDay(),
            ],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function ventasEnRango(Carbon $inicio, Carbon $fin): Collection
    {
        return Venta::with('cliente', 'usuario', 'detalles.producto')
            ->whereBetween('fecha', [$inicio, $fin])
            ->where('estado', '!=', 'Cancelada')
            ->latest('fecha')
            ->get();
    }

    public function ventas(Request $request): View
    {
        [$inicio, $fin] = $this->rangoFechas($request);
        $ventas = $this->ventasEnRango($inicio, $fin);

        $total = $ventas->sum('total');
        $cantidad = $ventas->count();

        return view('reportes.ventas', [
            'rango' => $request->query('rango', 'hoy'),
            'fechaInicio' => $inicio,
            'fechaFin' => $fin,
            'ventas' => $ventas,
            'total' => $total,
            'cantidad' => $cantidad,
            'promedio' => $cantidad > 0 ? round($total / $cantidad, 2) : 0,
            'masVendidos' => $this->masVendidos($ventas),
        ]);
    }

    public function exportarPdfVentas(Request $request): Response
    {
        [$inicio, $fin] = $this->rangoFechas($request);
        $ventas = $this->ventasEnRango($inicio, $fin);
        $total = $ventas->sum('total');

        $pdf = Pdf::loadView('reportes.pdf-ventas', [
            'fechaInicio' => $inicio,
            'fechaFin' => $fin,
            'ventas' => $ventas,
            'total' => $total,
            'cantidad' => $ventas->count(),
            'masVendidos' => $this->masVendidos($ventas),
        ])->setPaper('a4');

        return $pdf->download('reporte-ventas-'.$inicio->format('Y-m-d').'-al-'.$fin->format('Y-m-d').'.pdf');
    }

    public function exportarCsvVentas(Request $request): StreamedResponse
    {
        [$inicio, $fin] = $this->rangoFechas($request);
        $ventas = $this->ventasEnRango($inicio, $fin);

        return CsvExporter::stream(
            'reporte-ventas-'.$inicio->format('Y-m-d').'-al-'.$fin->format('Y-m-d').'.csv',
            ['N.º', 'Fecha', 'Cliente', 'Vendedor', 'Total (Bs)', 'Estado'],
            $ventas->map(fn (Venta $v) => [
                $v->id,
                $v->fecha->format('d/m/Y H:i'),
                $v->cliente?->nombre ?? 'Consumidor final',
                $v->usuario->name,
                number_format((float) $v->total, 2),
                $v->estado,
            ])
        );
    }

    public function inventario(Request $request): View
    {
        $soloBajo = $request->boolean('bajo');
        $productos = $this->productosInventario($soloBajo);

        return view('reportes.inventario', [
            'productos' => $productos,
            'soloBajo' => $soloBajo,
            'valorInventario' => $productos->sum(fn ($p) => $p->stock * $p->precio_unitario),
            'bajoStock' => $productos->filter(fn ($p) => $p->tieneStockBajo()),
            'totalProductos' => Producto::count(),
        ]);
    }

    public function exportarPdfInventario(Request $request): Response
    {
        $soloBajo = $request->boolean('bajo');
        $productos = $this->productosInventario($soloBajo);

        $pdf = Pdf::loadView('reportes.pdf-inventario', [
            'productos' => $productos,
            'soloBajo' => $soloBajo,
            'valorInventario' => $productos->sum(fn ($p) => $p->stock * $p->precio_unitario),
            'generado' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-inventario-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportarCsvInventario(Request $request): StreamedResponse
    {
        $soloBajo = $request->boolean('bajo');
        $productos = $this->productosInventario($soloBajo);

        return CsvExporter::stream(
            'reporte-inventario-'.now()->format('Y-m-d').'.csv',
            ['Producto', 'Categoría', 'Tipo', 'Stock', 'Umbral', 'Precio (Bs)', 'Valor en stock (Bs)', 'Estado'],
            $productos->map(fn (Producto $p) => [
                $p->nombre,
                $p->categoria->nombre,
                $p->categoria->tipo,
                $p->stock,
                $p->umbral_alerta,
                number_format((float) $p->precio_unitario, 2),
                number_format($p->stock * $p->precio_unitario, 2),
                $p->tieneStockBajo() ? 'Bajo stock' : 'OK',
            ])
        );
    }

    private function productosInventario(bool $soloBajo): Collection
    {
        return Producto::with('categoria')
            ->when($soloBajo, fn ($q) => $q->whereColumn('stock', '<=', 'umbral_alerta'))
            ->orderBy('stock')
            ->get();
    }

    private function masVendidos(Collection $ventas): Collection
    {
        return $ventas
            ->flatMap(fn (Venta $v) => $v->detalles)
            ->groupBy('producto_id')
            ->map(fn ($detalles) => [
                'producto' => $detalles->first()->producto->nombre,
                'cantidad' => $detalles->sum('cantidad'),
                'ingresos' => $detalles->sum(fn ($d) => $d->cantidad * $d->precio_unitario),
            ])
            ->sortByDesc('cantidad')
            ->take(10)
            ->values();
    }
}
