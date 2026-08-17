@extends('layouts.publico')

@section('titulo', 'Catálogo de repuestos')

@section('contenido')
    <div class="space-y-6">
        <!-- Encabezado del catálogo -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow !text-[9px] text-gray-500">Disponibilidad en tiempo real</p>
                <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Catálogo de repuestos</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $productos->total() }} producto(s) disponibles
                    @if ($categoriaActiva) · {{ $categorias->firstWhere('id', $categoriaActiva)?->nombre }} @endif
                </p>
            </div>

            <form method="GET" action="{{ route('catalogo.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                @if ($categoriaActiva)
                    <input type="hidden" name="categoria" value="{{ $categoriaActiva }}">
                @endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar repuesto…"
                    class="input flex-1 sm:w-64 px-3 py-2">
                <button type="submit" class="btn px-4 py-2">Buscar</button>
            </form>
        </div>

        <!-- Filtros por categoría -->
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('catalogo.index', ['q' => request('q')]) }}"
                class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ ! $categoriaActiva ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400' }}">
                Todas
            </a>
            @foreach ($categorias as $categoria)
                <a href="{{ route('catalogo.index', ['categoria' => $categoria->id, 'q' => request('q')]) }}"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold border transition {{ $categoriaActiva === $categoria->id ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400' }}">
                    {{ $categoria->nombre }}
                </a>
            @endforeach
        </div>

        <!-- Rejilla de repuestos -->
        @if ($productos->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <x-empty-state
                    :titulo="$categoriaActiva || request('q') ? 'Sin repuestos para este filtro' : 'El catálogo está por estrenarse'"
                    :mensaje="$categoriaActiva || request('q')
                        ? 'No encontramos repuestos que coincidan con tu búsqueda. Revisá la ortografía o probá con otra categoría.'
                        : 'Todavía no cargamos repuestos en el sistema. Volvé pronto.'"
                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>'
                >
                    @if ($categoriaActiva || request('q'))
                        <x-slot name="accion">
                            <a href="{{ route('catalogo.index') }}" class="link text-xs">Limpiar filtros</a>
                        </x-slot>
                    @endif
                </x-empty-state>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($productos as $producto)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-5 pt-4 pb-3 border-b border-gray-100 flex items-start justify-between gap-2">
                            <p class="eyebrow !text-[9px] text-gray-500">{{ $producto->categoria->nombre }}</p>
                            @if ($producto->tieneStockBajo())
                                <span class="flex items-center gap-1.5 text-[10px] font-mono font-semibold uppercase tracking-wide {{ $producto->stock === 0 ? 'text-red-600' : 'text-yellow-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block {{ $producto->stock === 0 ? 'bg-red-600' : 'hazard-caution' }}"></span>
                                    {{ $producto->stock === 0 ? 'Agotado' : 'Últimas unidades' }}
                                </span>
                            @else
                                <span class="text-[10px] font-mono font-semibold uppercase tracking-wide text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-600 inline-block mr-1"></span>En stock
                                </span>
                            @endif
                        </div>
                        <div class="px-5 py-4 flex-1">
                            <h2 class="font-display text-[15px] font-bold text-gray-900 leading-snug">{{ $producto->nombre }}</h2>
                            <p class="mt-1.5 text-xs text-gray-500 line-clamp-2">{{ $producto->descripcion ?: 'Sin descripción.' }}</p>
                        </div>
                        <div class="px-5 py-4 border-t border-gray-100">
                            <div class="flex items-center justify-between">
                                <p class="num text-lg font-bold text-gray-900">Bs {{ number_format($producto->precio_unitario, 2) }}</p>
                                <p class="text-[10px] font-mono text-gray-400 uppercase tracking-wide">Ref. #{{ $producto->id }}</p>
                            </div>
                            @if ($producto->stock > 0)
                                <form method="POST" action="{{ route('carrito.agregar') }}" class="mt-3 flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                                    <input type="number" name="cantidad" value="1" min="1" max="{{ min(99, $producto->stock) }}"
                                        class="w-16 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm text-center">
                                    <button type="submit" class="btn-flame flex-1 justify-center text-xs">Agregar al carrito</button>
                                </form>
                            @else
                                <p class="mt-3 text-xs font-medium text-red-600">Agotado — consultá la reposición en el taller.</p>
                            @endif
                        </div>
                        <div class="hazard h-1"></div>
                    </div>
                @endforeach
            </div>
        @endif

        <div>{{ $productos->links() }}</div>

        <!-- Nota comercial -->
        <div class="card px-5 py-4 flex flex-wrap items-center gap-3">
            <img src="{{ asset('images/icono.png') }}" alt="APZ Motor's" class="w-9 h-9 object-contain">
            <div class="flex-1 min-w-[220px]">
                <p class="text-sm font-semibold text-gray-900">¿Necesitás un repuesto que no está en la lista?</p>
                <p class="text-xs text-gray-500 mt-0.5">Consultá disponibilidad y precios en el taller — Av. La Paz, Bolivia. Tu ruta, nuestro compromiso.</p>
            </div>
            <a href="{{ route('login') }}" class="btn-ghost btn px-4 py-2 text-xs">Ingresar al sistema</a>
        </div>
    </div>
@endsection
