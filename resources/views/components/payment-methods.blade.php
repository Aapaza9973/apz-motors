@props(['methods' => [], 'amount' => null])

{{--
    Pasarela de pago — tarjeta profesional con los métodos disponibles.
    Slots: title, subtitle, currencyNote. Los radios seleccionan el método;
    el formulario que envía el cobro lo define la vista anfitriona.
--}}

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    <div class="hazard h-1"></div>

    <div class="p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="eyebrow">Pasarela de pago</p>
                <h3 class="mt-1 font-display text-lg font-bold text-gray-900">{{ $title ?? 'Elegí cómo pagar' }}</h3>
            </div>
            @if ($amount !== null)
                <span class="num shrink-0 rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-semibold text-gray-900">
                    Bs {{ number_format((float) $amount, 2) }}
                </span>
            @endif
        </div>

        <p class="text-sm text-gray-500">{{ $subtitle ?? 'Elegí el método que prefieras. El pago queda registrado para confirmar la venta.' }}</p>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($methods as $method)
                <label class="flex flex-col items-start gap-2 rounded-xl border p-3.5 cursor-pointer transition
                    has-[:checked]:border-orange-500 has-[:checked]:ring-1 has-[:checked]:ring-orange-500
                    border-gray-200 hover:border-gray-300">
                    <input type="radio" name="metodo_pago" value="{{ $method['id'] }}"
                        @checked(($selected ?? null) === $method['id'])
                        class="mt-0.5 h-4 w-4 accent-orange-600">

                    <span class="flex items-center justify-center h-9 w-9 rounded-lg {{ $method['colorBg'] ?? 'bg-gray-50' }}">
                        {!! $method['svg'] !!}
                    </span>

                    <span class="text-sm font-semibold leading-snug text-gray-900">{{ $method['title'] }}</span>

                    @if (isset($method['description']))
                        <span class="text-xs leading-relaxed text-gray-500">{{ $method['description'] }}</span>
                    @endif

                    @if ($method['simulacion'])
                        <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-mono uppercase tracking-wide text-amber-700">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>simulación
                        </span>
                    @endif
                </label>
            @endforeach
        </div>

        @isset($currencyNote)
            <p class="text-xs text-gray-400">{{ $currencyNote }}</p>
        @endisset
    </div>

    <div class="hazard h-1"></div>
</div>
