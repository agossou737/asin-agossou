<?php

namespace App\Mail;

use App\Models\DemandeActe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeRejeteeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DemandeActe $demande) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre demande '.$this->demande->numero.' a été rejetée');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.demande-rejetee');
    }
}
