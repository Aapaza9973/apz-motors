<x-app-layout>
    <x-slot name="titulo">Clientes</x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('clientes.index') }}" class="flex items-center gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, correo o teléfono…"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm w-72">
                <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">Buscar</button>
            </form>

            @can('crear clientes')
                <a href="{{ route('clientes.create') }}" class="bg-orange-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-orange-700 transition">+ Nuevo cliente</a>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Teléfono</th>
                        <th class="px-5 py-3">Correo</th>
                        <th class="px-5 py-3 text-center">Compras</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clientes as $cliente)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('clientes.show', $cliente) }}" class="font-medium text-gray-900 hover:text-orange-600">{{ $cliente->nombre }}</a>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $cliente->telefono ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $cliente->email ?? '—' }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">{{ $cliente->ventas_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('clientes.show', $cliente) }}" class="text-orange-600 hover:text-orange-700 font-medium">Historial</a>
                                @can('editar clientes')
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="text-gray-600 hover:text-gray-800 font-medium ml-2">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-4">
                                <x-empty-state
                                    :titulo="request('q') ? 'Sin clientes para esa búsqueda' : 'Sin clientes todavía'"
                                    :mensaje="request('q')
                                        ? 'No encontramos clientes con ese nombre, correo o teléfono. Revisá la ortografía o probá con otro dato.'
                                        : 'Cuando registres tu primer cliente, aparecerá acá con su historial de compras.'"
                                    icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>'
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $clientes->links() }}</div>
    </div>
</x-app-layout>
