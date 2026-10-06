<?php

namespace App\Mail;

use App\Models\DemandeActe;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemandeValideeMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param string|null $pdf contenu binaire du recapitulatif (null si la generation a echoue) */
    public function __construct(public DemandeActe $demande, protected ?string $pdf = null) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre demande '.$this->demande->numero.' a été validée');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.demande-validee');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if ($this->pdf === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdf, 'demande-'.$this->demande->numero.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
