<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo al cliente del catálogo público con el estado de su pedido:
 * recibido, confirmado (con número de venta) o cancelado.
 */
class PedidoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'recibido'|'confirmado'|'cancelado'  $estado
     */
    public function __construct(
        public Pedido $pedido,
        public string $estado,
        public ?int $ventaId = null,
    ) {}

    public function envelope(): Envelope
    {
        $asunto = match ($this->estado) {
            'confirmado' => "Tu pedido #{$this->pedido->id} fue confirmado — APZ Motor's",
            'cancelado' => "Pedido #{$this->pedido->id} cancelado — APZ Motor's",
            default => "Pedido #{$this->pedido->id} recibido — APZ Motor's",
        };

        return new Envelope(subject: $asunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pedido');
    }
}
