<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservaCanceladaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Reserva $reserva,
        public readonly string $horaFormateada,
        public readonly ?string $empresaSlug = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reserva cancelada - Minigolf Córdoba');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reserva-cancelada');
    }

    public function attachments(): array
    {
        return [];
    }
}
