<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Rappel periodique (8h00 et 16h00) des demandes a traiter. */
class RappelDemandesAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, int> $compteurs nombre de demandes par statut (deposee, en_cours)
     * @param Collection<int, \App\Models\DemandeActe> $demandes
     */
    public function __construct(public array $compteurs, public Collection $demandes, public int $reste = 0) {}

    public function envelope(): Envelope
    {
        $total = array_sum($this->compteurs);

        return new Envelope(subject: sprintf(
            'Rappel : %d demande(s) à traiter (%d en attente, %d en cours)',
            $total,
            $this->compteurs['deposee'] ?? 0,
            $this->compteurs['en_cours'] ?? 0,
        ));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.rappel-admin');
    }
}
