<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas — APZ Motor's</title>
    <style>
        /* Identidad corporativa APZ Motor's (Documento Maestro): llama #f54505,
           carbon #18191c, números de dato en mono (ficha de repuesto). */
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #181a1d; }
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
        .total { font-size: 14px; font-weight: bold; color: #f54505; margin-top: 14px; text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
        .estado-ok { color: #16794b; font-weight: bold; }
        .estado-pend { color: #7a5b00; font-weight: bold; }
        .estado-cancel { color: #d92d20; font-weight: bold; }
        .pie { margin-top: 22px; padding-top: 8px; border-top: 1px solid #dfe3e7; font-size: 8px; color: #8b9097; text-align: center; letter-spacing: 0.05em; }
        .page-break { page-break-before: always; }
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
                    <b>Reporte de Ventas</b>
                    Documento generado {{ now()->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="cinta"></div>

    <h1>Ventas del periodo</h1>
    <div class="sub">Desde {{ $fechaInicio->format('d/m/Y') }} hasta {{ $fechaFin->format('d/m/Y') }}</div>

    <table class="resumen">
        <tr>
            <td><span>Total vendido</span><b class="mono">Bs {{ number_format($total, 2) }}</b></td>
            <td><span>Transacciones</span><b class="mono">{{ $cantidad }}</b></td>
            <td><span>Ticket promedio</span><b class="mono">Bs {{ number_format($cantidad > 0 ? $total / $cantidad : 0, 2) }}</b></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>N.º</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="der">Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ventas as $venta)
                <tr>
                    <td class="mono">#{{ $venta->id }}</td>
                    <td class="mono">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $venta->cliente?->nombre ?? 'Consumidor final' }}</td>
                    <td>{{ $venta->usuario->name }}</td>
                    <td class="der mono">Bs {{ number_format($venta->total, 2) }}</td>
                    <td class="estado-{{ strtolower($venta->estado) === 'cancelada' ? 'cancel' : (strtolower($venta->estado) === 'pagado' ? 'ok' : 'pend') }}">{{ $venta->estado }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin ventas en el periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($masVendidos->isNotEmpty())
        <div class="page-break"></div>
        <h1>Productos <b>más vendidos</b></h1>
        <div class="sub">Ranking por cantidad en el periodo</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th class="der">Cantidad</th>
                    <th class="der">Ingresos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($masVendidos as $i => $item)
                    <tr>
                        <td class="mono">{{ $i + 1 }}</td>
                        <td>{{ $item['producto'] }}</td>
                        <td class="der mono">{{ $item['cantidad'] }}</td>
                        <td class="der mono">Bs {{ number_format($item['ingresos'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="pie">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia — Sistema de Ventas e Inventario · Tu ruta, nuestro compromiso.</div>
</body>
</html>
