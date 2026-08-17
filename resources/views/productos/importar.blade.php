<x-app-layout>
    <x-slot name="titulo">Importar productos (CSV)</x-slot>

    <div class="max-w-4xl space-y-4">
        <div class="flex items-center justify-between">
            <p class="eyebrow !text-[9px] text-gray-500">Inventario · Carga masiva</p>
            <a href="{{ route('productos.importar.historial') }}" class="link text-xs">Ver historial de importaciones →</a>
        </div>

        @if ($resultado)
            @php
                $resumen = $resultado;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="hazard h-1"></div>
                <div class="px-6 py-5">
                    <p class="eyebrow !text-[9px] text-gray-500">Importación ejecutada</p>
                    <h2 class="mt-1 font-display text-lg font-bold text-gray-900">Lote {{ $resumen['lote'] }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Cada movimiento de stock quedó registrado con este lote para poder auditarlo.</p>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="num text-2xl font-extrabold text-gray-900">{{ $resumen['creados'] }}</p>
                            <p class="text-xs text-gray-500">creado(s)</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="num text-2xl font-extrabold text-gray-900">{{ $resumen['actualizados'] }}</p>
                            <p class="text-xs text-gray-500">actualizado(s)</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                            <p class="num text-2xl font-extrabold {{ $resumen['errores'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $resumen['errores'] }}</p>
                            <p class="text-xs text-gray-500">fila(s) con error</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 flex items-center">
                            <a href="{{ route('productos.importar.reporte', $resumen['lote']) }}" class="link text-sm">
                                Descargar reporte de movimientos ↓
                            </a>
                        </div>
                    </div>

                    @if (! empty($resumen['detalle_errores']))
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                            <span class="font-semibold">Filas omitidas:</span>
                            <ul class="mt-1 list-disc list-inside space-y-0.5">
                                @foreach ($resumen['detalle_errores'] as $detalle)
                                    <li>{{ $detalle }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <a href="{{ route('productos.index') }}" class="btn-flame">Ir a productos</a>
                        <form method="POST" action="{{ route('productos.importar.cancelar') }}">
                            @csrf
                            <button type="submit" class="btn-ghost">Nueva importación</button>
                        </form>
                    </div>
                </div>
                <div class="hazard h-1"></div>
            </div>
        @elseif (blank($previa))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="hazard h-1"></div>
                <div class="px-6 py-5">
                    <p class="eyebrow !text-[9px] text-gray-500">Carga masiva desde archivo</p>
                    <h2 class="mt-1 font-display text-lg font-bold text-gray-900">Subir archivo CSV</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        El archivo usa <span class="num">;</span> como separador, codificación UTF-8 y la primera fila con los encabezados.
                        Las categorías que no existan se crean solas; los productos se actualizan si ya hay uno con el mismo nombre.
                    </p>

                    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 overflow-x-auto">
                        <p class="font-mono font-semibold text-gray-800 mb-1">Encabezados esperados</p>
                        <code class="num text-[11px]">nombre;categoria;precio_unitario;stock;umbral_alerta;costo;descripcion;tipo</code>
                        <p class="mt-2">Solo <span class="num">nombre</span>, <span class="num">categoria</span>, <span class="num">precio_unitario</span> y <span class="num">stock</span> son obligatorios. <span class="num">tipo</span> acepta Repuesto o Accesorio.</p>
                    </div>

                    <form method="POST" action="{{ route('productos.importar.preview') }}" enctype="multipart/form-data" class="mt-5 flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file" name="archivo" accept=".csv,.txt,text/csv" required
                            class="block w-full sm:w-auto text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-white file:text-sm file:font-medium hover:file:bg-gray-700">
                        <button type="submit" class="btn-flame">Analizar archivo</button>
                        <a href="{{ route('productos.importar.plantilla') }}" class="link text-xs">Descargar plantilla de ejemplo</a>
                    </form>

                    @error('archivo')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="hazard h-1"></div>
            </div>
        @else
            @php
                $validas = collect($previa)->reject(fn ($fila) => ! empty($fila['errores']));
                $erradas = collect($previa)->filter(fn ($fila) => ! empty($fila['errores']));
            @endphp

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="eyebrow !text-[9px] text-gray-500">Vista previa de la importación</p>
                    <h2 class="mt-1 font-display text-lg font-bold text-gray-900">{{ count($previa) }} fila(s) analizadas</h2>
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 font-mono font-semibold text-green-700">{{ $validas->count() }} válidas</span>
                    <span class="inline-flex items-center rounded-full bg-red-50 px-3 py-1 font-mono font-semibold text-red-700">{{ $erradas->count() }} con error</span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-5 py-3">Fila</th>
                            <th class="px-5 py-3">Producto</th>
                            <th class="px-5 py-3">Categoría</th>
                            <th class="px-5 py-3 text-right">Precio</th>
                            <th class="px-5 py-3 text-center">Stock</th>
                            <th class="px-5 py-3">Acción</th>
                            <th class="px-5 py-3">Resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($previa as $fila)
                            <tr class="{{ ! empty($fila['errores']) ? 'bg-red-50/40' : '' }}">
                                <td class="px-5 py-3 num text-gray-500">{{ $fila['numero'] }}</td>
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $fila['datos']['nombre'] ?: '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $fila['datos']['categoria'] ?: '—' }}</td>
                                <td class="px-5 py-3 text-right num text-gray-900">{{ $fila['datos']['precio_unitario'] ? 'Bs '.$fila['datos']['precio_unitario'] : '—' }}</td>
                                <td class="px-5 py-3 text-center num text-gray-700">{{ $fila['datos']['stock'] ?: '0' }}</td>
                                <td class="px-5 py-3">
                                    @if (($fila['accion'] ?? null) === 'actualizar')
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-amber-700">Actualizar</span>
                                    @elseif (($fila['accion'] ?? null) === 'crear')
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-blue-700">Nuevo</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if (empty($fila['errores']))
                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-[10px] font-mono font-semibold uppercase tracking-wide text-green-700">Lista</span>
                                    @else
                                        <p class="text-xs text-red-700">{{ implode(' · ', $fila['errores']) }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('productos.importar.ejecutar') }}">
                    @csrf
                    <button type="submit" class="btn-flame"
                        onclick="return confirm('¿Importar {{ $validas->count() }} fila(s) válidas? Las filas con error se omitirán.');">
                        Confirmar importación
                    </button>
                </form>
                <form method="POST" action="{{ route('productos.importar.cancelar') }}">
                    @csrf
                    <button type="submit" class="btn-ghost">Descartar y volver</button>
                </form>
                <a href="{{ route('productos.index') }}" class="link text-xs">Ir a productos</a>
            </div>

            @if ($erradas->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                    <span class="font-semibold">Nota:</span> las {{ $erradas->count() }} fila(s) con error se omiten al importar.
                    Corregilas en el archivo y volvé a subirlo si querés incluirlas.
                </div>
            @endif

            <p class="text-xs text-gray-500">
                Las filas marcadas <span class="font-semibold text-amber-700">Actualizar</span> ya tienen un producto con ese nombre:
                se actualizan precio, costo, categoría y stock (sin crear duplicados).
                Las marcadas <span class="font-semibold text-blue-700">Nuevo</span> crean el producto.
            </p>
        @endif
    </div>
</x-app-layout>
