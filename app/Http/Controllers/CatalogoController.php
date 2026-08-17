<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo público de repuestos: cualquier visitante puede consultar
 * el inventario y la disponibilidad sin iniciar sesión.
 */
class CatalogoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::query()->with('categoria')->orderBy('nombre');

        if ($categoriaId = $request->integer('categoria')) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($busqueda = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%"));
        }

        return view('catalogo.index', [
            'productos' => $query->paginate(12)->withQueryString(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'categoriaActiva' => $request->integer('categoria') ?: null,
        ]);
    }
}
