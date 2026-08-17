@extends('layouts.publico')

@section('titulo', 'Pedido recibido')

@section('contenido')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="hazard h-1"></div>
            <div class="px-8 py-10 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-green-50 flex items-center justify-center text-green-600 mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <p class="eyebrow !text-[9px] text-gray-500">Pedido recibido</p>
                <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Gracias, {{ $pedido->nombre_cliente }}</h1>
                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    Tu pedido <span class="num font-semibold text-gray-900">#{{ $pedido->id }}</span> por
                    <span class="num font-semibold text-gray-900">Bs {{ number_format($pedido->total, 2) }}</span>
                    ya está en la bandeja del taller. Te llamaremos al
                    <span class="num">{{ $pedido->telefono }}</span> para coordinar la entrega.
                </p>
            </div>

            <div class="px-8 pb-8">
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
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
                </div>

                @if ($pedido->estaPagado())
                    <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        <span class="font-semibold">Pago en línea confirmado</span> — pagaste con {{ $pedido->metodo_pago }}. La venta se generará ya pagada.
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <span class="font-semibold">Pagarás al recibir</span> — abonás {{ $pedido->metodo_pago }} en el taller cuando retirás.
                    </div>
                @endif

                <div class="flex gap-3 mt-6">
                    <a href="{{ route('catalogo.index') }}" class="btn-flame flex-1 justify-center">Volver al catálogo</a>
                    <a href="{{ route('pedidos.estado', ['pedido' => $pedido, 'telefono' => $pedido->telefono]) }}" class="btn-ghost flex-1 justify-center">Ver estado del pedido</a>
                </div>
            </div>
            <div class="hazard h-1"></div>
        </div>
    </div>
@endsection
