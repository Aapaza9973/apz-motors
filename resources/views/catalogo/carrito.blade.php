@extends('layouts.publico')

@section('titulo', 'Carrito')

@section('contenido')
    <div class="space-y-6">
        <div>
            <p class="eyebrow !text-[9px] text-gray-500">Tu selección</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Carrito</h1>
            <p class="mt-1 text-sm text-gray-500">Revisá las cantidades antes de pasar al pedido.</p>
        </div>

        @if ($items->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <x-empty-state
                    titulo="El carrito está en frío"
                    mensaje="Todavía no agregaste repuestos. El motor arranca cuando elegís la primera pieza."
                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>'
                >
                    <x-slot name="accion">
                        <a href="{{ route('catalogo.index') }}" class="btn-flame">Ver catálogo</a>
                    </x-slot>
                </x-empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                <div class="lg:col-span-2 space-y-3">
                    @foreach ($items as $linea)
                        @php($producto = $linea['producto'])
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col sm:flex-row sm:items-center gap-4 p-5">
                            <div class="flex-1 min-w-0">
                                <p class="eyebrow !text-[9px] text-gray-500">{{ $producto->categoria?->nombre }}</p>
                                <h2 class="font-display text-[15px] font-bold text-gray-900">{{ $producto->nombre }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500">Ref. #{{ $producto->id }} · {{ $producto->stock }} en stock</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <form method="POST" action="{{ route('carrito.actualizar') }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                    <input type="number" name="cantidad" value="{{ $linea['cantidad'] }}" min="1" max="{{ min(99, max(1, $producto->stock)) }}"
                                        class="w-16 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm text-center">
                                    <button type="submit" class="btn-ghost px-2.5 py-2 text-xs">OK</button>
                                </form>
                                <p class="num text-base font-bold text-gray-900 w-24 text-right">Bs {{ number_format($producto->precio_unitario * $linea['cantidad'], 2) }}</p>
                                <form method="POST" action="{{ route('carrito.eliminar') }}">
                                    @csrf
                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition p-1" title="Quitar del carrito">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:sticky lg:top-4">
                    <div class="hazard h-1"></div>
                    <div class="px-6 py-5">
                        <p class="eyebrow !text-[9px] text-gray-500">Total del pedido</p>
                        <p class="mt-1 num text-2xl font-bold text-gray-900">Bs {{ number_format($total, 2) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $items->sum('cantidad') }} producto(s) en el carrito.</p>
                        <a href="{{ route('pedidos.checkout') }}" class="btn-flame w-full mt-5 justify-center">Continuar al pedido</a>
                        <a href="{{ route('catalogo.index') }}" class="link text-xs block text-center mt-3">Seguir mirando el catálogo</a>
                    </div>
                    <div class="hazard h-1"></div>
                </div>
            </div>
        @endif
    </div>
@endsection
