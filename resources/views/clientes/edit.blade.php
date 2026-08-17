<x-app-layout>
    <x-slot name="titulo">Editar cliente</x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            @method('PUT')
            @include('clientes.form')
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-orange-600 text-white font-medium px-5 py-2.5 rounded-lg hover:bg-orange-700 transition">Guardar cambios</button>
                <a href="{{ route('clientes.show', $cliente) }}" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
