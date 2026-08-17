<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('categorias.index', [
            'categorias' => Categoria::withCount('productos')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('categorias.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre'],
            'tipo' => ['required', 'in:Repuesto,Accesorio'],
        ]);

        $categoria = Categoria::create($datos);

        return redirect()
            ->route('categorias.index')
            ->with('status', "Categoría \"{$categoria->nombre}\" creada correctamente.");
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.edit', ['categoria' => $categoria]);
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:categorias,nombre,'.$categoria->id],
            'tipo' => ['required', 'in:Repuesto,Accesorio'],
        ]);

        $categoria->update($datos);

        return redirect()
            ->route('categorias.index')
            ->with('status', "Categoría \"{$categoria->nombre}\" actualizada.");
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->exists()) {
            return back()->withErrors(['error' => "No se puede eliminar \"{$categoria->nombre}\": tiene productos asociados."]);
        }

        $categoria->delete();

        return redirect()
            ->route('categorias.index')
            ->with('status', "Categoría \"{$categoria->nombre}\" eliminada.");
    }
}
