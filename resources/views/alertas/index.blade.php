<x-app-layout>
    <x-slot name="titulo">Alertas de stock</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">
                {{ $pendientes }} alerta(s) pendiente(s) de revisión.
            </p>
            @if ($pendientes > 0)
                <form method="POST" action="{{ route('alertas.marcar-todas-leidas') }}">
                    @csrf
                    <button type="submit" class="bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-700 transition">Marcar todas como leídas</button>
                </form>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="divide-y divide-gray-100">
                @forelse ($alertas as $alerta)
                    <div class="px-5 py-4 flex items-center justify-between gap-4 {{ $alerta->leida ? 'opacity-60' : '' }}">
                        <div class="flex items-start gap-3">
                            @if (! $alerta->leida)
                                <span class="mt-1 w-2.5 h-2.5 rounded-full bg-orange-500 flex-shrink-0"></span>
                            @endif
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $alerta->producto->nombre }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $alerta->mensaje }}</p>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $alerta->producto->categoria->nombre }} · {{ $alerta->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        @if (! $alerta->leida)
                            <form method="POST" action="{{ route('alertas.marcar-leida', $alerta) }}">
                                @csrf
                                <button type="submit" class="text-xs text-orange-600 hover:text-orange-700 font-medium">Marcar leída</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <x-empty-state
                        titulo="Stock en calma"
                        mensaje="No hay alertas activas. Cuando un repuesto baje de su umbral, la alerta aparecerá acá para que lo repongas a tiempo."
                        icono='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>'
                    />
                @endforelse
            </div>
        </div>

        <div>{{ $alertas->links() }}</div>
    </div>
</x-app-layout>
