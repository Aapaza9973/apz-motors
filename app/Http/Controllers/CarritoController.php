<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\Carrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Carrito del catálogo público (sin autenticación, vive en la sesión).
 */
class CarritoController extends Controller
{
    public function ver(): View
    {
        return view('catalogo.carrito', [
            'items' => Carrito::detalle(),
            'total' => Carrito::total(),
        ]);
    }

    public function agregar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:'.Carrito::MAX_CANTIDAD],
        ]);

        $producto = Producto::findOrFail($datos['producto_id']);

        if ($producto->stock <= 0) {
            return back()->with('status-error', "\"{$producto->nombre}\" está agotado. Consultá en el taller su reposición.");
        }

        Carrito::agregar($producto->id, (int) $datos['cantidad']);

        return back()->with('status', "{$producto->nombre} agregado al carrito.");
    }

    public function actualizar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:'.Carrito::MAX_CANTIDAD],
        ]);

        Carrito::actualizar((int) $datos['producto_id'], (int) $datos['cantidad']);

        return redirect()->route('carrito.ver')->with('status', 'Cantidad actualizada.');
    }

    public function eliminar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer'],
        ]);

        Carrito::eliminar((int) $datos['producto_id']);

        return redirect()->route('carrito.ver')->with('status', 'Producto quitado del carrito.');
    }
}
