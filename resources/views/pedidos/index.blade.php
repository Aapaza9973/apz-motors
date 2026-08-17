<x-app-layout>
    <x-slot name="titulo">Pedidos en línea</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Bandeja de pedidos del catálogo público — confirmá y convertí en venta.</p>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('pedidos.index') }}" class="flex flex-wrap items-center gap-2">
                    <select name="estado" onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente" @selected($estadoActivo === 'Pendiente')>Pendientes</option>
                        <option value="Confirmado" @selected($estadoActivo === 'Confirmado')>Confirmados</option>
                        <option value="Cancelado" @selected($estadoActivo === 'Cancelado')>Cancelados</option>
                    </select>
                    <select name="estado_pago" onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        <option value="">Todos los pagos</option>
                        <option value="Pagado" @selected($estadoPagoActivo === 'Pagado')>Pagados en línea</option>
                        <option value="Pendiente" @selected($estadoPagoActivo === 'Pendiente')>Pago al recibir</option>
                    </select>
                </form>
                @if ($pendientes > 0 && $estadoActivo !== 'Pendiente')
                    <a href="{{ route('pedidos.index', ['estado' => 'Pendiente']) }}" class="btn-ghost text-xs flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                        {{ $pendientes }} pendiente(s)
                    </a>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">N.º</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Contacto</th>
                        <th class="px-5 py-3 text-center">Items</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3">Pago</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3">Recibido</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pedidos as $pedido)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-gray-900">#{{ $pedido->id }}</td>
                            <td class="px-5 py-3 text-gray-800 font-medium">{{ $pedido->nombre_cliente }}</td>
                            <td class="px-5 py-3 num text-gray-600">{{ $pedido->telefono }}</td>
                            <td class="px-5 py-3 text-center num text-gray-700">{{ $pedido->items->sum('cantidad') }}</td>
                            <td class="px-5 py-3 text-right num font-semibold text-gray-900">Bs {{ number_format($pedido->total, 2) }}</td>
                            <td class="px-5 py-3">
                                @if ($pedido->estaPagado())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $pedido->metodo_pago ?? 'Pagado' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-gray-500">
                                        Al recibir
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if ($pedido->estado === 'Pendiente')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-amber-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pendiente
                                        </span>
                                    @elseif ($pedido->estado === 'Confirmado')
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-green-700">
                                            Confirmado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-gray-500">
                                            Cancelado
                                        </span>
                                    @endif
                                    @if ($pedido->clienteConfirmo())
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-blue-700">
                                            Cliente confirmó
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-500 whitespace-nowrap">{{ $pedido->created_at->format('d/m H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('pedidos.show', $pedido) }}" class="btn-ghost px-3 py-1.5 text-xs">Revisar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-4">
                                <x-empty-state
                                    titulo="{{ $estadoActivo || $estadoPagoActivo ? 'Sin pedidos con esos filtros' : 'La bandeja está despejada' }}"
                                    mensaje="{{ $estadoActivo || $estadoPagoActivo ? 'Ningún pedido coincide con el estado y el pago seleccionados. Aflojá los filtros para ver más.' : 'Cuando un cliente haga un pedido desde el catálogo público, va a caer acá para que lo confirmes.' }}"
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $pedidos->links() }}</div>
    </div>
</x-app-layout>
