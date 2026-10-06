<?php

namespace App\Console\Commands;

use App\Enums\StatutDemande;
use App\Models\DemandeActe;
use App\Services\NotificationDemandeService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class RappelDemandesAdmin extends Command
{
    protected $signature = 'demandes:rappel-admin';

    protected $description = 'Envoie aux administrateurs un rappel des demandes en attente (déposées) et en cours';

    public function handle(NotificationDemandeService $notifications): int
    {
        try {
            $demandes = DemandeActe::query()
                ->whereIn('statut', [StatutDemande::Deposee->value, StatutDemande::EnCours->value])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();
        } catch (QueryException $e) {
            Log::error('Rappel admin : échec de lecture des demandes', ['erreur' => $e->getMessage()]);
            $this->error('Erreur technique : consultez storage/logs/laravel.log.');

            return self::FAILURE;
        }

        if ($demandes->isEmpty()) {
            $this->info('Aucune demande à traiter : aucun rappel envoyé.');

            return self::SUCCESS;
        }

        $envoyes = $notifications->rappelAdmin($demandes);

        if ($envoyes === 0) {
            $this->error('Aucun email de rappel n\'a pu être envoyé (voir storage/logs/laravel.log).');

            return self::FAILURE;
        }

        $this->info(sprintf('%d demande(s) à traiter : rappel envoyé à %d administrateur(s).', $demandes->count(), $envoyes));

        return self::SUCCESS;
    }
}
