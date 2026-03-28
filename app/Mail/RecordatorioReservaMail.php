<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioReservaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reserva $reserva,
        public readonly string $horaFormateada,
        public readonly string $empresaNombre,
        public readonly string $empresaSlug,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '['.$this->empresaNombre.'] Recordatorio: tienes una reserva mañana');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.recordatorio-reserva');
    }

    public function attachments(): array
    {
        return [];
    }
}
