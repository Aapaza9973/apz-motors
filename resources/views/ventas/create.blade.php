<x-app-layout>
    <x-slot name="titulo">Punto de venta</x-slot>

    <form method="POST" action="{{ route('ventas.store') }}" id="form-venta">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Selección de productos -->
            <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-800">1. Agregar productos</h2>
                </div>
                <div class="p-4">
                    <input type="text" id="buscador" placeholder="Buscar producto…" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm mb-3">
                    <div class="max-h-[28rem] overflow-y-auto divide-y divide-gray-100">
                        @forelse ($productos as $producto)
                            <button type="button" class="agregar-producto w-full text-left px-3 py-2.5 flex items-center justify-between hover:bg-orange-50 transition"
                                data-id="{{ $producto->id }}"
                                data-nombre="{{ $producto->nombre }}"
                                data-precio="{{ $producto->precio_unitario }}"
                                data-stock="{{ $producto->stock }}">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $producto->nombre }}</p>
                                    <p class="text-xs text-gray-500">{{ $producto->categoria->nombre }} · Stock: {{ $producto->stock }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-orange-600">Bs {{ number_format($producto->precio_unitario, 2) }}</p>
                                    <p class="text-xs text-gray-400">Tocar para agregar</p>
                                </div>
                            </button>
                        @empty
                            <x-empty-state
                                titulo="Sin repuestos disponibles"
                                mensaje="No hay productos con stock para vender en este momento. Cargá inventario desde el módulo de productos."
                            />
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Carrito -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="font-semibold text-gray-800">2. Carrito</h2>
                        <button type="button" id="vaciar-carrito" class="text-xs text-red-600 hover:text-red-700 font-medium">Vaciar</button>
                    </div>

                    <div id="carrito-vacio" class="px-5 py-10 text-center text-sm text-gray-500">
                        Agrega productos para comenzar la venta.
                    </div>

                    <div id="items-carrito" class="divide-y divide-gray-100"></div>

                    <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total</span>
                            <span id="total-venta" class="text-2xl font-bold text-gray-900">Bs 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-4">
                    <h2 class="font-semibold text-gray-800">3. Cliente y pago</h2>

                    <div>
                        <label for="cliente_id" class="block text-sm font-medium text-gray-700">Cliente</label>
                        <select id="cliente_id" name="cliente_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            <option value="">Consumidor final</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" data-puntos="{{ max(0, (int) ($cliente->puntos_total ?? 0)) }}">{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">¿Cliente nuevo? Regístralo en <a href="{{ route('clientes.create') }}" class="text-orange-600 hover:underline">Clientes</a>.</p>
                    </div>

                    <div id="bloque-puntos" class="hidden rounded-lg border border-orange-200 bg-orange-50/60 p-3">
                        <label for="puntos_canje" class="block text-sm font-medium text-gray-700">Canjear puntos de fidelización</label>
                        <div class="mt-1 flex items-center gap-2">
                            <input type="number" min="0" step="1" id="puntos_canje" name="puntos_canje" value="0"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                            <button type="button" id="btn-max-puntos" class="shrink-0 text-xs font-medium bg-white border border-orange-300 text-orange-700 px-2.5 py-2 rounded-lg hover:bg-orange-100 transition">Canjear máximo</button>
                        </div>
                        <p id="puntos-info" class="mt-1.5 text-xs text-gray-600"></p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="cobrar-ahora" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" checked>
                            Cobrar en efectivo ahora
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="imprimir_comprobante" name="imprimir_comprobante" value="1"
                                class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" @checked($imprimirComprobante)>
                            Imprimir comprobante al confirmar
                        </label>
                        <p class="mt-1 text-xs text-gray-400">Se abre el comprobante e imprime automáticamente. La preferencia queda guardada para tus próximas ventas.</p>
                    </div>

                    <div id="bloque-pago" class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="pago_monto" class="block text-sm font-medium text-gray-700">Monto recibido (Bs)</label>
                            <input type="number" step="0.01" min="0" id="pago_monto" name="pago_monto" value="0"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                        </div>
                        <div>
                            <label for="pago_metodo" class="block text-sm font-medium text-gray-700">Método</label>
                            <select id="pago_metodo" name="pago_metodo" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm">
                                @foreach (['Efectivo', 'Tarjeta', 'Transferencia', 'Otro'] as $metodo)
                                    <option value="{{ $metodo }}">{{ $metodo }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" id="btn-confirmar" class="w-full bg-orange-600 text-white font-semibold py-3 rounded-lg hover:bg-orange-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Confirmar venta
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        const carrito = new Map();
        const PUNTOS_VALOR_BS = {{ json_encode(config('puntos.puntos_por_bs_descuento')) }};
        let subtotal = 0;

        function fmtBs(valor) {
            return 'Bs ' + valor.toFixed(2);
        }

        function saldoPuntos() {
            const select = document.getElementById('cliente_id');
            const opcion = select.selectedOptions[0];
            return opcion && opcion.dataset.puntos !== undefined ? parseInt(opcion.dataset.puntos, 10) : 0;
        }

        function descuentoPuntos() {
            const puntos = Math.max(0, parseInt(document.getElementById('puntos_canje').value || '0', 10));
            return puntos / PUNTOS_VALOR_BS;
        }

        function recalcularTotales() {
            const descuento = descuentoPuntos();
            const total = Math.max(0, subtotal - descuento);
            document.getElementById('total-venta').textContent = fmtBs(total);
            if (document.getElementById('cobrar-ahora').checked) {
                document.getElementById('pago_monto').value = total.toFixed(2);
            }
            if (descuento > 0) {
                document.getElementById('puntos-info').textContent =
                    `Descuento aplicado: −${fmtBs(Math.min(descuento, subtotal))}. El cliente gana puntos sobre el total pagado.`;
            }
        }

        function actualizarBloquePuntos() {
            const bloque = document.getElementById('bloque-puntos');
            const saldo = saldoPuntos();
            bloque.classList.toggle('hidden', saldo <= 0);
            document.getElementById('puntos-info').textContent = saldo > 0
                ? `Saldo disponible: ${saldo} puntos (Bs ${fmtBs(saldo / PUNTOS_VALOR_BS)})`
                : '';
            if (saldo <= 0) {
                document.getElementById('puntos_canje').value = 0;
            }
            recalcularTotales();
        }

        document.getElementById('cliente_id').addEventListener('change', actualizarBloquePuntos);

        document.getElementById('puntos_canje').addEventListener('input', function () {
            const saldo = saldoPuntos();
            const valor = parseInt(this.value || '0', 10);
            if (valor > saldo) { this.value = saldo; }
            recalcularTotales();
        });

        document.getElementById('btn-max-puntos').addEventListener('click', function () {
            document.getElementById('puntos_canje').value = saldoPuntos();
            recalcularTotales();
        });

        document.querySelectorAll('.agregar-producto').forEach((boton) => {
            boton.addEventListener('click', () => {
                const id = boton.dataset.id;
                const item = carrito.get(id) || {
                    id: id,
                    nombre: boton.dataset.nombre,
                    precio: parseFloat(boton.dataset.precio),
                    stock: parseInt(boton.dataset.stock, 10),
                    cantidad: 0,
                };
                if (item.cantidad < item.stock) {
                    item.cantidad += 1;
                    carrito.set(id, item);
                } else {
                    alert(`Stock máximo disponible para "${item.nombre}": ${item.stock}.`);
                }
                renderCarrito();
            });
        });

        document.getElementById('vaciar-carrito').addEventListener('click', () => {
            carrito.clear();
            renderCarrito();
        });

        document.getElementById('cobrar-ahora').addEventListener('change', (e) => {
            document.getElementById('bloque-pago').style.display = e.target.checked ? 'grid' : 'none';
            document.getElementById('pago_monto').disabled = !e.target.checked;
            document.getElementById('pago_metodo').disabled = !e.target.checked;
        });

        // Persiste la preferencia de autoimpresión por vendedor al cambiarla.
        document.getElementById('imprimir_comprobante').addEventListener('change', (e) => {
            fetch('{{ route('preferencias.comprobante') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ imprimir_pos: e.target.checked }),
            });
        });

        function renderCarrito() {
            const contenedor = document.getElementById('items-carrito');
            const vacio = document.getElementById('carrito-vacio');
            const total = [...carrito.values()].reduce((suma, i) => suma + i.precio * i.cantidad, 0);

            contenedor.innerHTML = '';
            vacio.style.display = carrito.size === 0 ? 'block' : 'none';

            carrito.forEach((item) => {
                const fila = document.createElement('div');
                fila.className = 'px-5 py-3 flex items-center justify-between gap-3';
                fila.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">${item.nombre}</p>
                        <p class="text-xs text-gray-500">${fmtBs(item.precio)} c/u</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="cambiar-cantidad w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold" data-id="${item.id}" data-delta="-1">−</button>
                        <span class="w-8 text-center text-sm font-semibold">${item.cantidad}</span>
                        <button type="button" class="cambiar-cantidad w-7 h-7 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold" data-id="${item.id}" data-delta="1">+</button>
                    </div>
                    <p class="text-sm font-bold text-gray-900 w-20 text-right">${fmtBs(item.precio * item.cantidad)}</p>
                `;
                contenedor.appendChild(fila);
            });

            document.getElementById('total-venta').textContent = fmtBs(total);
            document.getElementById('pago_monto').value = total.toFixed(2);
            document.getElementById('btn-confirmar').disabled = carrito.size === 0;
            document.querySelectorAll('.cambiar-cantidad').forEach((boton) => {
                boton.addEventListener('click', () => {
                    const item = carrito.get(boton.dataset.id);
                    const delta = parseInt(boton.dataset.delta, 10);
                    item.cantidad += delta;
                    if (item.cantidad > item.stock) {
                        alert(`Stock máximo disponible para "${item.nombre}": ${item.stock}.`);
                        item.cantidad = item.stock;
                    }
                    if (item.cantidad <= 0) {
                        carrito.delete(item.id);
                    }
                    renderCarrito();
                });
            });
        }

        document.getElementById('form-venta').addEventListener('submit', (e) => {
            if (carrito.size === 0) {
                e.preventDefault();
                alert('Agrega al menos un producto al carrito.');
                return;
            }
            // Limpia inputs previos y reconstruye con el estado actual.
            document.querySelectorAll('input[name^="items["]').forEach((el) => el.remove());
            carrito.forEach((item) => {
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'items[' + item.id + '][producto_id]';
                inputId.value = item.id;

                const inputCantidad = document.createElement('input');
                inputCantidad.type = 'hidden';
                inputCantidad.name = 'items[' + item.id + '][cantidad]';
                inputCantidad.value = item.cantidad;

                document.getElementById('form-venta').appendChild(inputId);
                document.getElementById('form-venta').appendChild(inputCantidad);
            });
        });
    </script>
</x-app-layout>
