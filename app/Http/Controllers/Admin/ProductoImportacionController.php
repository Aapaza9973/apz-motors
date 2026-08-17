<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CsvExporter;
use App\Http\Controllers\Controller;
use App\Services\ImportacionProductosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function preg_match;

/**
 * Importación masiva de productos desde CSV (matriz: Admin · Inventario).
 * Flujo en dos pasos: subir y validar (vista previa con reporte de errores
 * fila por fila), y luego confirmar la importación.
 */
class ProductoImportacionController extends Controller
{
    public function __construct(private ImportacionProductosService $importacion) {}

    public function formulario(): View
    {
        return view('productos.importar', [
            'previa' => session('importacion_previa'),
            'resultado' => session('importacion_resultado'),
        ]);
    }

    /**
     * Analiza el archivo y guarda las filas validadas en sesión para
     * mostrarlas en la vista previa.
     */
    public function vistaPrevia(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        try {
            $resultado = $this->importacion->analizar(
                (string) file_get_contents($request->file('archivo')->getRealPath())
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        session(['importacion_previa' => $resultado['filas']]);

        return redirect()->route('productos.importar')
            ->with('status', 'Archivo analizado: '.count($resultado['filas']).' fila(s) listas para revisar.');
    }

    /**
     * Ejecuta la importación de las filas previamente validadas y
     * reporta el resumen (creados / actualizados / errores).
     */
    public function importar(Request $request): RedirectResponse
    {
        $filas = session('importacion_previa', []);

        if (empty($filas)) {
            return redirect()->route('productos.importar')
                ->with('status-error', 'No hay filas para importar. Subí el archivo CSV primero.');
        }

        $resumen = $this->importacion->importar($filas, $request->user());

        session()->forget('importacion_previa');
        session(['importacion_resultado' => $resumen]);

        return redirect()->route('productos.importar');
    }

    /**
     * Descarta la vista previa guardada en sesión y vuelve al formulario.
     */
    public function cancelar(): RedirectResponse
    {
        session()->forget(['importacion_previa', 'importacion_resultado']);

        return redirect()->route('productos.importar');
    }

    /**
     * Reporte CSV de los movimientos de stock de una importación (lote
     * IMP-…). Permite auditar qué cambió y quién lo registró.
     */
    public function reporteMovimientos(string $lote): StreamedResponse
    {
        // Solo acepta lotes con el formato generado por el sistema.
        if (! preg_match('/^IMP-\d{8}-\d{6}$/', $lote)) {
            abort(404);
        }

        $movimientos = $this->importacion->movimientosDeLote($lote);

        return CsvExporter::stream(
            "movimientos-{$lote}.csv",
            ['Lote', 'Fecha', 'Producto', 'Tipo', 'Cantidad', 'Stock resultante', 'Motivo', 'Registrado por'],
            $movimientos->map(fn ($m) => [
                $lote,
                $m->created_at->format('d/m/Y H:i'),
                $m->producto?->nombre ?? '—',
                $m->tipo,
                $m->cantidad,
                $m->stock_resultante,
                $m->motivo ?? '',
                $m->usuario?->name ?? '—',
            ])
        );
    }

    /**
     * Plantilla CSV de ejemplo para armar el archivo de importación.
     */
    public function plantilla(): StreamedResponse
    {
        return CsvExporter::stream(
            'plantilla-importacion-productos.csv',
            ImportacionProductosService::COLUMNAS,
            [
                ['Filtro de aire de alto flujo', 'Repuestos de Motor', '45.00', '10', '5', '25.00', 'Filtro de aire de alto flujo', 'Repuesto'],
                ['Casco integral talla M', 'Cascos y Seguridad', '450.00', '5', '2', '300.00', 'Casco certificado DOT', 'Accesorio'],
            ]
        );
    }
}
