@extends('layouts.publico')

@section('titulo', $producto->nombre)

@section('contenido')
    <div class="space-y-6">
        <!-- Miga de navegación -->
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <a href="{{ route('catalogo.index') }}" class="link">Catálogo</a>
            @if ($producto->categoria)
                <span>/</span>
                <a href="{{ route('catalogo.index', ['categoria' => $producto->categoria_id]) }}" class="hover:text-gray-800 transition">
                    {{ $producto->categoria->nombre }}
                </a>
            @endif
            <span>/</span>
            <span class="text-gray-800 font-medium truncate">{{ $producto->nombre }}</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <!-- Ficha principal -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="hazard h-1"></div>
                    <div class="px-6 py-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <p class="eyebrow !text-[9px] text-gray-500">{{ $producto->categoria?->nombre ?? 'Repuesto' }}</p>
                            <span class="flex items-center gap-1.5 text-[10px] font-mono font-semibold uppercase tracking-wide {{ $producto->stock === 0 ? 'text-red-600' : ($producto->tieneStockBajo() ? 'text-yellow-700' : 'text-green-700') }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block {{ $producto->stock === 0 ? 'bg-red-600' : ($producto->tieneStockBajo() ? 'hazard-caution' : 'bg-green-600') }}"></span>
                                {{ $producto->stock === 0 ? 'Agotado' : ($producto->tieneStockBajo() ? 'Últimas unidades' : 'En stock') }}
                            </span>
                        </div>

                        <h1 class="mt-2 font-display text-2xl font-bold text-gray-900">{{ $producto->nombre }}</h1>

                        @if ($producto->descripcion)
                            <p class="mt-3 text-sm leading-relaxed text-gray-600 whitespace-pre-line">{{ $producto->descripcion }}</p>
                        @else
                            <p class="mt-3 text-sm text-gray-400 italic">Sin descripción cargada. Consultá en el taller para más detalle.</p>
                        @endif

                        <!-- Ficha técnica -->
                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <p class="eyebrow !text-[9px] text-gray-500">Ficha técnica</p>
                            <dl class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <dt class="text-[10px] font-mono uppercase tracking-wide text-gray-400">Referencia</dt>
                                    <dd class="num mt-1 text-sm font-semibold text-gray-900">#{{ $producto->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-mono uppercase tracking-wide text-gray-400">Categoría</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $producto->categoria?->nombre ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-mono uppercase tracking-wide text-gray-400">Disponibilidad</dt>
                                    <dd class="num mt-1 text-sm font-semibold text-gray-900">{{ $producto->stock }} unidad(es)</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] font-mono uppercase tracking-wide text-gray-400">Precio unitario</dt>
                                    <dd class="num mt-1 text-sm font-semibold text-gray-900">Bs {{ number_format($producto->precio_unitario, 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="hazard h-1"></div>
                </div>

                <!-- Consulta de compatibilidad -->
                <div class="card px-6 py-5 flex flex-wrap items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6 text-gray-400 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" /></svg>
                    <div class="flex-1 min-w-[220px]">
                        <p class="text-sm font-semibold text-gray-900">¿Dudás de la compatibilidad con tu moto?</p>
                        <p class="text-xs text-gray-500 mt-0.5">Decinos la marca, modelo y año de tu moto en la nota del pedido, o consultá al taller. Te confirmamos antes de despachar.</p>
                    </div>
                    @if ($enCarrito > 0)
                        <a href="{{ route('carrito.ver') }}" class="btn-ghost btn px-4 py-2 text-xs">Ir a mi pedido</a>
                    @else
                        <a href="{{ route('catalogo.index') }}" class="btn-ghost btn px-4 py-2 text-xs">Ver más repuestos</a>
                    @endif
                </div>
            </div>

            <!-- Panel de pedido -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden lg:sticky lg:top-4">
                <div class="hazard h-1"></div>
                <div class="px-6 py-5">
                    <p class="eyebrow !text-[9px] text-gray-500">Pedido online</p>
                    <p class="mt-2 num text-3xl font-bold text-gray-900">Bs {{ number_format($producto->precio_unitario, 2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Sin cuenta · pagás online o al recibir.</p>

                    @if ($producto->stock > 0)
                        <form method="POST" action="{{ route('carrito.agregar') }}" class="mt-5 flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $producto->id }}">
                            <label for="cantidad" class="sr-only">Cantidad</label>
                            <input id="cantidad" type="number" name="cantidad" value="{{ max(1, $enCarrito) }}" min="1" max="{{ min(99, $producto->stock) }}"
                                class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm text-center">
                            <button type="submit" class="btn-flame flex-1 justify-center">Agregar al carrito</button>
                        </form>
                        @if ($enCarrito > 0)
                            <p class="mt-2 text-xs text-gray-500 num">Ya tenés {{ $enCarrito }} en tu carrito.</p>
                        @endif
                        <a href="{{ route('carrito.ver') }}" class="btn-dark btn w-full mt-3 justify-center">Ver carrito y finalizar</a>
                    @else
                        <p class="mt-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            Agotado por el momento. Consultá al taller cuándo vuelve a estar disponible.
                        </p>
                        <a href="{{ route('pedidos.consultar') }}" class="link text-xs block text-center mt-4">Consultar mi pedido</a>
                    @endif

                    <ul class="mt-5 space-y-2 text-xs text-gray-500 border-t border-gray-100 pt-4">
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 inline-block shrink-0"></span>
                            Confirmamos tu pedido desde el taller antes de cobrar.
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 inline-block shrink-0"></span>
                            Seguí el estado con tu número y teléfono.
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-600 inline-block shrink-0"></span>
                            Retirá en el taller o coordinamos la entrega.
                        </li>
                    </ul>
                </div>
                <div class="hazard h-1"></div>
            </div>
        </div>

        <!-- Encuesta exprés de mejora del catálogo -->
        <x-encuesta-catalogo origen="producto" />

        <!-- Relacionados de la misma categoría -->
        @if ($relacionados->isNotEmpty())
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="eyebrow !text-[9px] text-gray-500">Misma categoría</p>
                        <h2 class="mt-1 font-display text-lg font-bold text-gray-900">También te puede servir</h2>
                    </div>
                    @if ($producto->categoria)
                        <a href="{{ route('catalogo.index', ['categoria' => $producto->categoria_id]) }}" class="link text-xs">Ver la categoría →</a>
                    @endif
                </div>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    @foreach ($relacionados as $relacionado)
                        <a href="{{ route('catalogo.producto', $relacionado) }}"
                            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col p-4 transition hover:border-orange-400 hover:shadow-md group">
                            <div class="flex items-start justify-between gap-2">
                                <p class="eyebrow !text-[9px] text-gray-500 truncate">{{ $relacionado->categoria?->nombre }}</p>
                                <span class="flex items-center gap-1.5 text-[10px] font-mono font-semibold uppercase tracking-wide shrink-0 {{ $relacionado->stock === 0 ? 'text-red-600' : ($relacionado->tieneStockBajo() ? 'text-yellow-700' : 'text-green-700') }}">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block {{ $relacionado->stock === 0 ? 'bg-red-600' : ($relacionado->tieneStockBajo() ? 'hazard-caution' : 'bg-green-600') }}"></span>
                                    {{ $relacionado->stock === 0 ? 'Agotado' : ($relacionado->tieneStockBajo() ? 'Últimas' : 'En stock') }}
                                </span>
                            </div>
                            <h3 class="mt-2 font-display text-sm font-bold text-gray-900 leading-snug group-hover:text-orange-700 transition">{{ $relacionado->nombre }}</h3>
                            <p class="mt-auto pt-3 num text-base font-bold text-gray-900">Bs {{ number_format($relacionado->precio_unitario, 2) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
