@props([
    'origen' => 'catalogo',
])

<section class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" id="encuesta">
    <div class="hazard h-1"></div>
    <div class="px-6 py-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="eyebrow !text-[9px] text-gray-500">Tu opinión nos hace mejores</p>
                <h2 class="mt-1 font-display text-lg font-bold text-gray-900">Encuesta exprés del catálogo</h2>
                <p class="mt-1 text-sm text-gray-500">3 preguntas, sin registro y 100% anónima — nos ayuda a tener los repuestos que buscás.</p>
            </div>
            <span class="badge badge-muted shrink-0">Anónima · ~30 seg</span>
        </div>

        @if (session('encuesta_respondida'))
            <div class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                ¡Gracias por responder! Si querés contarnos más, volvé a escribirnos en tu próxima visita.
            </div>
        @else
            <form method="POST" action="{{ route('encuesta.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="origen" value="{{ $origen }}">

                <!-- 1. Satisfacción -->
                <div>
                    <p class="text-sm font-medium text-gray-700">1. ¿Cómo calificarías el catálogo?</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ([1 => '1 · Muy malo', 2 => '2 · Regular', 3 => '3 · Está bien', 4 => '4 · Me gusta', 5 => '5 · Excelente'] as $valor => $etiqueta)
                            <label class="cursor-pointer">
                                <input type="radio" name="satisfaccion" value="{{ $valor }}" required
                                    class="peer sr-only" @checked(old('satisfaccion') == $valor)>
                                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold border border-gray-200 text-gray-600 transition peer-checked:bg-gray-900 peer-checked:text-white peer-checked:border-gray-900 hover:border-gray-400">
                                    {{ $etiqueta }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('satisfaccion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- 2. Facilidad para encontrar -->
                <div>
                    <p class="text-sm font-medium text-gray-700">2. ¿Te fue fácil encontrar lo que buscabas?</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['facil' => 'Me fue fácil', 'normal' => 'Me costó un poco', 'dificil' => 'No encontré lo que buscaba'] as $valor => $etiqueta)
                            <label class="cursor-pointer">
                                <input type="radio" name="facilidad_encontrar" value="{{ $valor }}" required
                                    class="peer sr-only" @checked(old('facilidad_encontrar') === $valor)>
                                <span class="inline-block px-3 py-1.5 rounded-full text-xs font-semibold border border-gray-200 text-gray-600 transition peer-checked:bg-gray-900 peer-checked:text-white peer-checked:border-gray-900 hover:border-gray-400">
                                    {{ $etiqueta }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('facilidad_encontrar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- 3. Opcional -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="encuesta-falta" class="block text-sm font-medium text-gray-700">
                            ¿Qué repuestos faltan? <span class="text-gray-400">(opcional)</span>
                        </label>
                        <input id="encuesta-falta" type="text" name="falta" value="{{ old('falta') }}" maxlength="500"
                            placeholder="Ej: pastillas para Honda Titan 150…"
                            class="input mt-1 block w-full px-3 py-2">
                        @error('falta')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="encuesta-comentario" class="block text-sm font-medium text-gray-700">
                            Sugerencia libre <span class="text-gray-400">(opcional)</span>
                        </label>
                        <input id="encuesta-comentario" type="text" name="comentario" value="{{ old('comentario') }}" maxlength="1000"
                            placeholder="¿Qué cambiarías del catálogo?"
                            class="input mt-1 block w-full px-3 py-2">
                        @error('comentario')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-[11px] text-gray-400">No pedimos nombre, correo ni ningún dato personal.</p>
                    <button type="submit" class="btn-flame px-5 py-2.5 text-sm">Enviar mi opinión</button>
                </div>
            </form>
        @endif
    </div>
    <div class="hazard h-1"></div>
</section>
