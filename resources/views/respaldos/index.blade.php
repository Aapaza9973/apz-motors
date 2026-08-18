<x-app-layout>
    <x-slot name="titulo">Respaldos de base de datos</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Historial de ejecuciones del respaldo diario (Documento Maestro §6) — 7 días de retención.</p>
            <form method="GET" action="{{ route('respaldos.index') }}">
                <select name="estado" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                    <option value="">Todos los resultados</option>
                    <option value="exitoso" @selected($estadoActivo === 'exitoso')>Exitosos</option>
                    <option value="fallido" @selected($estadoActivo === 'fallido')>Fallidos</option>
                </select>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Ejecutado</th>
                        <th class="px-5 py-3">Resultado</th>
                        <th class="px-5 py-3">Archivo</th>
                        <th class="px-5 py-3 text-right">Tamaño</th>
                        <th class="px-5 py-3">Detalle</th>
                        <th class="px-5 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($respaldos as $respaldo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">{{ $respaldo->ejecutado_en->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-3">
                                @if ($respaldo->fueExitoso())
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-green-700">Exitoso</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-red-700">Fallido</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs text-gray-800">{{ $respaldo->archivo ?: '—' }}</td>
                            <td class="px-5 py-3 text-right num text-gray-700">{{ $respaldo->tamano_bytes !== null ? number_format($respaldo->tamano_bytes / 1024, 1).' KB' : '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 max-w-[280px] truncate" title="{{ $respaldo->mensaje }}">{{ $respaldo->mensaje ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($respaldo->archivo)
                                    <a href="{{ route('respaldos.descargar', $respaldo) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white px-3 py-1.5 text-xs font-medium transition"
                                        title="Descargar {{ basename($respaldo->archivo) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        Descargar
                                    </a>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin ejecuciones registradas"
                                    mensaje="Cuando el respaldo diario corra (o lo ejecutes a mano con php artisan backup:database), cada resultado quedará registrado acá."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $respaldos->links() }}</div>
    </div>
</x-app-layout>
