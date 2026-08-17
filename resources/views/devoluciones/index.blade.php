<x-app-layout>
    <x-slot name="titulo">Devoluciones</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Solicitudes de devolución y reembolso registradas.</p>
            <form method="GET" action="{{ route('devoluciones.index') }}">
                <select name="estado" onchange="this.form.submit()" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="">Todos los estados</option>
                    @foreach (['Pendiente', 'Aprobada', 'Rechazada'] as $estado)
                        <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">N.º</th>
                        <th class="px-5 py-3">Venta</th>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3 text-center">Cant.</th>
                        <th class="px-5 py-3">Motivo</th>
                        <th class="px-5 py-3 text-right">Reembolso</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($devoluciones as $devolucion)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900">#{{ $devolucion->id }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('ventas.show', $devolucion->venta) }}" class="text-orange-600 hover:text-orange-700 font-medium">
                                    Venta #{{ $devolucion->venta_id }}
                                </a>
                                <p class="text-xs text-gray-400">{{ $devolucion->venta->cliente?->nombre ?? 'Consumidor final' }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-800">{{ $devolucion->producto->nombre }}</td>
                            <td class="px-5 py-3 text-center text-gray-700">{{ $devolucion->cantidad }}</td>
                            <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ $devolucion->motivo }}</td>
                            <td class="px-5 py-3 text-right text-gray-700">
                                {{ $devolucion->monto_reembolso ? 'Bs '.number_format($devolucion->monto_reembolso, 2) : '—' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $devolucion->estado === 'Aprobada' ? 'bg-green-100 text-green-700' : ($devolucion->estado === 'Rechazada' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ $devolucion->estado }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if ($devolucion->estado === 'Pendiente' && auth()->user()->can('aprobar devoluciones'))
                                    <form method="POST" action="{{ route('devoluciones.aprobar', $devolucion) }}" class="inline" onsubmit="return confirm('¿Aprobar esta devolución? Se repondrá el stock y se registrará el reembolso.');">
                                        @csrf
                                        <button type="submit" class="text-green-600 hover:text-green-700 font-medium">Aprobar</button>
                                    </form>
                                    <form method="POST" action="{{ route('devoluciones.rechazar', $devolucion) }}" class="inline" onsubmit="return confirm('¿Rechazar esta devolución?');">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700 font-medium ml-2">Rechazar</button>
                                    </form>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin devoluciones"
                                    mensaje="No hay solicitudes de devolución por ahora. Cuando se registre una, aparecerá acá con su estado."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $devoluciones->links() }}</div>
    </div>
</x-app-layout>
