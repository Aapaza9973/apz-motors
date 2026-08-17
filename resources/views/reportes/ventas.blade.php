<x-app-layout>
    <x-slot name="titulo">Reporte de ventas</x-slot>

    <div class="space-y-6">
        <form method="GET" action="{{ route('reportes.ventas') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex flex-wrap items-center gap-3">
            <select name="rango" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                @foreach (['hoy' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'personalizado' => 'Personalizado'] as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($rango === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
            <input type="date" name="desde" value="{{ request('desde') }}" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm" placeholder="Desde">
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm" placeholder="Hasta">
            <button type="submit" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">Generar reporte</button>
            <div class="flex items-center gap-2 border-l border-gray-200 pl-3">
                <a href="{{ route('reportes.ventas.pdf', request()->query()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.ventas.csv', request()->query()) }}" class="inline-flex items-center gap-1.5 text-sm font-medium px-3 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Excel (CSV)
                </a>
            </div>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Periodo</p>
                <p class="mt-1 text-sm font-semibold text-gray-800">{{ $fechaInicio->format('d/m/Y') }} — {{ $fechaFin->format('d/m/Y') }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Total vendido</p>
                <p class="mt-1 text-2xl font-bold text-orange-600">Bs {{ number_format($total, 2) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <p class="text-sm text-gray-500">Transacciones / promedio</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $cantidad }}</p>
                <p class="text-xs text-gray-400">Promedio Bs {{ number_format($promedio, 2) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">Ventas del periodo</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">N.º</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Cliente</th>
                            <th class="px-5 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ventas as $venta)
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-medium text-gray-800">#{{ $venta->id }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</td>
                                <td class="px-5 py-3 text-right font-semibold text-gray-900">Bs {{ number_format($venta->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin ventas en el periodo"
                                    mensaje="No hay transacciones en el rango seleccionado. Probá con otro rango de fechas."
                                />
                            </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">Productos más vendidos</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3 text-center">Cantidad</th>
                            <th class="px-5 py-3 text-right">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($masVendidos as $item)
                            <tr>
                                <td class="px-5 py-3 text-gray-800">{{ $item['producto'] }}</td>
                                <td class="px-5 py-3 text-center font-semibold text-gray-900">{{ $item['cantidad'] }}</td>
                                <td class="px-5 py-3 text-right text-gray-700">Bs {{ number_format($item['ingresos'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin ventas para ranking"
                                    mensaje="No hay productos vendidos en el rango seleccionado."
                                />
                            </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
