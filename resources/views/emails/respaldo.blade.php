<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Respaldo de base de datos — APZ Motor's</title>
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
        .estado-exitoso { background: #e6f4ec; color: #16794b; }
        .estado-fallido { background: #fdeceb; color: #d92d20; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 12px; }
        th { text-align: left; padding: 8px 10px; background: #f6f8f9; color: #6b7076; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #dfe3e7; }
        td { padding: 8px 10px; border-bottom: 1px solid #eceff1; font-family: Consolas, monospace; }
        .error { margin-top: 16px; background: #fdeceb; border: 1px solid #f5c2be; border-radius: 8px; padding: 12px 14px; font-size: 12px; color: #d92d20; font-family: Consolas, monospace; }
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
                <div class="tagline">Sistema de Ventas e Inventario · La Paz, Bolivia</div>
            </div>
        </div>
        <div class="cuerpo">
            <span class="estado estado-{{ $estado }}">
                {{ $estado === 'exitoso' ? 'Completado' : 'Fallido' }}
            </span>
            <h1 style="margin-top:12px;">
                {{ $estado === 'exitoso' ? 'Respaldo diario de la base de datos' : 'El respaldo diario no se pudo completar' }}
            </h1>
            <p>Ejecutado el {{ now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }} (hora local del servidor).</p>

            <table>
                <tr><th>Campo</th><th>Detalle</th></tr>
                <tr><td>Resultado</td><td>{{ $estado === 'exitoso' ? 'exitoso' : 'fallido' }}</td></tr>
                @if ($archivo)
                    <tr><td>Archivo</td><td>{{ $archivo }}</td></tr>
                @endif
                @if ($tamanoBytes !== null)
                    <tr><td>Tamaño</td><td>{{ number_format($tamanoBytes / 1024, 1) }} KB (comprimido)</td></tr>
                @endif
            </table>

            @if ($estado === 'fallido' && $mensaje)
                <div class="error">{{ $mensaje }}</div>
            @endif
        </div>
        <div class="pie">© {{ date('Y') }} APZ Motor's · Tu ruta, nuestro compromiso.</div>
    </div>
</body>
</html>
