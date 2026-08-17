<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::with('roles')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', 'exists:roles,name'],
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'email_verified_at' => now(),
        ]);

        $usuario->assignRole($datos['rol']);

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario \"{$usuario->name}\" creado correctamente.");
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'rol' => ['required', 'exists:roles,name'],
        ]);

        $usuario->update($datos);
        $usuario->syncRoles([$datos['rol']]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario \"{$usuario->name}\" actualizado.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === $request->user()->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $nombre = $usuario->name;
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario \"{$nombre}\" eliminado.");
    }
}
