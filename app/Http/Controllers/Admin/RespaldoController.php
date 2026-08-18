<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Respaldo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Historial de respaldos de base de datos (Documento Maestro §6).
 * Cada ejecución del comando `backup:database` deja su registro acá,
 * con el resultado y el archivo generado — consultable desde la app.
 */
class RespaldoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Respaldo::query()->latest('ejecutado_en');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        return view('respaldos.index', [
            'respaldos' => $query->paginate(15)->withQueryString(),
            'estadoActivo' => $request->query('estado'),
        ]);
    }

    /**
     * Descarga el archivo de respaldo, verificando antes que exista
     * en el disco (puede haber sido purgado por la retención).
     */
    public function descargar(Respaldo $respaldo): StreamedResponse|RedirectResponse
    {
        if ($respaldo->archivo === null || ! Storage::disk('local')->exists($respaldo->archivo)) {
            return back()->with('status-error', 'El archivo de este respaldo ya no existe en el disco (pudo ser purgado por la retención de 7 días).');
        }

        return Storage::disk('local')->download($respaldo->archivo, basename($respaldo->archivo));
    }
}
