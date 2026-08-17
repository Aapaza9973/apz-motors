@extends('layouts.publico')

@section('titulo', 'Finalizar pedido')

@section('contenido')
    <div class="space-y-6">
        <div>
            <p class="eyebrow !text-[9px] text-gray-500">Último paso</p>
            <h1 class="mt-1 font-display text-2xl font-bold text-gray-900">Tus datos y el pedido</h1>
            <p class="mt-1 text-sm text-gray-500">Dejá tus datos de contacto y el pedido llegará al taller. Te llamamos para coordinar la entrega y el pago.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            <form method="POST" action="{{ route('pedidos.store') }}" class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nombre_cliente" class="block text-sm font-medium text-gray-700">Nombre completo *</label>
                        <input id="nombre_cliente" name="nombre_cliente" value="{{ old('nombre_cliente') }}" required
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('nombre_cliente')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono / WhatsApp *</label>
                        <input id="telefono" name="telefono" value="{{ old('telefono') }}" required placeholder="7XXXXXXX"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('telefono')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Correo <span class="text-gray-400">(opcional)</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección <span class="text-gray-400">(opcional)</span></label>
                        <input id="direccion" name="direccion" value="{{ old('direccion') }}" placeholder="Zona, calle, referencia…"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        @error('direccion')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="nota" class="block text-sm font-medium text-gray-700">Nota para el taller <span class="text-gray-400">(opcional)</span></label>
                    <textarea id="nota" name="nota" rows="2" placeholder="Modelo de tu moto, año, consultas…"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">{{ old('nota') }}</textarea>
                    @error('nota')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <p class="block text-sm font-medium text-gray-700">Método de pago</p>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                        @php($metodos = [
                            'Efectivo' => ['título' => 'Pagar al recibir', 'detalle' => 'Abonás en el taller cuando retirás.', 'sim' => false],
                            'Stripe' => ['título' => 'Tarjeta (Stripe)', 'detalle' => 'Pagás ahora con tarjeta.', 'sim' => $pagoSimulacion['Stripe']],
                            'PayPal' => ['título' => 'PayPal', 'detalle' => 'Pagás ahora con tu cuenta PayPal.', 'sim' => $pagoSimulacion['PayPal']],
                        ])
                        @foreach ($metodos as $valor => $metodo)
                            <label class="relative flex items-start gap-2.5 rounded-xl border p-3.5 cursor-pointer transition has-[:checked]:border-orange-500 has-[:checked]:ring-1 has-[:checked]:ring-orange-500 {{ old('metodo_pago', 'Efectivo') === $valor ? 'border-orange-500 ring-1 ring-orange-500' : 'border-gray-200 hover:border-gray-300' }}">
                                <input type="radio" name="metodo_pago" value="{{ $valor }}" @checked(old('metodo_pago', 'Efectivo') === $valor)
                                    class="mt-0.5 accent-orange-600">
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900">{{ $metodo['título'] }}</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">{{ $metodo['detalle'] }}
                                        @if ($metodo['sim'])
                                            <span class="inline-flex items-center gap-1 text-[10px] font-mono uppercase tracking-wide text-amber-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>modo simulación
                                            </span>
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('metodo_pago')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-flame">Confirmar pedido</button>
            </form>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="hazard h-1"></div>
                <div class="px-6 py-5">
                    <p class="eyebrow !text-[9px] text-gray-500">Resumen</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($items as $linea)
                            <li class="flex justify-between gap-3">
                                <span class="text-gray-600">{{ $linea['cantidad'] }} × {{ $linea['producto']->nombre }}</span>
                                <span class="num text-gray-900 whitespace-nowrap">Bs {{ number_format($linea['producto']->precio_unitario * $linea['cantidad'], 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-sm text-gray-600 font-medium">Total</span>
                        <span class="num text-xl font-bold text-gray-900">Bs {{ number_format($total, 2) }}</span>
                    </div>
                    <a href="{{ route('carrito.ver') }}" class="link text-xs block mt-4">Volver al carrito</a>
                </div>
                <div class="hazard h-1"></div>
            </div>
        </div>
    </div>
@endsection
