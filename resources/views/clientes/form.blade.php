@php
    $cliente ??= null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre completo *</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $cliente?->nombre) }}" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
        <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $cliente?->telefono) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
        <input type="email" id="email" name="email" value="{{ old('email', $cliente?->email) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
        <input type="text" id="direccion" name="direccion" value="{{ old('direccion', $cliente?->direccion) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>
</div>
