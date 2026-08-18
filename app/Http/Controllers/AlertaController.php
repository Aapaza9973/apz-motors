<?php

namespace App\Http\Controllers;

use App\Models\AlertaStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlertaController extends Controller
{
    public function index(): View
    {
        return view('alertas.index', [
            'alertas' => AlertaStock::with('producto.categoria')->latest()->paginate(20),
            'pendientes' => AlertaStock::where('leida', false)->count(),
        ]);
    }

    public function marcarLeida(AlertaStock $alerta): RedirectResponse
    {
        $alerta->update(['leida' => true]);

        return back()->with('status', 'Alerta marcada como leída.');
    }

    public function marcarTodasLeidas(): RedirectResponse
    {
        AlertaStock::where('leida', false)->update(['leida' => true]);

        return back()->with('status', 'Todas las alertas fueron marcadas como leídas.');
    }
}
