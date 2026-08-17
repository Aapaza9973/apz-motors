@php
    $producto ??= null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $producto?->nombre) }}" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoría *</label>
        <select id="categoria_id" name="categoria_id" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto?->categoria_id) == $categoria->id)>
                    {{ $categoria->nombre }} ({{ $categoria->tipo }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">{{ old('descripcion', $producto?->descripcion) }}</textarea>
    </div>

    <div>
        <label for="precio_unitario" class="block text-sm font-medium text-gray-700">Precio unitario (Bs) *</label>
        <input type="number" step="0.01" min="0.01" id="precio_unitario" name="precio_unitario"
            value="{{ old('precio_unitario', $producto?->precio_unitario) }}" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="costo" class="block text-sm font-medium text-gray-700">Costo (Bs) — opcional</label>
        <input type="number" step="0.01" min="0" id="costo" name="costo" value="{{ old('costo', $producto?->costo) }}"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
    </div>

    <div>
        <label for="stock" class="block text-sm font-medium text-gray-700">Stock actual *</label>
        <input type="number" min="0" id="stock" name="stock" value="{{ old('stock', $producto?->stock ?? 0) }}" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
        @if ($producto)
            <p class="mt-1 text-xs text-gray-400">La diferencia con el stock actual se registra como ajuste.</p>
        @endif
    </div>

    <div>
        <label for="umbral_alerta" class="block text-sm font-medium text-gray-700">Umbral de alerta *</label>
        <input type="number" min="0" id="umbral_alerta" name="umbral_alerta"
            value="{{ old('umbral_alerta', $producto?->umbral_alerta ?? 5) }}" required
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
        <p class="mt-1 text-xs text-gray-400">Se genera una alerta cuando el stock queda igual o por debajo de este valor.</p>
    </div>
</div>
