<?php

namespace App\Jobs;

use App\Models\Pedido;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\PedidoRecibidoTaller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa al taller (WhatsApp / push) que llegó un pedido del catálogo.
 * Corre en la cola para que el checkout no espere al proveedor externo;
 * un fallo del envío nunca rompe el flujo del pedido.
 */
class NotificarTallerPedido implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $pedidoId) {}

    public function handle(): void
    {
        $pedido = Pedido::find($this->pedidoId);

        if (! $pedido) {
            return;
        }

        try {
            Notification::route(WhatsAppChannel::class, config('services.whatsapp.to'))
                ->notify(new PedidoRecibidoTaller($pedido));
        } catch (\Throwable $e) {
            Log::warning('Taller: no se pudo notificar el pedido recibido.', [
                'pedido' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
