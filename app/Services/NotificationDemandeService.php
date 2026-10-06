<?php

namespace App\Services;

use App\Enums\StatutDemande;
use App\Mail\DemandeRecueMail;
use App\Mail\DemandeRejeteeMail;
use App\Mail\DemandeValideeMail;
use App\Mail\NouvelleDemandeAdminMail;
use App\Mail\RappelDemandesAdminMail;
use App\Models\DemandeActe;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi des emails. Un echec d'envoi est journalise mais ne fait JAMAIS echouer
 * la demande : elle est deja enregistree en base.
 */
class NotificationDemandeService
{
    /** Nombre maximum de demandes detaillees dans un email de rappel. */
    public const RAPPEL_MAX_LIGNES = 50;

    public function __construct(private readonly PdfDemandeService $pdf) {}

    /** Accuse de reception a l'usager + alerte a l'administrateur. */
    public function demandeDeposee(DemandeActe $demande): void
    {
        $this->envoyer($demande->email, new DemandeRecueMail($demande), 'accusé de réception', $demande);
        $this->envoyer(config('demandes.admin_email'), new NouvelleDemandeAdminMail($demande), 'alerte administrateur', $demande);
    }

    /** Email de validation avec le PDF recapitulatif en piece jointe (sans PDF si sa generation echoue). */
    public function demandeValidee(DemandeActe $demande): void
    {
        $pdf = null;

        try {
            $pdf = $this->pdf->generer($demande);
        } catch (Throwable $e) {
            Log::error("Échec de la génération du PDF joint à l'email", ['demande' => $demande->id, 'erreur' => $e->getMessage()]);
        }

        $this->envoyer($demande->email, new DemandeValideeMail($demande, $pdf), 'demande validée', $demande);
    }

    public function demandeRejetee(DemandeActe $demande): void
    {
        $this->envoyer($demande->email, new DemandeRejeteeMail($demande), 'demande rejetée', $demande);
    }

    /**
     * Rappel aux administrateurs : demandes en attente (deposees) et en cours.
     *
     * @param Collection<int, DemandeActe> $demandes triees de la plus ancienne a la plus recente
     * @return int nombre d'emails envoyes
     */
    public function rappelAdmin(Collection $demandes): int
    {
        $compteurs = [
            StatutDemande::Deposee->value => $demandes->where('statut', StatutDemande::Deposee)->count(),
            StatutDemande::EnCours->value => $demandes->where('statut', StatutDemande::EnCours)->count(),
        ];

        $liste = $demandes->take(self::RAPPEL_MAX_LIGNES)->values();
        $reste = max($demandes->count() - $liste->count(), 0);

        $envoyes = 0;
        foreach ($this->destinatairesAdmin() as $email) {
            if ($this->envoyer($email, new RappelDemandesAdminMail($compteurs, $liste, $reste), 'rappel administrateur')) {
                $envoyes++;
            }
        }

        return $envoyes;
    }

    /**
     * ADMIN_EMAIL + email de chaque compte administrateur (sans doublon).
     *
     * @return list<string>
     */
    public function destinatairesAdmin(): array
    {
        $emails = User::query()->where('is_admin', true)->pluck('email')->all();
        $emails[] = config('demandes.admin_email');

        return array_values(array_unique(array_filter(array_map(fn ($e) => mb_strtolower(trim((string) $e)), $emails))));
    }

    private function envoyer(?string $destinataire, Mailable $mail, string $contexte, ?DemandeActe $demande = null): bool
    {
        if (! $destinataire) {
            return false;
        }

        try {
            Mail::to($destinataire)->send($mail);

            return true;
        } catch (Throwable $e) {
            Log::error("Échec d'envoi de l'email ({$contexte})", [
                'demande' => $demande?->id,
                'destinataire' => $destinataire,
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
