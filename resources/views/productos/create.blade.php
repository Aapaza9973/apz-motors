<x-app-layout>
    <x-slot name="titulo">Nuevo producto</x-slot>

    <div class="max-w-3xl">
        <form method="POST" action="{{ route('productos.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            @include('productos.form')
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-orange-600 text-white font-medium px-5 py-2.5 rounded-lg hover:bg-orange-700 transition">Guardar producto</button>
                <a href="{{ route('productos.index') }}" class="text-gray-600 hover:text-gray-800 text-sm font-medium">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
