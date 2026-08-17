<?php

namespace App\Jobs;

use App\Mail\PedidoMail;
use App\Models\Pedido;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envía al cliente el correo de estado de su pedido en línea (recibido,
 * confirmado con número de venta, cancelado). Corre en la cola para que
 * el checkout no espere al envío; un fallo del correo no rompe el flujo
 * del pedido.
 */
class NotificarClientePedido implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $pedidoId,
        public string $estado,
        public ?int $ventaId = null,
    ) {}

    public function handle(): void
    {
        $pedido = Pedido::with('items')->find($this->pedidoId);

        if (! $pedido || ! $pedido->email) {
            return;
        }

        try {
            Mail::to($pedido->email)->send(new PedidoMail($pedido, $this->estado, $this->ventaId));
        } catch (\Throwable $e) {
            Log::warning('Pedido: no se pudo notificar al cliente por correo.', [
                'pedido' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
