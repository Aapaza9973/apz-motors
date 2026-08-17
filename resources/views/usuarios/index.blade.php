<x-app-layout>
    <x-slot name="titulo">Usuarios y roles</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Gestiona los accesos del personal y sus roles.</p>
            <a href="{{ route('usuarios.create') }}" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">+ Nuevo usuario</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Usuario</th>
                        <th class="px-5 py-3">Correo</th>
                        <th class="px-5 py-3">Rol</th>
                        <th class="px-5 py-3">Creado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($usuarios as $usuario)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $usuario->name }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $usuario->email }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $usuario->hasRole('Admin') ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $usuario->getRoleNames()->first() ?? 'Sin rol' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $usuario->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="text-orange-600 hover:text-orange-700 font-medium">Editar</a>
                                @if ($usuario->id !== auth()->id())
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" class="inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium ml-2">Eliminar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $usuarios->links() }}</div>
    </div>
</x-app-layout>
