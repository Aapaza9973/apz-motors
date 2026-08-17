@extends('layouts.publico')

@section('titulo', 'Consultar mi pedido')

@section('contenido')
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="hazard h-1"></div>
            <div class="px-8 py-8">
                <p class="eyebrow !text-[9px] text-gray-500">Seguimiento sin cuenta</p>
                <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Consultar mi pedido</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Ingresá el número de tu pedido y el teléfono que dejaste al hacerlo. Así ves el estado actual: si sigue en el taller, si ya fue confirmado o cancelado.
                </p>

                <form method="POST" action="{{ route('pedidos.consultar-enviar') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="pedido_id" class="block text-sm font-medium text-gray-700">Número de pedido *</label>
                        <input id="pedido_id" name="pedido_id" type="number" value="{{ old('pedido_id') }}" required placeholder="Ej. 12"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('pedido_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono del pedido *</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" required placeholder="7XXXXXXX"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('telefono')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @error('pedido')
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
                    @enderror

                    <button type="submit" class="btn-flame">Ver estado del pedido</button>
                </form>
            </div>
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-2 text-xs text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-4 h-4 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                ¿No recordás el número? Te lo confirmamos por WhatsApp al hacer el pedido.
            </div>
            <div class="hazard h-1"></div>
        </div>
    </div>
@endsection
