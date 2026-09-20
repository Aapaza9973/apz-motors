<?php

namespace App\Http\Controllers;

use App\Models\EncuestaCatalogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Encuesta anónima de mejora del catálogo público.
 *
 * Los visitantes responden sin cuenta ni datos personales; el Admin
 * consulta el resumen y las respuestas desde Administración.
 */
class EncuestaCatalogoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            // Escala 1..5 (radio obligatorio).
            'satisfaccion' => ['required', 'integer', 'between:1,5'],
            'facilidad_encontrar' => ['required', 'string', 'in:'.implode(',', EncuestaCatalogo::FACILIDADES)],
            'falta' => ['nullable', 'string', 'max:500'],
            'comentario' => ['nullable', 'string', 'max:1000'],
            'origen' => ['nullable', 'string', 'in:catalogo,producto'],
        ], [
            'satisfaccion.required' => 'Elegí una calificación de 1 a 5 estrellas.',
            'facilidad_encontrar.required' => 'Contanos si te fue fácil encontrar lo que buscabas.',
        ]);

        EncuestaCatalogo::create([
            'satisfaccion' => (int) $datos['satisfaccion'],
            'facilidad_encontrar' => $datos['facilidad_encontrar'],
            'falta' => trim((string) ($datos['falta'] ?? '')) ?: null,
            'comentario' => trim((string) ($datos['comentario'] ?? '')) ?: null,
            'origen' => $datos['origen'] ?? 'catalogo',
        ]);

        // Recuerda en sesión que ya respondió (suave: solo oculta el widget).
        session(['encuesta_respondida' => true]);

        return back()->with('status', '¡Gracias por tu opinión! Nos ayuda a mejorar el catálogo.');
    }

    public function index(): View
    {
        $total = EncuestaCatalogo::count();

        $resumen = [
            'total' => $total,
            'promedio' => $total > 0 ? round((float) EncuestaCatalogo::avg('satisfaccion'), 2) : null,
            'por_facilidad' => EncuestaCatalogo::select('facilidad_encontrar', DB::raw('count(*) as total'))
                ->groupBy('facilidad_encontrar')
                ->pluck('total', 'facilidad_encontrar'),
            'satisfechos' => EncuestaCatalogo::where('satisfaccion', '>=', 4)->count(),
            'insatisfechos' => EncuestaCatalogo::where('satisfaccion', '<=', 2)->count(),
            'con_comentario' => EncuestaCatalogo::whereNotNull('comentario')->count(),
            'sugerencias_falta' => EncuestaCatalogo::whereNotNull('falta')
                ->select('falta', DB::raw('count(*) as total'))
                ->groupBy('falta')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),
        ];

        return view('encuestas.index', [
            'respuestas' => EncuestaCatalogo::latest()->paginate(20),
            'resumen' => $resumen,
        ]);
    }
}
