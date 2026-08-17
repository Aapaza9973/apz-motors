<x-app-layout>
    <x-slot name="titulo">Categorías</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Clasifica los productos entre repuestos y accesorios.</p>
            @can('crear categorias')
                <a href="{{ route('categorias.create') }}" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">+ Nueva categoría</a>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Nombre</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3 text-center">Productos</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categorias as $categoria)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $categoria->nombre }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $categoria->tipo === 'Repuesto' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                    {{ $categoria->tipo }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-700">{{ $categoria->productos_count }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @can('editar categorias')
                                    <a href="{{ route('categorias.edit', $categoria) }}" class="text-orange-600 hover:text-orange-700 font-medium">Editar</a>
                                @endcan
                                @can('eliminar categorias')
                                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" class="inline" onsubmit="return confirm('¿Eliminar esta categoría?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium ml-2">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin categorías todavía"
                                    mensaje="Creá la primera categoría para organizar el catálogo entre repuestos y accesorios."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categorias->links() }}</div>
    </div>
</x-app-layout>
