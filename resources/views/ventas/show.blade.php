<x-app-layout>
    <x-slot name="titulo">Venta #{{ $venta->id }}</x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="comprobante {{ $papelComprobante === 'carta' ? 'papel-carta' : '' }} bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Encabezado tipo comprobante -->
            <div class="px-6 py-5 border-b border-gray-200 flex flex-wrap items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/icono.png') }}" alt="APZ Motor's" class="w-10 h-10 object-contain">
                    <div>
                        <p class="text-2xl font-extrabold text-gray-900">APZ Motor's</p>
                        <p class="text-xs text-gray-500">Repuestos y accesorios para motocicletas · La Paz, Bolivia</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800">Comprobante de venta #{{ $venta->id }}</p>
                    <p class="text-xs text-gray-500">{{ $venta->fecha->format('d/m/Y H:i') }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $venta->estado === 'Pagado' ? 'bg-green-100 text-green-700' : ($venta->estado === 'Cancelada' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ $venta->estado }}
                    </span>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-200 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Cliente</p>
                    <p class="font-medium text-gray-800">{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</p>
                    @if ($venta->cliente?->telefono)
                        <p class="text-xs text-gray-500">{{ $venta->cliente->telefono }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Atendido por</p>
                    <p class="font-medium text-gray-800">{{ $venta->usuario->name }}</p>
                </div>
            </div>

            @if ($venta->pedido)
                @php $pedido = $venta->pedido; @endphp
                <a href="{{ route('pedidos.show', $pedido) }}"
                   class="block px-6 py-3 bg-gradient-to-r from-orange-50 to-amber-50 border-b border-orange-100 hover:from-orange-100 hover:to-amber-100 transition">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-6 h-2.5 rounded-sm shrink-0" style="background: repeating-linear-gradient(-45deg, #17181c 0 4px, #f54505 4px 8px);"></span>
                            <span class="font-semibold text-gray-800">Origen: pedido en línea #{{ $pedido->id }}</span>
                            <span class="text-xs text-gray-500">del catálogo público — {{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <span class="text-xs font-medium text-orange-700">Ver pedido →</span>
                    </div>
                </a>
            @endif

            <!-- Detalle -->
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-3">Producto</th>
                        <th class="px-6 py-3 text-center">Cant.</th>
                        <th class="px-6 py-3 text-right">Precio</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($venta->detalles as $detalle)
                        <tr>
                            <td class="px-6 py-3 text-gray-800">{{ $detalle->producto->nombre }}</td>
                            <td class="px-6 py-3 text-center text-gray-600">{{ $detalle->cantidad }}</td>
                            <td class="px-6 py-3 text-right text-gray-600">Bs {{ number_format($detalle->precio_unitario, 2) }}</td>
                            <td class="px-6 py-3 text-right font-medium text-gray-900">Bs {{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col items-end gap-1">
                <div class="flex items-center justify-between w-64">
                    <span class="text-sm text-gray-600">Total</span>
                    <span class="text-xl font-bold text-gray-900">Bs {{ number_format($venta->total, 2) }}</span>
                </div>
                @if ($venta->pagos->isNotEmpty())
                    <div class="w-64">
                        @foreach ($venta->pagos as $pago)
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Pago {{ $pago->metodo }} ({{ $pago->estado }})</span>
                                <span>Bs {{ number_format($pago->monto, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 no-print flex items-center justify-between gap-3">
            <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 hover:text-gray-800 font-medium">← Volver a ventas</a>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Papel</span>
                    <select id="papel-comprobante" class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        <option value="termico" @selected($papelComprobante === 'termico')>Térmico 80 mm</option>
                        <option value="carta" @selected($papelComprobante === 'carta')>Carta A4</option>
                    </select>
                </label>
                @can('crear ventas')
                    <button id="btn-imprimir" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">Imprimir comprobante</button>
                @endcan
            </div>
        </div>

        @can('crear devoluciones')
            @php
                $detallesVenta = $venta->loadMissing('detalles.producto')->detalles;
            @endphp
            <div class="mt-6 no-print bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-800 text-lg">Registrar devolución</h2>
                <form method="POST" action="{{ route('devoluciones.store', $venta) }}" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <div>
                        <label for="producto_id" class="block text-sm font-medium text-gray-700">Producto</label>
                        <select id="producto_id" name="producto_id" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            @foreach ($detallesVenta as $detalle)
                                <option value="{{ $detalle->producto_id }}">{{ $detalle->producto->nombre }} (vendidos: {{ $detalle->cantidad }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" min="1" value="1" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="motivo" class="block text-sm font-medium text-gray-700">Motivo *</label>
                        <input type="text" id="motivo" name="motivo" required maxlength="1000" placeholder="Ej: producto defectuoso, talla incorrecta…" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">Enviar solicitud de devolución</button>
                    </div>
                </form>
            </div>
        @endcan

        @if (! in_array($venta->estado, ['Pagado', 'Cancelada']))
            <div class="mt-6 no-print bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="font-semibold text-gray-800 text-lg">Cobrar en línea</h2>
                <p class="text-sm text-gray-500 mt-1">La venta está <strong>Pendiente</strong>. Envía el cobro a través de una pasarela de pago (Fase 2).</p>

                <div class="mt-4 flex flex-wrap gap-3">
                    @foreach (['Stripe', 'PayPal'] as $metodo)
                        <form method="POST" action="{{ route('pagos.iniciar', $venta) }}">
                            @csrf
                            <input type="hidden" name="metodo" value="{{ $metodo }}">
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition
                                {{ $metodo === 'Stripe' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-[#003087] hover:bg-[#00256b] text-white' }}">
                                Cobrar con {{ $metodo }}
                            </button>
                        </form>
                    @endforeach
                </div>

                @php
                    $paymentService = app(\App\Services\PaymentService::class);
                    $simulacion = $paymentService->usaSimulacion('Stripe') || $paymentService->usaSimulacion('PayPal');
                @endphp
                @if ($simulacion)
                    <p class="mt-3 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        ⚠️ Modo <strong>simulación</strong>: no hay claves API configuradas (STRIPE_KEY / PAYPAL_CLIENT_ID en <code>.env</code>).
                        El cobro se registrará como completado para poder probar el flujo.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <script>
        // Preferencia de papel del comprobante: se aplica al instante sobre el
        // comprobante y se persiste por usuario (POST /preferencias/comprobante).
        const selectorPapel = document.getElementById('papel-comprobante');
        const comprobante = document.querySelector('.comprobante');

        function aplicarPapel(valor) {
            if (comprobante) {
                comprobante.classList.toggle('papel-carta', valor === 'carta');
            }
        }

        if (selectorPapel) {
            selectorPapel.addEventListener('change', () => {
                aplicarPapel(selectorPapel.value);
                fetch('{{ route('preferencias.comprobante') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ papel_comprobante: selectorPapel.value }),
                });
            });
        }

        // Auditoría + impresión: primero se registra en Telescope quién imprime
        // (origen=manual) y después se abre el diálogo de impresión.
        const botonImprimir = document.getElementById('btn-imprimir');
        if (botonImprimir) {
            botonImprimir.addEventListener('click', () => {
                fetch('{{ route('ventas.imprimir', $venta) }}?origen=manual', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                }).finally(() => window.print());
            });
        }
    </script>

    @if (request()->query('imprimir') === '1')
        {{-- Impresión automática al confirmar desde el Punto de Venta: espera
             el render del comprobante, registra la auditoría (origen=pos) y
             usa las mismas reglas de impresión aislada (@media print). --}}
        <script data-auto-imprimir>
            window.addEventListener('load', () => {
                setTimeout(() => {
                    fetch('{{ route('ventas.imprimir', $venta) }}?origen=pos', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    }).finally(() => window.print());
                }, 300);
            });
        </script>
    @endif
</x-app-layout>
