<x-app-layout>
    <x-slot name="titulo">Cierre de caja</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Historial de cierres por vendedor y día.</p>
            <div class="flex items-center gap-3">
                <form method="GET" action="{{ route('caja.index') }}">
                    <input type="date" name="fecha" value="{{ request('fecha') }}"
                        onchange="this.form.submit()"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                </form>
                @can('crear cierres de caja')
                    <a href="{{ route('caja.create') }}" class="btn-flame">Cerrar caja de hoy</a>
                @endcan
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Vendedor</th>
                        <th class="px-5 py-3 text-center">Ventas</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Efectivo</th>
                        <th class="px-5 py-3 text-right">Tarjeta</th>
                        <th class="px-5 py-3 text-right">Transferencia</th>
                        <th class="px-5 py-3 text-right">En línea</th>
                        <th class="px-5 py-3">Observación</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cierres as $cierre)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-gray-900 whitespace-nowrap">{{ $cierre->fecha_cierre->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-gray-800">{{ $cierre->usuario->name }}</td>
                            <td class="px-5 py-3 text-center font-mono text-gray-700">{{ $cierre->cantidad_ventas }}</td>
                            <td class="px-5 py-3 text-right font-mono font-semibold text-gray-900">Bs {{ number_format($cierre->total_ventas, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono text-gray-700">Bs {{ number_format($cierre->total_efectivo, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono text-gray-700">Bs {{ number_format($cierre->total_tarjeta, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono text-gray-700">Bs {{ number_format($cierre->total_transferencia, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono text-gray-700">Bs {{ number_format($cierre->total_stripe + $cierre->total_paypal, 2) }}</td>
                            <td class="px-5 py-3 text-gray-500 max-w-[180px] truncate">{{ $cierre->observacion ?: '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('caja.pdf', $cierre) }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-orange-600 transition" title="Exportar cierre a PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                    PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-4">
                                <x-empty-state
                                    titulo="Sin cierres registrados"
                                    mensaje="Cuando un vendedor cierre su turno, el arqueo del día aparecerá acá con el detalle por método de pago."
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $cierres->links() }}</div>
    </div>
</x-app-layout>
