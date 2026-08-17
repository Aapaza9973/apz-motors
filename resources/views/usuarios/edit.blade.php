<x-app-layout>
    <x-slot name="titulo">Editar usuario</x-slot>

    <div class="max-w-xl">
        <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre completo *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $usuario->name) }}" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
            </div>
            <div>
                <label for="rol" class="block text-sm font-medium text-gray-700">Rol *</label>
                <select id="rol" name="rol" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->name }}" @selected(old('rol', $usuario->getRoleNames()->first()) === $rol->name)>{{ $rol->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-orange-600 text-white font-medium px-5 py-2.5 rounded-lg hover:bg-orange-700 transition">Guardar cambios</button>
                <a href="{{ route('usuarios.index') }}" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
