<x-app-layout>
    <x-slot name="titulo">Encuesta del catálogo</x-slot>

    <div class="space-y-4">
        <div>
            <p class="eyebrow !text-[9px] text-gray-500">Voz del cliente</p>
            <h1 class="mt-1 font-display text-xl font-bold text-gray-900">Encuesta de mejora del catálogo</h1>
            <p class="mt-1 text-sm text-gray-500">Respuestas anónimas de los clientes del catálogo público (sin datos personales registrados).</p>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card kpi">
                <p class="kpi-value">{{ number_format($resumen['total']) }}</p>
                <p class="kpi-label">Respuestas totales</p>
            </div>
            <div class="card kpi">
                <p class="kpi-value">{{ $resumen['promedio'] !== null ? number_format($resumen['promedio'], 2).' / 5' : '—' }}</p>
                <p class="kpi-label">Satisfacción promedio</p>
            </div>
            <div class="card kpi">
                <p class="kpi-value">{{ number_format($resumen['satisfechos']) }} <span class="text-sm font-normal text-gray-500">/ {{ number_format($resumen['insatisfechos']) }}</span></p>
                <p class="kpi-label">Satisfechos (4-5) / Insatisfechos (1-2)</p>
            </div>
            <div class="card kpi">
                <p class="kpi-value">{{ number_format($resumen['con_comentario']) }}</p>
                <p class="kpi-label">Con comentario escrito</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Facilidad para encontrar -->
            <div class="card px-5 py-4">
                <p class="eyebrow !text-[9px] text-gray-500">¿Te fue fácil encontrar lo que buscabas?</p>
                <div class="mt-3 space-y-2">
                    @foreach (['facil' => 'Me fue fácil', 'normal' => 'Me costó un poco', 'dificil' => 'No encontré lo que buscaba'] as $clave => $etiqueta)
                        @php($cantidad = $resumen['por_facilidad']->get($clave, 0))
                        @php($porcentaje = $resumen['total'] > 0 ? round($cantidad * 100 / $resumen['total']) : 0)
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-700">{{ $etiqueta }}</span>
                                <span class="num text-gray-500">{{ $cantidad }} · {{ $porcentaje }}%</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $clave === 'facil' ? 'bg-green-600' : ($clave === 'normal' ? 'bg-yellow-500' : 'bg-red-600') }}"
                                    style="width: {{ $porcentaje }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Qué falta (agrupado) -->
            <div class="card px-5 py-4">
                <p class="eyebrow !text-[9px] text-gray-500">Repuestos que faltan (más mencionados)</p>
                @if ($resumen['sugerencias_falta']->isEmpty())
                    <p class="mt-3 text-sm text-gray-400">Todavía no hay sugerencias de productos faltantes.</p>
                @else
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($resumen['sugerencias_falta'] as $sugerencia)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-gray-700 truncate">{{ $sugerencia->falta }}</span>
                                <span class="badge badge-flame num shrink-0">{{ $sugerencia->total }}×</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Respuestas detalladas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Calificación</th>
                        <th class="px-5 py-3">Encontró lo que buscaba</th>
                        <th class="px-5 py-3">Falta</th>
                        <th class="px-5 py-3">Comentario</th>
                        <th class="px-5 py-3">Origen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($respuestas as $respuesta)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">{{ $respuesta->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="badge num {{ $respuesta->satisfaccion >= 4 ? 'badge-ok' : ($respuesta->satisfaccion === 3 ? 'badge-warn' : 'badge-alarm') }}">
                                    {{ $respuesta->satisfaccion }}/5 · {{ $respuesta->etiquetaSatisfaccion() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $respuesta->etiquetaFacilidad() }}</td>
                            <td class="px-5 py-3 text-gray-600 max-w-[220px]">{{ $respuesta->falta ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 max-w-[260px]">{{ $respuesta->comentario ?: '—' }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-400 uppercase">{{ $respuesta->origen }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin respuestas todavía"
                                    mensaje="Cuando los clientes respondan la encuesta exprés del catálogo público, verás sus opiniones acá."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $respuestas->links() }}</div>
    </div>
</x-app-layout>
