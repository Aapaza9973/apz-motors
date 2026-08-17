<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notificación del respaldo diario (Documento Maestro §6): el administrador
 * recibe un correo con el resultado, el archivo generado y su tamaño.
 */
class RespaldoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'exitoso'|'fallido'  $estado
     */
    public function __construct(
        public string $estado,
        public ?string $archivo = null,
        public ?int $tamanoBytes = null,
        public ?string $mensaje = null,
    ) {}

    public function envelope(): Envelope
    {
        $asunto = $this->estado === 'exitoso'
            ? 'Respaldo de base de datos completado — APZ Motor\'s'
            : 'Respaldo de base de datos fallido — APZ Motor\'s';

        return new Envelope(subject: $asunto);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.respaldo');
    }
}
