<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** E-mail « Nouvelle connexion détectée » (voir App\Support\AlerteConnexion). */
class NouvelleConnexion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nom,
        public readonly string $date,
        public readonly string $ip,
        public readonly string $appareil,
        public readonly string $userAgent,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'KalanNet : nouvelle connexion détectée sur votre compte');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.nouvelle-connexion', with: ['lienMotDePasse' => route('profile.edit')]);
    }
}
