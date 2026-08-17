<x-app-layout>
    <x-slot name="titulo">Cerrar caja de hoy</x-slot>

    <div class="max-w-2xl space-y-4">
        <p class="text-sm text-gray-500">Revisa el resumen de tu turno antes de confirmar el cierre. No podrás cerrar dos veces el mismo día.</p>

        @if ($resumen['ya_cerrado'])
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-sm text-amber-800">
                <p class="font-semibold">La caja de hoy ya fue cerrada.</p>
                <p class="mt-1">Si necesitas revisar el registro, consulta el historial de cierres.</p>
            </div>
            <a href="{{ route('caja.index') }}" class="btn-secondary">Ver historial</a>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="hazard h-1"></div>
                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="eyebrow !text-[9px] text-gray-500">Resumen del turno</p>
                    <p class="font-display text-xl font-bold text-gray-900 mt-1">Bs {{ number_format($resumen['total_ventas'], 2) }}</p>
                    <p class="text-xs text-gray-500">{{ $resumen['cantidad_ventas'] }} venta(s) registrada(s) hoy · {{ now()->format('d/m/Y') }}</p>
                </div>
                <dl class="divide-y divide-gray-100 text-sm">
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Efectivo</dt>
                        <dd class="font-mono text-gray-900">Bs {{ number_format($resumen['total_efectivo'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Tarjeta</dt>
                        <dd class="font-mono text-gray-900">Bs {{ number_format($resumen['total_tarjeta'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Transferencia</dt>
                        <dd class="font-mono text-gray-900">Bs {{ number_format($resumen['total_transferencia'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Stripe / PayPal</dt>
                        <dd class="font-mono text-gray-900">Bs {{ number_format($resumen['total_stripe'] + $resumen['total_paypal'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between px-6 py-2.5">
                        <dt class="text-gray-500">Otros</dt>
                        <dd class="font-mono text-gray-900">Bs {{ number_format($resumen['total_otros'], 2) }}</dd>
                    </div>
                </dl>
            </div>

            <form method="POST" action="{{ route('caja.store') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                @csrf
                <div>
                    <label for="observacion" class="block text-sm font-medium text-gray-700">Observación <span class="text-gray-400">(opcional)</span></label>
                    <textarea id="observacion" name="observacion" rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm"
                        placeholder="Notas del turno, arqueo, incidencias…"></textarea>
                    @error('observacion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-flame"
                    onclick="return confirm('¿Confirmar el cierre de caja con {{ $resumen['cantidad_ventas'] }} venta(s) por Bs {{ number_format($resumen['total_ventas'], 2) }}?');">
                    Confirmar cierre de caja
                </button>
                <a href="{{ route('ventas.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-3">Cancelar</a>
            </form>
        @endif
    </div>
</x-app-layout>
