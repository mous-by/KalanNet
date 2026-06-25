<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppelNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $prenomEleve,
        public readonly string $nomEleve,
        public readonly string $statutControle,
        public readonly string $libelleAppel,
        public readonly string $dateAppel,
        public readonly string $nomEcole = 'KalanNet',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Notification appel de présence — {$this->prenomEleve} {$this->nomEleve}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appel-notification',
        );
    }
}
