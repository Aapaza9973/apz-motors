<x-app-layout>
    <x-slot name="titulo">Reporte de inventario</x-slot>

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('reportes.inventario') }}">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="bajo" value="1" @checked($soloBajo) onchange="this.form.submit()"
                        class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    Solo productos bajo umbral
                </label>
            </form>
            <div class="flex items-center gap-3">
                <div class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-900">{{ $totalProductos }}</span> productos ·
                    Valor del inventario: <span class="font-bold text-orange-600">Bs {{ number_format($valorInventario, 2) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reportes.inventario.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">PDF</a>
                    <a href="{{ route('reportes.inventario.csv', request()->query()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">Excel (CSV)</a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3">Categoría</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-center">Umbral</th>
                        <th class="px-5 py-3 text-right">Precio</th>
                        <th class="px-5 py-3 text-right">Valor en stock</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $producto)
                        <tr class="{{ $producto->tieneStockBajo() ? 'bg-red-50/60' : '' }}">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $producto->nombre }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $producto->categoria->nombre }}</td>
                            <td class="px-5 py-3 text-center font-semibold {{ $producto->tieneStockBajo() ? 'text-red-600' : 'text-gray-800' }}">{{ $producto->stock }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $producto->umbral_alerta }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">Bs {{ number_format($producto->precio_unitario, 2) }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">Bs {{ number_format($producto->stock * $producto->precio_unitario, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                @if ($producto->tieneStockBajo())
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Bajo stock</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin productos con ese filtro"
                                    mensaje="No hay repuestos que coincidan con los filtros activos. Desactivá el filtro de bajo stock para ver todo el catálogo."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($bajoStock->isNotEmpty())
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-orange-800">
                <strong>{{ $bajoStock->count() }} producto(s)</strong> requieren reposición. Revisa las <a href="{{ route('alertas.index') }}" class="underline font-medium">alertas de stock</a> para más detalle.
            </div>
        @endif
    </div>
</x-app-layout>
