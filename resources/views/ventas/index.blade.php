<x-app-layout>
    <x-slot name="titulo">Ventas</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="date" name="desde" value="{{ request('desde') }}"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                <span class="text-gray-400 text-sm">hasta</span>
                <input type="date" name="hasta" value="{{ request('hasta') }}"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                <select name="estado" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="">Todos los estados</option>
                    @foreach (['Pendiente', 'Pagado', 'Cancelada'] as $estado)
                        <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">Filtrar</button>
            </form>

            @can('crear ventas')
                <a href="{{ route('ventas.create') }}" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">+ Nueva venta</a>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">N.º</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Vendedor</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($ventas as $venta)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">#{{ $venta->id }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $venta->usuario->name }}</td>
                            <td class="px-5 py-3 text-right font-bold text-gray-900">Bs {{ number_format($venta->total, 2) }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $venta->estado === 'Pagado' ? 'bg-green-100 text-green-700' : ($venta->estado === 'Cancelada' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ $venta->estado }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('ventas.show', $venta) }}" class="text-orange-600 hover:text-orange-700 font-medium">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin ventas en este periodo"
                                    mensaje="No hay transacciones que coincidan con el filtro. Ajustá las fechas o probá con otro estado."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $ventas->links() }}</div>
    </div>
</x-app-layout>
