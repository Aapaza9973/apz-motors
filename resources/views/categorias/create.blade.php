<x-app-layout>
    <x-slot name="titulo">Nueva categoría</x-slot>

    <div class="max-w-xl">
        <form method="POST" action="{{ route('categorias.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
            </div>
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo *</label>
                <select id="tipo" name="tipo" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="Repuesto" @selected(old('tipo') === 'Repuesto')>Repuesto</option>
                    <option value="Accesorio" @selected(old('tipo') === 'Accesorio')>Accesorio</option>
                </select>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-orange-600 text-white font-medium px-5 py-2.5 rounded-lg hover:bg-orange-700 transition">Guardar categoría</button>
                <a href="{{ route('categorias.index') }}" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
