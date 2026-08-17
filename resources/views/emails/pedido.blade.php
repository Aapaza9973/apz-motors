<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pedido #{{ $pedido->id }} — APZ Motor's</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #eceff1; margin: 0; padding: 24px; color: #181a1d; }
        .tarjeta { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #dfe3e7; }
        .cinta { height: 6px; background: repeating-linear-gradient(-45deg, #18191c 0 8px, #f54505 8px 16px); }
        .cabeza { padding: 20px 24px; border-bottom: 1px solid #eceff1; display: flex; align-items: center; gap: 12px; }
        .marca { font-size: 17px; font-weight: bold; color: #18191c; }
        .marca b { color: #f54505; }
        .tagline { font-size: 10px; color: #8b9097; letter-spacing: 0.08em; text-transform: uppercase; }
        .cuerpo { padding: 24px; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        p { font-size: 13px; color: #4d5157; line-height: 1.5; }
        .estado { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.06em; }
        .estado-recibido { background: #fef3e2; color: #b45309; }
        .estado-confirmado { background: #e6f4ec; color: #16794b; }
        .estado-cancelado { background: #f1f3f5; color: #6b7076; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        th { text-align: left; padding: 8px 10px; background: #f6f8f9; color: #6b7076; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #dfe3e7; }
        td { padding: 8px 10px; border-bottom: 1px solid #eceff1; }
        td.monto, .monto { font-family: Consolas, monospace; }
        .total { font-size: 15px; font-weight: bold; color: #f54505; font-family: Consolas, monospace; text-align: right; margin-top: 10px; }
        .dato { margin-top: 16px; background: #f6f8f9; border-radius: 8px; padding: 12px 14px; font-size: 12px; color: #4d5157; }
        .dato b { color: #18191c; }
        .boton { display: inline-block; margin-top: 18px; background: #18191c; color: #ffffff !important; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 12px; font-weight: bold; }
        .pie { padding: 16px 24px; background: #f6f8f9; font-size: 10px; color: #8b9097; text-align: center; letter-spacing: 0.05em; border-top: 1px solid #eceff1; }
    </style>
</head>
<body>
    <div class="tarjeta">
        <div class="cinta"></div>
        <div class="cabeza">
            <span style="font-size:26px; font-weight:bold; color:#f54505;">A</span>
            <div>
                <div class="marca"><b>APZ</b> Motor's</div>
                <div class="tagline">Repuestos y accesorios para motocicletas · La Paz, Bolivia</div>
            </div>
        </div>
        <div class="cuerpo">
            <span class="estado estado-{{ $estado }}">
                {{ match ($estado) { 'confirmado' => 'Confirmado', 'cancelado' => 'Cancelado', default => 'Recibido' } }}
            </span>

            @if ($estado === 'recibido')
                <h1 style="margin-top:12px;">Hola {{ $pedido->nombre_cliente }}, tu pedido fue recibido</h1>
                <p>Ya está en la bandeja del taller. Te llamaremos al <b>{{ $pedido->telefono }}</b> para coordinar la entrega y el pago.
                @if ($pedido->estaPagado())
                    <br><br>Tu pedido ya quedó <b>pagado</b> ({{ $pedido->metodo_pago }}).
                @endif
                </p>

                @if ($pedido->token)
                    <p style="margin-top:16px;">Antes de que el taller lo procese podés:</p>
                    <table style="width:100%; border-collapse:collapse; margin-top:8px;">
                        <tr>
                            <td style="padding:4px 0;">
                                <a class="boton" style="display:inline-block; background:#16794b;" href="{{ route('pedidos.confirmar-cliente', ['pedido' => $pedido, 'token' => $pedido->token]) }}">Confirmar mi pedido</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:4px 0;">
                                <a class="boton" style="display:inline-block; background:#6b7076;" href="{{ route('pedidos.cancelar-cliente', ['pedido' => $pedido, 'token' => $pedido->token]) }}">Cancelar mi pedido</a>
                            </td>
                        </tr>
                    </table>
                    <p style="font-size:11px; color:#8b9097; margin-top:8px;">Si no hiciste este pedido, ignorá este correo o usá el enlace de cancelación.</p>
                @endif
            @elseif ($estado === 'confirmado')
                <h1 style="margin-top:12px;">Tu pedido fue confirmado</h1>
                <p>El taller confirmó tu pedido y se generó la <b>venta #{{ $ventaId }}</b>.
                @if ($pedido->estaPagado())
                    Tu pago ({{ $pedido->metodo_pago }}) ya está registrado como completado.
                @else
                    El stock ya fue apartado; quedó pendiente el pago al recibir.
                @endif
                </p>
            @else
                <h1 style="margin-top:12px;">Tu pedido fue cancelado</h1>
                <p>El taller canceló tu pedido. No se descontó stock y no hay ningún cargo pendiente. Consultanos al taller si necesitás ayuda.</p>
            @endif

            <table>
                <thead>
                    <tr><th>Producto</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach ($pedido->items as $item)
                        <tr>
                            <td>{{ $item->nombre }}</td>
                            <td style="text-align:center;" class="monto">{{ $item->cantidad }}</td>
                            <td style="text-align:right;" class="monto">Bs {{ number_format($item->precio_unitario * $item->cantidad, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="total">Total: Bs {{ number_format($pedido->total, 2) }}</div>

            <div class="dato">
                <b>Pedido #{{ $pedido->id }}</b> · {{ $pedido->created_at->format('d/m/Y H:i') }}<br>
                Podés consultar el estado en cualquier momento desde el catálogo público.
            </div>

            <a class="boton" href="{{ url('/catalogo/consultar') }}">Consultar mi pedido</a>
        </div>
        <div class="pie">© {{ date('Y') }} APZ Motor's · Tu ruta, nuestro compromiso.</div>
    </div>
</body>
</html>
