@props([
    'titulo' => 'Sin resultados',
    'mensaje' => 'No hay registros para mostrar.',
    'icono' => null,
    'accion' => null,
])

<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    @if ($icono)
        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 mb-4">
            {!! $icono !!}
        </div>
    @else
        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="w-6 h-6 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
    @endif

    <p class="font-display text-base font-bold text-gray-900">{{ $titulo }}</p>
    <p class="mt-1 text-sm text-gray-500 max-w-sm">{{ $mensaje }}</p>

    @if ($accion)
        <div class="mt-5">
            {{ $accion }}
        </div>
    @endif
</div>
