<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $query = Producto::with('categoria');

        if ($busqueda = $request->query('q')) {
            $query->where('nombre', 'like', "%{$busqueda}%");
        }

        if ($categoriaId = $request->query('categoria')) {
            $query->where('categoria_id', $categoriaId);
        }

        if ($request->query('stock') === 'bajo') {
            $query->whereColumn('stock', '<=', 'umbral_alerta');
        }

        return view('productos.index', [
            'productos' => $query->latest()->paginate(15)->withQueryString(),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('productos.create', [
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255', 'unique:productos,nombre'],
            'descripcion' => ['nullable', 'string'],
            'precio_unitario' => ['required', 'numeric', 'min:0.01'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'umbral_alerta' => ['required', 'integer', 'min:0'],
        ]);

        // El stock inicial se registra como movimiento de entrada para
        // mantener la trazabilidad (evita sumar dos veces el stock).
        $stockInicial = (int) $datos['stock'];
        $datos['stock'] = 0;

        $producto = Producto::create($datos);

        if ($stockInicial > 0) {
            $this->inventory->ajustarStock(
                $producto,
                $stockInicial,
                'entrada',
                'Stock inicial',
                $request->user()
            );
        }

        return redirect()
            ->route('productos.index')
            ->with('status', "Producto \"{$producto->nombre}\" registrado correctamente.");
    }

    public function show(Producto $producto): View
    {
        return view('productos.show', [
            'producto' => $producto->load('categoria', 'movimientos.usuario'),
        ]);
    }

    public function edit(Producto $producto): View
    {
        return view('productos.edit', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'nombre' => ['required', 'string', 'max:255', 'unique:productos,nombre,'.$producto->id],
            'descripcion' => ['nullable', 'string'],
            'precio_unitario' => ['required', 'numeric', 'min:0.01'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'umbral_alerta' => ['required', 'integer', 'min:0'],
        ]);

        $stockNuevo = (int) $datos['stock'];
        $delta = $stockNuevo - $producto->stock;

        if ($delta !== 0) {
            // Ajusta stock, registra movimiento y genera alertas.
            $this->inventory->ajustarStock($producto, $delta, 'ajuste', 'Ajuste manual desde catálogo', $request->user());
            unset($datos['stock']);
        }

        $producto->update($datos);

        return redirect()
            ->route('productos.index')
            ->with('status', "Producto \"{$producto->nombre}\" actualizado correctamente.");
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        Gate::authorize('delete', $producto);

        $nombre = $producto->nombre;
        $producto->delete();

        return redirect()
            ->route('productos.index')
            ->with('status', "Producto \"{$nombre}\" eliminado.");
    }
}
