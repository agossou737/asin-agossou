<?php

namespace App\Mail;

use App\Models\DemandeActe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NouvelleDemandeAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemandeActe $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouvelle demande à traiter '.$this->demande->numero);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.nouvelle-demande-admin');
    }
}
