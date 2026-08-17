<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de inventario — APZ Motor's</title>
    <style>
        /* Identidad corporativa APZ Motor's (Documento Maestro): llama #f54505,
           carbon #18191c, números de dato en mono (ficha de repuesto). */
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #181a1d; }
        .cabeza { border-bottom: 3px solid #f54505; padding-bottom: 10px; margin-bottom: 16px; }
        .cabeza table { width: 100%; border-collapse: collapse; }
        .cabeza .marca { font-size: 17px; font-weight: bold; color: #18191c; letter-spacing: 0.02em; }
        .cabeza .marca b { color: #f54505; }
        .cabeza .tagline { font-size: 9px; color: #6b7076; letter-spacing: 0.06em; }
        .cabeza .doc { text-align: right; font-size: 9px; color: #6b7076; }
        .cabeza .doc b { font-size: 12px; color: #18191c; display: block; }
        .cinta { height: 5px; background: repeating-linear-gradient(-45deg, #18191c 0 7px, #f54505 7px 14px); margin-bottom: 14px; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #18191c; text-transform: uppercase; letter-spacing: 0.04em; }
        h1 b { color: #f54505; }
        .sub { color: #6b7076; font-size: 9px; margin-bottom: 14px; }
        .resumen { width: 100%; border-collapse: collapse; margin: 12px 0 4px; }
        .resumen td { background: #f6f8f9; border: 1px solid #dfe3e7; padding: 8px 12px; }
        .resumen span { display: block; font-size: 8px; color: #6b7076; text-transform: uppercase; letter-spacing: 0.08em; }
        .resumen b { display: block; font-size: 15px; color: #18191c; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #18191c; color: #ffffff; text-align: left; padding: 6px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; }
        td { padding: 6px 8px; border-bottom: 1px solid #e9ecef; font-size: 10px; }
        tr:nth-child(even) td { background: #fafbfc; }
        .mono, td.mono { font-family: 'DejaVu Sans Mono', monospace; font-variant-numeric: tabular-nums; }
        .der { text-align: right; }
        .bajo { color: #d92d20; font-weight: bold; }
        .ok { color: #16794b; }
        .pie { margin-top: 20px; padding-top: 8px; border-top: 1px solid #dfe3e7; font-size: 8px; color: #8b9097; text-align: center; letter-spacing: 0.05em; }
    </style>
</head>
<body>
    <div class="cabeza">
        <table>
            <tr>
                <td style="width: 44px;">
                    <img src="{{ public_path('images/icono.png') }}" alt="APZ Motor's" style="width: 34px; height: 34px;">
                </td>
                <td>
                    <div class="marca"><b>APZ</b> Motor's</div>
                    <div class="tagline">REPUESTOS Y ACCESORIOS PARA MOTOCICLETAS · LA PAZ, BOLIVIA</div>
                </td>
                <td class="doc">
                    <b>Reporte de Inventario</b>
                    Documento generado {{ $generado->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="cinta"></div>

    <h1>Estado del <b>inventario</b></h1>
    <div class="sub">
        @if ($soloBajo) Filtro: solo productos bajo umbral · @endif
        @if (request('q')) Búsqueda: "{{ request('q') }}" · @endif
        Valor total en stock
    </div>

    <table class="resumen">
        <tr>
            <td><span>Valor del inventario</span><b class="mono">Bs {{ number_format($valorInventario, 2) }}</b></td>
            <td><span>Productos listados</span><b class="mono">{{ $productos->count() }}</b></td>
            <td><span>Bajo umbral</span><b class="mono">{{ $productos->filter(fn ($p) => $p->tieneStockBajo())->count() }}</b></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th>Tipo</th>
                <th class="der">Stock</th>
                <th class="der">Umbral</th>
                <th class="der">Precio</th>
                <th class="der">Valor en stock</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($productos as $producto)
                <tr>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->categoria->nombre }}</td>
                    <td>{{ $producto->categoria->tipo }}</td>
                    <td class="der mono {{ $producto->tieneStockBajo() ? 'bajo' : '' }}">{{ $producto->stock }}</td>
                    <td class="der mono">{{ $producto->umbral_alerta }}</td>
                    <td class="der mono">Bs {{ number_format($producto->precio_unitario, 2) }}</td>
                    <td class="der mono">Bs {{ number_format($producto->stock * $producto->precio_unitario, 2) }}</td>
                    <td class="{{ $producto->tieneStockBajo() ? 'bajo' : 'ok' }}">{{ $producto->tieneStockBajo() ? 'Bajo stock' : 'OK' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Sin productos para el filtro seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia — Sistema de Ventas e Inventario · Tu ruta, nuestro compromiso.</div>
</body>
</html>
