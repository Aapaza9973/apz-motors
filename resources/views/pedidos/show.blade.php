<x-app-layout>
    <x-slot name="titulo">Pedido #{{ $pedido->id }}</x-slot>

    <div class="space-y-4 max-w-4xl">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Pedido del catálogo público · recibido {{ $pedido->created_at->format('d/m/Y H:i') }}</p>
            <div class="flex items-center gap-3">
                @if ($pedido->estado === 'Pendiente')
                    @can('confirmar pedidos')
                        <form method="POST" action="{{ route('pedidos.confirmar', $pedido) }}"
                            onsubmit="return confirm('¿Confirmar el pedido #{{ $pedido->id }} por Bs {{ number_format($pedido->total, 2) }}? Se generará la venta y se descontará el stock.');">
                            @csrf
                            <button type="submit" class="btn-flame">Confirmar y convertir en venta</button>
                        </form>
                        <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}"
                            onsubmit="return confirm('¿Cancelar el pedido #{{ $pedido->id }}?');">
                            @csrf
                            <button type="submit" class="btn-ghost text-red-600 hover:bg-red-50">Cancelar pedido</button>
                        </form>
                    @endcan
                @else
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-mono font-semibold uppercase tracking-wide
                        {{ $pedido->estado === 'Confirmado' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $pedido->estado }}
                    </span>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 space-y-1">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="hazard h-1"></div>
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="eyebrow !text-[9px] text-gray-500">Repuestos pedidos</p>
                    <p class="num text-xl font-bold text-gray-900 mt-1">Bs {{ number_format($pedido->total, 2) }}</p>
                </div>
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-6 py-2.5">Producto</th>
                            <th class="px-6 py-2.5 text-center">Cant.</th>
                            <th class="px-6 py-2.5 text-right">P. unit.</th>
                            <th class="px-6 py-2.5 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pedido->items as $item)
                            <tr>
                                <td class="px-6 py-3">
                                    <span class="text-gray-800">{{ $item->nombre }}</span>
                                    <span class="block text-[11px] font-mono text-gray-400">Ref. #{{ $item->producto_id }} · stock actual: {{ $item->producto?->stock ?? '—' }}</span>
                                </td>
                                <td class="px-6 py-3 text-center num text-gray-700">{{ $item->cantidad }}</td>
                                <td class="px-6 py-3 text-right num text-gray-600">Bs {{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="px-6 py-3 text-right num font-semibold text-gray-900">Bs {{ number_format($item->precio_unitario * $item->cantidad, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="hazard h-1"></div>
            </div>

            <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="eyebrow !text-[9px] text-gray-500">Pago</p>
                </div>
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Estado</dt>
                        <dd>
                            @if ($pedido->estaPagado())
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-mono font-semibold uppercase tracking-wide text-emerald-700">Pagado</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[10px] font-mono font-semibold uppercase tracking-wide text-amber-700">Pendiente</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Método</dt>
                        <dd class="text-gray-900">{{ $pedido->metodo_pago ?: '—' }}</dd>
                    </div>
                    @if ($pedido->referencia_pago)
                        <div class="flex justify-between px-6 py-2.5">
                            <dt class="text-gray-500">Referencia</dt>
                            <dd class="num text-gray-900 text-xs">{{ $pedido->referencia_pago }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="eyebrow !text-[9px] text-gray-500">Datos del cliente</p>
                </div>
                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex justify-between px-6 py-2.5">
                            <dt class="text-gray-500">Nombre</dt>
                            <dd class="text-gray-900 font-medium text-right">{{ $pedido->nombre_cliente }}</dd>
                        </div>
                        <div class="flex justify-between px-6 py-2.5">
                            <dt class="text-gray-500">Teléfono</dt>
                            <dd class="num text-gray-900">{{ $pedido->telefono }}</dd>
                        </div>
                        @if ($pedido->email)
                            <div class="flex justify-between px-6 py-2.5">
                                <dt class="text-gray-500">Correo</dt>
                                <dd class="num text-gray-900 text-right">{{ $pedido->email }}</dd>
                            </div>
                        @endif
                        @if ($pedido->direccion)
                            <div class="flex justify-between px-6 py-2.5">
                                <dt class="text-gray-500">Dirección</dt>
                                <dd class="text-gray-900 text-right max-w-[140px]">{{ $pedido->direccion }}</dd>
                            </div>
                        @endif
                        @if ($pedido->nota)
                            <div class="px-6 py-2.5">
                                <dt class="text-gray-500 text-xs mb-1">Nota</dt>
                                <dd class="text-gray-800 text-xs">{{ $pedido->nota }}</dd>
                            </div>
                        @endif
                        @if ($pedido->clienteConfirmo())
                            <div class="px-6 py-2.5">
                                <dt class="text-gray-500 text-xs mb-1">Confirmación del cliente</dt>
                                <dd class="text-blue-700 text-xs font-medium">Confirmó el {{ $pedido->cliente_confirmado_en->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if ($pedido->estado !== 'Pendiente')
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 text-sm">
                        <p class="eyebrow !text-[9px] text-gray-500">Procesado por</p>
                        <p class="mt-1.5 text-gray-800">{{ $pedido->usuario?->name ?? '—' }}</p>
                        @if ($pedido->venta)
                            <a href="{{ route('ventas.show', $pedido->venta) }}" class="link text-xs mt-2 inline-block">
                                Ver venta #{{ $pedido->venta->id }} →
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
