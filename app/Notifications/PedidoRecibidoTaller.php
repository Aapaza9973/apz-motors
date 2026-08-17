<?php

namespace App\Notifications;

use App\Models\Pedido;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Avisa al taller (WhatsApp / push) que llegó un pedido del catálogo
 * público. Complementa la campana interna del panel: la idea es que el
 * encargado se entere aunque no tenga la app abierta.
 */
class PedidoRecibidoTaller extends Notification
{
    use Queueable;

    public function __construct(public Pedido $pedido) {}

    /** @return array<int, class-string> */
    public function via(object $notifiable): array
    {
        return [WhatsAppChannel::class];
    }

    /** @return array<string, string> */
    public function toWhatsApp(object $notifiable): array
    {
        $items = $this->pedido->relationLoaded('items')
            ? $this->pedido->items->sum('cantidad')
            : $this->pedido->items()->sum('cantidad');

        $pago = $this->pedido->estaPagado()
            ? 'pagado ('.$this->pedido->metodo_pago.')'
            : 'pago al recibir';

        return [
            'titulo' => "Nuevo pedido #{$this->pedido->id} — APZ Motor's",
            'mensaje' => sprintf(
                'Nuevo pedido #%d de %s (%s): %d ítem(s), Bs %s. %s. Confirmalo desde la bandeja del panel.',
                $this->pedido->id,
                $this->pedido->nombre_cliente,
                $this->pedido->telefono,
                $items,
                number_format((float) $this->pedido->total, 2),
                $pago
            ),
        ];
    }
}
