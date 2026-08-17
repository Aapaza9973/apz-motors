<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cierre de caja — APZ Motor's</title>
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
        .ficha { width: 100%; border-collapse: collapse; margin: 12px 0 4px; }
        .ficha td { background: #f6f8f9; border: 1px solid #dfe3e7; padding: 8px 12px; }
        .ficha span { display: block; font-size: 8px; color: #6b7076; text-transform: uppercase; letter-spacing: 0.08em; }
        .ficha b { display: block; font-size: 15px; color: #18191c; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #18191c; color: #ffffff; text-align: left; padding: 6px 8px; font-size: 8px; text-transform: uppercase; letter-spacing: 0.06em; }
        td { padding: 6px 8px; border-bottom: 1px solid #e9ecef; font-size: 10px; }
        tr:nth-child(even) td { background: #fafbfc; }
        .mono, td.mono { font-family: 'DejaVu Sans Mono', monospace; font-variant-numeric: tabular-nums; }
        .der { text-align: right; }
        .total { font-size: 14px; font-weight: bold; color: #f54505; margin-top: 14px; text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
        .obs { margin-top: 14px; border: 1px solid #dfe3e7; border-radius: 4px; padding: 8px 12px; }
        .obs span { font-size: 8px; color: #6b7076; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 3px; }
        .pie { margin-top: 22px; padding-top: 8px; border-top: 1px solid #dfe3e7; font-size: 8px; color: #8b9097; text-align: center; letter-spacing: 0.05em; }
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
                    <b>Cierre de Caja</b>
                    Documento generado {{ now()->format('d/m/Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="cinta"></div>

    <h1>Cierre de caja — <b>{{ $cierre->fecha_cierre->format('d/m/Y') }}</b></h1>
    <div class="sub">Vendedor: {{ $cierre->usuario->name }} · Turno cerrado el {{ $cierre->fecha_cierre->format('l, d \\d\\e F \\d\\e Y') }}</div>

    <table class="ficha">
        <tr>
            <td><span>Total del turno</span><b class="mono">Bs {{ number_format($cierre->total_ventas, 2) }}</b></td>
            <td><span>Ventas registradas</span><b class="mono">{{ $cierre->cantidad_ventas }}</b></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Método de pago</th>
                <th class="der">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Efectivo</td>
                <td class="der mono">Bs {{ number_format($cierre->total_efectivo, 2) }}</td>
            </tr>
            <tr>
                <td>Tarjeta</td>
                <td class="der mono">Bs {{ number_format($cierre->total_tarjeta, 2) }}</td>
            </tr>
            <tr>
                <td>Transferencia</td>
                <td class="der mono">Bs {{ number_format($cierre->total_transferencia, 2) }}</td>
            </tr>
            <tr>
                <td>Stripe</td>
                <td class="der mono">Bs {{ number_format($cierre->total_stripe, 2) }}</td>
            </tr>
            <tr>
                <td>PayPal</td>
                <td class="der mono">Bs {{ number_format($cierre->total_paypal, 2) }}</td>
            </tr>
            <tr>
                <td>Otros</td>
                <td class="der mono">Bs {{ number_format($cierre->total_otros, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">Total arqueado: Bs {{ number_format($cierre->total_ventas, 2) }}</div>

    @if ($cierre->observacion)
        <div class="obs">
            <span>Observación del turno</span>
            {{ $cierre->observacion }}
        </div>
    @endif

    <div class="pie">© {{ date('Y') }} APZ Motor's · La Paz, Bolivia — Sistema de Ventas e Inventario · Tu ruta, nuestro compromiso.</div>
</body>
</html>
