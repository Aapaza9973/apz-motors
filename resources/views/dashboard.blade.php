<x-app-layout>
    <x-slot name="titulo">Tablero</x-slot>

    <div class="space-y-6">
        <!-- Hero: el día de la tienda en un vistazo, sobre el banner de marca -->
        <div class="hero hero-banner rise">
            <div class="px-6 py-6 sm:px-8 sm:py-7 flex flex-wrap items-center justify-between gap-6">
                <div>
                    <p class="hero-eyebrow">Tablero del día</p>
                    <p class="mt-3 hero-value">{{ 'Bs '.number_format($ingresosHoy, 2) }}</p>
                    <p class="mt-2 text-sm text-gray-400">ingresos registrados hoy · {{ $ventasHoy }} venta(s)</p>
                </div>
                <div class="flex items-center gap-5">
                    <img src="{{ asset('images/icono.png') }}" alt="APZ Motor's" class="w-14 h-14 object-contain opacity-90">
                    @can('crear ventas')
                        <a href="{{ route('ventas.create') }}" class="btn">
                            Nueva venta
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Franja de instrumentos -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 rise rise-1">
            <div class="kpi">
                <p class="kpi-label">Ventas hoy</p>
                <p class="kpi-value mt-1">{{ $ventasHoy }}</p>
                <p class="mt-1 text-xs text-gray-500">transacciones</p>
            </div>
            <div class="kpi">
                <p class="kpi-label">Productos catálogo</p>
                <p class="kpi-value mt-1">{{ $productosTotales }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $categoriasTotales }} categorías</p>
            </div>
            <div class="kpi">
                <p class="kpi-label">Clientes</p>
                <p class="kpi-value mt-1">{{ $clientesTotales }}</p>
                <p class="mt-1 text-xs text-gray-500">base de datos</p>
            </div>
            <div class="kpi {{ $productosBajoStock > 0 ? '!border-red-200' : '' }}">
                <p class="kpi-label flex items-center gap-1.5">
                    <span class="w-2 h-2 hazard-caution rounded-full inline-block"></span>
                    Bajo stock
                </p>
                <p class="kpi-value mt-1 {{ $productosBajoStock > 0 ? 'text-red-600' : '' }}">{{ $productosBajoStock }}</p>
                <p class="mt-1 text-xs {{ $productosBajoStock > 0 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $productosBajoStock > 0 ? 'requieren reposición' : 'todo en orden' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 rise rise-2">
            <!-- Últimas ventas -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <p class="eyebrow">Últimas ventas</p>
                    @can('ver ventas')
                        <a href="{{ route('ventas.index') }}" class="link text-xs">Ver todas →</a>
                    @endcan
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($ultimasVentas as $venta)
                        <div class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">#{{ $venta->id }} · {{ $venta->cliente?->nombre ?? 'Consumidor final' }}</p>
                                <p class="text-xs text-gray-500 num mt-0.5">{{ $venta->fecha->format('d/m/Y H:i') }} · {{ $venta->usuario->name }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-gray-900 num">Bs {{ number_format($venta->total, 2) }}</p>
                                <span class="badge mt-0.5 {{ $venta->estado === 'Pagado' ? 'badge-ok' : ($venta->estado === 'Cancelada' ? 'badge-alarm' : 'badge-warn') }}">
                                    {{ $venta->estado }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-gray-500 text-center">Aún no hay ventas registradas hoy.</p>
                    @endforelse
                </div>
            </div>

            <!-- Stock bajo -->
            <div class="card overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <p class="eyebrow">Reposiciones pendientes</p>
                    <a href="{{ route('reportes.inventario', ['bajo' => 1]) }}" class="link text-xs">Reporte →</a>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($productosBajoStockLista as $producto)
                        <div class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                            <div class="min-w-0 flex items-center gap-3">
                                <span class="w-1 h-8 hazard-caution rounded-full flex-shrink-0"></span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $producto->nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ $producto->categoria->nombre }}</p>
                                </div>
                            </div>
                            <span class="badge badge-alarm flex-shrink-0">
                                {{ $producto->stock }} / umbral {{ $producto->umbral_alerta }}
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-sm text-gray-500 text-center">No hay productos bajo su umbral.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Panel de instrumentos: tacómetro de ventas + medidor de stock -->
        @php
            $tacoMax = 20; // línea roja del tacómetro: 20 ventas/día
            $tacoFrac = $ventasHoy > 0 ? min($ventasHoy / $tacoMax, 1) : 0;
            $stockPct = (int) $stockSalud;
            $stockColor = $stockPct >= 70 ? '#16794b' : ($stockPct >= 40 ? '#ffb800' : '#d92d20');
            $tacoColor = $ventasHoy >= $tacoMax ? '#d92d20' : ($ventasHoy >= $tacoMax * 0.6 ? '#ffb800' : '#f54505');
        @endphp
        <div class="card overflow-hidden rise rise-3">
            <div class="hazard h-1"></div>
            <div class="px-6 py-5">
                <p class="eyebrow !text-[9px]">Panel de instrumentos</p>
                <p class="text-sm text-gray-600 mt-1">
                    <span class="num font-semibold text-gray-900">{{ $productosBajoStock }}</span> producto(s) bajo umbral de
                    <span class="num font-semibold text-gray-900">{{ $productosTotales }}</span> en catálogo ·
                    <span class="num font-semibold text-gray-900">{{ $ventasPendientes }}</span> venta(s) pendientes de pago.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mt-6">
                    <!-- Tacómetro de ventas del día -->
                    <div class="flex flex-col items-center">
                        <svg viewBox="0 0 220 132" class="w-full max-w-[260px]" role="img" aria-label="Tacómetro de ventas del día: {{ $ventasHoy }} de {{ $tacoMax }}">
                            <!-- pista -->
                            <path d="M 20 112 A 90 90 0 0 1 200 112" fill="none" stroke="#26272b" stroke-width="10" stroke-linecap="round" />
                            <!-- zona de línea roja -->
                            <path d="M 20 112 A 90 90 0 0 1 200 112" fill="none" stroke="#d92d20" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="28.27 282.74" stroke-dashoffset="-254.47" opacity="0.35" />
                            <!-- valor -->
                            <path d="M 20 112 A 90 90 0 0 1 200 112" fill="none" stroke="{{ $tacoColor }}" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ number_format($tacoFrac * 282.74, 1) }} 282.74" />
                            <!-- marcas -->
                            @foreach ([0, 25, 50, 75, 100] as $t)
                                <g transform="rotate({{ -90 + $t * 1.8 }} 110 112)">
                                    <line x1="110" y1="24" x2="110" y2="32" stroke="#4d5157" stroke-width="2" />
                                </g>
                            @endforeach
                            <!-- aguja -->
                            <g transform="rotate({{ -90 + $tacoFrac * 180 }} 110 112)">
                                <line x1="110" y1="112" x2="110" y2="36" stroke="{{ $tacoColor }}" stroke-width="3" stroke-linecap="round" />
                            </g>
                            <circle cx="110" cy="112" r="7" fill="#18191c" stroke="#4d5157" stroke-width="2" />
                            <text x="24" y="128" class="num" font-size="10" fill="#8b9097" font-family="monospace">0</text>
                            <text x="186" y="128" class="num" font-size="10" fill="#8b9097" font-family="monospace" text-anchor="end">{{ $tacoMax }}</text>
                            <text x="110" y="16" text-anchor="middle" font-size="12" font-weight="700" fill="#ffffff" font-family="monospace">{{ $ventasHoy }}</text>
                        </svg>
                        <p class="eyebrow !text-[9px] mt-2">Ventas hoy / {{ $tacoMax }}</p>
                    </div>

                    <!-- Medidor de salud del stock -->
                    <div class="flex flex-col items-center">
                        <svg viewBox="0 0 220 132" class="w-full max-w-[260px]" role="img" aria-label="Salud del stock: {{ $stockPct }} por ciento">
                            <!-- pista -->
                            <path d="M 20 112 A 90 90 0 0 1 200 112" fill="none" stroke="#26272b" stroke-width="10" stroke-linecap="round" />
                            <!-- valor -->
                            <path d="M 20 112 A 90 90 0 0 1 200 112" fill="none" stroke="{{ $stockColor }}" stroke-width="10" stroke-linecap="round"
                                stroke-dasharray="{{ number_format($stockPct / 100 * 282.74, 1) }} 282.74" />
                            <!-- marcas -->
                            @foreach ([0, 25, 50, 75, 100] as $t)
                                <g transform="rotate({{ -90 + $t * 1.8 }} 110 112)">
                                    <line x1="110" y1="24" x2="110" y2="32" stroke="#4d5157" stroke-width="2" />
                                </g>
                            @endforeach
                            <!-- aguja -->
                            <g transform="rotate({{ -90 + $stockPct * 1.8 }} 110 112)">
                                <line x1="110" y1="112" x2="110" y2="36" stroke="{{ $stockColor }}" stroke-width="3" stroke-linecap="round" />
                            </g>
                            <circle cx="110" cy="112" r="7" fill="#18191c" stroke="#4d5157" stroke-width="2" />
                            <text x="24" y="128" class="num" font-size="10" fill="#8b9097" font-family="monospace">0%</text>
                            <text x="186" y="128" class="num" font-size="10" fill="#8b9097" font-family="monospace" text-anchor="end">100%</text>
                            <text x="110" y="16" text-anchor="middle" font-size="12" font-weight="700" fill="#ffffff" font-family="monospace">{{ $stockPct }}%</text>
                        </svg>
                        <p class="eyebrow !text-[9px] mt-2">Salud del stock</p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $productosBajoStock > 0 ? $productosBajoStock.' repuesto(s) al límite — reponer antes de agotar' : 'Todo el catálogo por encima del umbral' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
