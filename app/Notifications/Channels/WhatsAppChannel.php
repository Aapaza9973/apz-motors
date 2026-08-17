<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal de notificación al taller por WhatsApp / push.
 *
 * Sin proveedor configurado (services.whatsapp.enabled = false) el aviso
 * queda en el log, para no perder el evento; la campana interna del panel
 * sigue siendo la fuente de verdad. Con proveedor, hace un POST con el
 * payload {"to", "text", "token"} al endpoint configurado (por ejemplo la
 * Messages API de Twilio o un webhook propio). Un fallo de envío nunca
 * rompe el flujo del pedido.
 */
class WhatsAppChannel
{
    /**
     * @param  mixed  $route  Destino configurado (número del taller).
     */
    public function send(object $notifiable, Notification $notification, mixed $route = null): void
    {
        $datos = method_exists($notification, 'toWhatsApp')
            ? $notification->toWhatsApp($notifiable)
            : [];

        $destino = $route ?: config('services.whatsapp.to');

        if (! config('services.whatsapp.enabled')) {
            Log::info('Taller: pedido recibido del catálogo (WhatsApp desactivado, aviso en log).', [
                'destino' => $destino,
                ...$datos,
            ]);

            return;
        }

        $url = config('services.whatsapp.url');

        if (! $url || ! $destino) {
            Log::warning('Taller: falta WHATSAPP_WEBHOOK_URL o NOTIFY_TALLER_PHONE para enviar el aviso.', [
                'destino' => $destino,
                ...$datos,
            ]);

            return;
        }

        try {
            Http::timeout(10)->post($url, [
                'to' => $destino,
                'text' => $datos['mensaje'] ?? '',
                'token' => config('services.whatsapp.token'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Taller: no se pudo enviar la notificación por WhatsApp.', [
                'error' => $e->getMessage(),
                'destino' => $destino,
                ...$datos,
            ]);
        }
    }
}
