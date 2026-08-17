<x-app-layout>
    <x-slot name="titulo">Productos</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre…"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                <select name="categoria" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(request('categoria') == $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
                <select name="stock" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="">Todo el stock</option>
                    <option value="bajo" @selected(request('stock') === 'bajo')>Solo bajo stock</option>
                </select>
                <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">Filtrar</button>
            </form>

            @can('crear productos')
                <a href="{{ route('productos.create') }}" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">+ Nuevo producto</a>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3">Categoría</th>
                        <th class="px-5 py-3 text-right">Precio</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-center">Umbral</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $producto)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('productos.show', $producto) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $producto->nombre }}</a>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $producto->categoria->nombre }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 font-medium">Bs {{ number_format($producto->precio_unitario, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $producto->tieneStockBajo() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $producto->stock }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $producto->umbral_alerta }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @can('editar productos')
                                    <a href="{{ route('productos.edit', $producto) }}" class="text-orange-600 hover:text-orange-700 font-medium">Editar</a>
                                @endcan
                                @can('eliminar productos')
                                    <form method="POST" action="{{ route('productos.destroy', $producto) }}" class="inline" onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium ml-2">Eliminar</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-4">
                                <x-empty-state
                                    :titulo="request('q') || request('categoria') || request('stock') ? 'Sin repuestos para este filtro' : 'El catálogo está por estrenarse'"
                                    :mensaje="request('q') || request('categoria') || request('stock')
                                        ? 'No encontramos repuestos que coincidan con tu búsqueda. Probá con otro nombre o categoría.'
                                        : 'Todavía no cargamos repuestos en el sistema. Agregá el primero para empezar a vender.'"
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>'
                                    :accion="request('q') || request('categoria') || request('stock')
                                        ? '<a href=&quot;' . route('productos.index') . '&quot; class=&quot;link text-xs&quot;>Limpiar filtros</a>'
                                        : null"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $productos->links() }}</div>
    </div>
</x-app-layout>
