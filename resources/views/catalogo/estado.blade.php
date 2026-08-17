@extends('layouts.publico')

@section('titulo', 'Estado del pedido #'.$pedido->id)

@section('contenido')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="hazard h-1"></div>
            <div class="px-8 py-6 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="eyebrow !text-[9px] text-gray-500">Pedido #{{ $pedido->id }}</p>
                    <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Estado de tu pedido</h1>
                    <p class="mt-1 text-sm text-gray-500">Realizado el {{ $pedido->created_at->format('d/m/Y H:i') }} por {{ $pedido->nombre_cliente }}.</p>
                </div>

                @if ($pedido->estado === 'Pendiente')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-[11px] font-mono font-semibold uppercase tracking-wide text-amber-700">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>En el taller
                    </span>
                @elseif ($pedido->estado === 'Confirmado')
                    <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1.5 text-[11px] font-mono font-semibold uppercase tracking-wide text-green-700">Confirmado</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-[11px] font-mono font-semibold uppercase tracking-wide text-gray-500">Cancelado</span>
                @endif
            </div>

            <div class="px-8 py-6">
                @if ($pedido->estado === 'Pendiente')
                    <p class="text-sm text-gray-600">Tu pedido está en la <b>bandeja del taller</b>. Te llamaremos al {{ $pedido->telefono }} para coordinar la entrega y el pago.</p>
                @elseif ($pedido->estado === 'Confirmado')
                    <p class="text-sm text-gray-600">
                        Tu pedido fue <b>confirmado</b>@if ($pedido->venta) y se convirtió en la <b>venta #{{ $pedido->venta_id }}</b>@endif.
                        El stock ya fue descontado; pasá por el taller o coordiná la entrega.
                    </p>
                @else
                    <p class="text-sm text-gray-600">Tu pedido fue <b>cancelado</b>. No se descontó stock y no hay cargos pendientes. Consultanos al taller si necesitás algo.</p>
                @endif

                @if ($pedido->estaPagado())
                    <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        <span class="font-semibold">Pago: Bs {{ number_format($pedido->total, 2) }}</span> — abonado con {{ $pedido->metodo_pago }}.
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <span class="font-semibold">Pago al recibir</span> — abonás {{ $pedido->metodo_pago }} en el taller.
                    </div>
                @endif

                @if ($pedido->clienteConfirmo())
                    <div class="mt-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Confirmaste tu pedido el {{ $pedido->cliente_confirmado_en->format('d/m/Y H:i') }} — el taller ya lo tiene priorizado.
                    </div>
                @endif

                <table class="mt-6 min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-4 py-2.5">Producto</th>
                            <th class="px-4 py-2.5 text-center">Cant.</th>
                            <th class="px-4 py-2.5 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pedido->items as $item)
                            <tr>
                                <td class="px-4 py-2.5 text-gray-800">{{ $item->nombre }}</td>
                                <td class="px-4 py-2.5 text-center num text-gray-700">{{ $item->cantidad }}</td>
                                <td class="px-4 py-2.5 text-right num text-gray-900">Bs {{ number_format($item->precio_unitario * $item->cantidad, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="num text-right text-lg font-bold text-gray-900 mt-4">Total: Bs {{ number_format($pedido->total, 2) }}</p>
            </div>

            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('catalogo.index') }}" class="link text-xs">Volver al catálogo</a>
                <a href="{{ route('pedidos.consultar') }}" class="link text-xs">Consultar otro pedido</a>
            </div>
            <div class="hazard h-1"></div>
        </div>
    </div>
@endsection
