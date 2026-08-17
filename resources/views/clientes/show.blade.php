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
    </div>
</x-app-layout>
