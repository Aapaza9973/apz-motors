<x-app-layout>
    <x-slot name="titulo">{{ $cliente->nombre }}</x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-3">
                <h2 class="font-semibold text-gray-800 text-lg">Información</h2>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Teléfono</p>
                    <p class="text-sm text-gray-800">{{ $cliente->telefono ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Correo</p>
                    <p class="text-sm text-gray-800">{{ $cliente->email ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Dirección</p>
                    <p class="text-sm text-gray-800">{{ $cliente->direccion ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Compras registradas</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $cliente->ventas->count() }}</p>
                </div>
                <div class="rounded-lg border-orange-200 bg-orange-50/60 p-3">
                    <p class="text-xs uppercase tracking-wide text-orange-600">Puntos de fidelización</p>
                    <p class="text-2xl font-bold text-orange-700">{{ $saldoPuntos }} <span class="text-sm font-medium text-orange-500">pts</span></p>
                    <p class="text-xs text-gray-600 mt-0.5">1 punto por cada Bs 10 de compra · {{ config('puntos.puntos_por_bs_descuento') }} pts = Bs 1 de descuento</p>
                </div>
                @can('editar clientes')
                    <form method="POST" action="{{ route('clientes.puntos.ajustar', $cliente) }}" class="space-y-2 rounded-lg border-gray-200 bg-gray-50 p-3">
                        @csrf
                        <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Ajustar puntos</p>
                        <div class="flex gap-2">
                            <input type="number" name="puntos" min="-99999" max="99999" step="1" required
                                placeholder="± puntos"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        </div>
                        <input type="text" name="motivo" maxlength="255" required placeholder="Motivo (obligatorio)"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        <button type="submit" class="w-full bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">Registrar ajuste</button>
                        @error('puntos') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('motivo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </form>
                @endcan
                @can('editar clientes')
                    <a href="{{ route('clientes.edit', $cliente) }}" class="block text-center bg-orange-600 text-white font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">Editar cliente</a>
                @endcan
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-800">Historial de compras</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($cliente->ventas as $venta)
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('ventas.show', $venta) }}" class="font-medium text-orange-600 hover:text-orange-700">
                                Venta #{{ $venta->id }}
                            </a>
                            <span class="text-sm font-bold text-gray-900">Bs {{ number_format($venta->total, 2) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $venta->fecha->format('d/m/Y H:i') }} · Atendió: {{ $venta->usuario->name }}
                        </p>
                        <ul class="mt-2 space-y-1 text-xs text-gray-600">
                            @foreach ($venta->detalles as $detalle)
                                <li>• {{ $detalle->cantidad }} × {{ $detalle->producto->nombre }}</li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-5 py-10 text-sm text-gray-500 text-center">Este cliente aún no tiene compras registradas.</p>
                @endforelse
            </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">Movimientos de puntos</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($movimientosPuntos as $movimiento)
                        <div class="px-5 py-3 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-800 truncate">{{ $movimiento->concepto }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $movimiento->created_at->format('d/m/Y H:i') }}
                                    @if ($movimiento->usuario) · {{ $movimiento->usuario->name }} @endif
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-bold {{ $movimiento->puntos >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $movimiento->puntos >= 0 ? '+' : '' }}{{ $movimiento->puntos }} pts
                            </span>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-sm text-gray-500 text-center">Este cliente aún no tiene movimientos de puntos.</p>
                    @endforelse
                </div>
                @if ($movimientosPuntos->hasPages())
                    <div class="px-5 py-3 border-t border-gray-200">
                        {{ $movimientosPuntos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
