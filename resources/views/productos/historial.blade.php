<x-app-layout>
    <x-slot name="titulo">Historial de importaciones</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">Todas las cargas masivas de productos (CSV), con su lote y el reporte de movimientos de cada una.</p>
            <a href="{{ route('productos.importar') }}" class="btn-flame text-sm">Nueva importación</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Lote</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3 text-center">Filas</th>
                        <th class="px-5 py-3 text-center">Creados</th>
                        <th class="px-5 py-3 text-center">Actualizados</th>
                        <th class="px-5 py-3 text-center">Errores</th>
                        <th class="px-5 py-3">Registrado por</th>
                        <th class="px-5 py-3 text-right">Reporte</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($importaciones as $importacion)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono font-semibold text-gray-900">{{ $importacion->lote }}</td>
                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $importacion->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-center num text-gray-700">{{ $importacion->total_filas }}</td>
                            <td class="px-5 py-3 text-center num text-blue-700">{{ $importacion->creados }}</td>
                            <td class="px-5 py-3 text-center num text-amber-700">{{ $importacion->actualizados }}</td>
                            <td class="px-5 py-3 text-center num {{ $importacion->errores > 0 ? 'font-semibold text-red-600' : 'text-gray-500' }}">{{ $importacion->errores }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $importacion->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('productos.importar.reporte', $importacion->lote) }}" class="link text-xs">Descargar reporte ↓</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Todavía no hay importaciones"
                                    mensaje="Cuando hagas una carga masiva desde CSV, el lote y su resumen quedan registrados acá para que puedas auditarlos."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>'
                                    :accion="'<a href=&quot;' . route('productos.importar') . '&quot; class=&quot;btn-ghost text-xs&quot;>Hacer la primera importación</a>'"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $importaciones->links() }}</div>
    </div>
</x-app-layout>
