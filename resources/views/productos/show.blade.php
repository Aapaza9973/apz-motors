<x-app-layout>
    <x-slot name="titulo">{{ $producto->nombre }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-3">
                <h2 class="font-semibold text-gray-800 text-lg">Detalles</h2>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Categoría</p>
                    <p class="text-sm text-gray-800">{{ $producto->categoria->nombre }} ({{ $producto->categoria->tipo }})</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Precio de venta</p>
                    <p class="text-xl font-bold text-orange-600">Bs {{ number_format($producto->precio_unitario, 2) }}</p>
                </div>
                @if ($producto->costo)
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Costo</p>
                        <p class="text-sm text-gray-800">Bs {{ number_format($producto->costo, 2) }}</p>
                    </div>
                @endif
                <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Stock</p>
                        <p class="text-2xl font-bold {{ $producto->tieneStockBajo() ? 'text-red-600' : 'text-gray-900' }}">{{ $producto->stock }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Umbral</p>
                        <p class="text-2xl font-bold text-gray-500">{{ $producto->umbral_alerta }}</p>
                    </div>
                </div>

                @if ($producto->descripcion)
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">Descripción</p>
                        <p class="text-sm text-gray-700">{{ $producto->descripcion }}</p>
                    </div>
                @endif

                @can('editar productos')
                    <a href="{{ route('productos.edit', $producto) }}" class="block text-center bg-orange-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">Editar producto</a>
                @endcan
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Movimientos de stock</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3 text-right">Cantidad</th>
                        <th class="px-5 py-3 text-center">Stock resultante</th>
                        <th class="px-5 py-3">Motivo / Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($producto->movimientos as $movimiento)
                        <tr>
                            <td class="px-5 py-3 text-gray-600">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ in_array($movimiento->tipo, ['entrada', 'devolucion']) ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ ucfirst($movimiento->tipo) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $movimiento->cantidad > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $movimiento->cantidad > 0 ? '+' : '' }}{{ $movimiento->cantidad }}
                            </td>
                            <td class="px-5 py-3 text-center text-gray-700">{{ $movimiento->stock_resultante }}</td>
                            <td class="px-5 py-3 text-gray-500">
                                {{ $movimiento->motivo }}@if ($movimiento->usuario) · {{ $movimiento->usuario->name }}@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">Sin movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
