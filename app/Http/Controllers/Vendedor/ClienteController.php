<?php

namespace App\Http\Controllers\Vendedor;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\PuntosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(private PuntosService $puntosService) {}
    public function index(Request $request): View
    {
        $query = Cliente::query();

        if ($busqueda = $request->query('q')) {
            $query->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%")
                ->orWhere('telefono', 'like', "%{$busqueda}%");
        }

        return view('clientes.index', [
            'clientes' => $query->withCount('ventas')->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $cliente = Cliente::create($datos);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', "Cliente \"{$cliente->nombre}\" registrado correctamente.");
    }

    public function show(Cliente $cliente): View
    {
        return view('clientes.show', [
            'cliente' => $cliente->load('ventas.detalles.producto', 'ventas.usuario'),
            'saldoPuntos' => $cliente->puntosDisponibles(),
            'movimientosPuntos' => $cliente->puntos()->with('usuario', 'venta')->latest()->paginate(10),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', ['cliente' => $cliente]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $cliente->update($datos);

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', "Cliente \"{$cliente->nombre}\" actualizado.");
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $nombre = $cliente->nombre;
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('status', "Cliente \"{$nombre}\" eliminado.");
    }

    /**
     * Ajuste manual de puntos de fidelización (correcciones/bonificaciones).
     * Ruta protegida con el permiso 'editar clientes' (Admin).
     */
    public function ajustarPuntos(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate([
            'puntos' => ['required', 'integer', 'not_in:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        $this->puntosService->ajustar(
            $cliente,
            (int) $datos['puntos'],
            $datos['motivo'],
            $request->user()
        );

        return redirect()
            ->route('clientes.show', $cliente)
            ->with('status', 'Ajuste de puntos registrado. Nuevo saldo: '.$cliente->puntosDisponibles().' puntos.');
    }
}
