<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Respaldo;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
