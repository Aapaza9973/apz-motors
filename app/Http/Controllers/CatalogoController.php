<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Support\Carrito;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catálogo público de repuestos: cualquier visitante puede consultar
 * el inventario y la disponibilidad sin iniciar sesión.
 */
class CatalogoController extends Controller
{
    /**
     * Ordenamientos permitidos del catálogo (lista blanca: evita inyección).
     */
    private const ORDENES = [
        'nombre' => ['columna' => 'nombre', 'direccion' => 'asc'],
        'nuevos' => ['columna' => 'id', 'direccion' => 'desc'],
        'precio-asc' => ['columna' => 'precio_unitario', 'direccion' => 'asc'],
        'precio-desc' => ['columna' => 'precio_unitario', 'direccion' => 'desc'],
    ];

    public function index(Request $request): View
    {
        $orden = (string) $request->query('orden', 'nombre');
        if (! array_key_exists($orden, self::ORDENES)) {
            $orden = 'nombre';
        }

        $query = Producto::query()->with('categoria')
            ->orderBy(self::ORDENES[$orden]['columna'], self::ORDENES[$orden]['direccion']);

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
            'orden' => $orden,
            'carritoCantidad' => Carrito::cantidadTotal(),
            'carritoTotal' => Carrito::total(),
        ]);
    }

    public function show(Producto $producto): View
    {
        $producto->load('categoria');

        $relacionados = Producto::query()
            ->where('categoria_id', $producto->categoria_id)
            ->whereKeyNot($producto->id)
            ->with('categoria')
            ->orderByDesc('stock')
            ->limit(4)
            ->get();

        return view('catalogo.producto', [
            'producto' => $producto,
            'relacionados' => $relacionados,
            'enCarrito' => Carrito::items()[$producto->id] ?? 0,
        ]);
    }
}
