<?php

namespace App\Services;

use App\Enums\StatutDemande;
use App\Exceptions\DemandeDejaExistanteException;
use App\Exceptions\ErreurTraitementException;
use App\Exceptions\TransitionInterditeException;
use App\Enums\TypeActe;
use App\Models\DemandeActe;
use App\Models\DemandeHistorique;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandeService
{
    public const PAR_PAGE_MAX = 20;

    public function __construct(private readonly NotificationDemandeService $notifications) {}

    /**
     * Depose une demande : une seule demande « active » (non rejetee) par couple (NPI, type d'acte).
     * Verification + creation dans une transaction, avec verrou sur les lignes existantes.
     *
     * @param array{npi: string, email: string, type_acte: string, nombre_copies: int} $data
     *
     * @throws DemandeDejaExistanteException
     * @throws ErreurTraitementException
     */
    public function deposer(array $data): DemandeActe
    {
        try {
            $demande = DB::transaction(function () use ($data) {
                $existante = DemandeActe::query()
                    ->where('npi', (string) $data['npi'])
                    ->where('type_acte', $data['type_acte'])
                    ->where('statut', '!=', StatutDemande::Rejetee->value) // un rejet autorise un nouveau depot
                    ->lockForUpdate()
                    ->first();

                if ($existante) {
                    throw DemandeDejaExistanteException::pour($existante);
                }

                $demande = DemandeActe::create([
                    'npi' => (string) $data['npi'],
                    'email' => $data['email'],
                    'type_acte' => $data['type_acte'],
                    'nombre_copies' => $data['nombre_copies'],
                    'statut' => StatutDemande::Deposee,
                ]);

                DemandeHistorique::create([
                    'demande_id' => $demande->id,
                    'source' => 'depot',
                    'ancien_statut' => null,
                    'nouveau_statut' => StatutDemande::Deposee,
                ]);

                return $demande;
            }, 3);
        } catch (QueryException $e) {
            Log::error('Échec du dépôt de la demande', ['erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        // Hors transaction : un echec d'email ne remet jamais en cause l'enregistrement.
        $this->notifications->demandeDeposee($demande);

        return $demande;
    }

    /** Demandes d'un usager : de la plus recente a la plus ancienne, filtre facultatif par statut. */
    public function lister(string $npi, ?StatutDemande $statut = null, int $parPage = self::PAR_PAGE_MAX): LengthAwarePaginator
    {
        return DemandeActe::query()
            ->where('npi', $npi)
            ->when($statut, fn ($q) => $q->where('statut', $statut->value))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min($parPage, self::PAR_PAGE_MAX));
    }

    /** Suivi public par numero de demande. */
    public function trouverParNumero(string $numero): ?DemandeActe
    {
        return DemandeActe::query()->where('numero', mb_strtoupper($numero))->first();
    }

    /**
     * Nombre de demandes par statut pour un usager (tous les statuts presents, y compris a 0).
     *
     * @return array<string, int>
     */
    public function compteursParStatut(string $npi): array
    {
        $reels = DemandeActe::query()
            ->where('npi', $npi)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $compteurs = [];
        foreach (StatutDemande::cases() as $statut) {
            $compteurs[$statut->value] = (int) ($reels[$statut->value] ?? 0);
        }

        return $compteurs;
    }

    /**
     * Liste admin : toutes les demandes, filtres facultatifs (statut, type d'acte, debut de NPI).
     *
     * @param array{statut?: ?string, type_acte?: ?string, npi?: ?string, numero?: ?string} $filtres
     */
    public function listerPourAdmin(array $filtres = [], int $parPage = self::PAR_PAGE_MAX): LengthAwarePaginator
    {
        return DemandeActe::query()
            ->when($filtres['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filtres['type_acte'] ?? null, fn ($q, $v) => $q->where('type_acte', $v))
            ->when($filtres['npi'] ?? null, fn ($q, $v) => $q->where('npi', 'like', $v.'%'))
            ->when($filtres['numero'] ?? null, fn ($q, $v) => $q->where('numero', 'like', mb_strtoupper($v).'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(min($parPage, self::PAR_PAGE_MAX));
    }

    /**
     * Statistiques globales pour le tableau de bord.
     *
     * @return array{total: int, par_statut: array<string, int>, par_type: array<string, int>}
     */
    public function statistiques(): array
    {
        $parStatut = DemandeActe::query()->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $parType = DemandeActe::query()->selectRaw('type_acte, COUNT(*) as total')->groupBy('type_acte')->pluck('total', 'type_acte');

        $statuts = [];
        foreach (StatutDemande::cases() as $c) {
            $statuts[$c->value] = (int) ($parStatut[$c->value] ?? 0);
        }
        $types = [];
        foreach (TypeActe::cases() as $c) {
            $types[$c->value] = (int) ($parType[$c->value] ?? 0);
        }

        return ['total' => array_sum($statuts), 'par_statut' => $statuts, 'par_type' => $types];
    }

    /**
     * Fait avancer une demande dans son cycle de vie (transaction + verrou : pas de double traitement),
     * puis notifie l'usager par email en cas de validation ou de rejet.
     *
     * @throws TransitionInterditeException
     * @throws ErreurTraitementException
     */
    public function changerStatut(string $id, StatutDemande $cible, ?string $motif = null, ?string $acteurId = null, string $source = 'admin'): DemandeActe
    {
        try {
            $demande = DB::transaction(function () use ($id, $cible, $motif, $acteurId, $source) {
                $demande = DemandeActe::query()->lockForUpdate()->findOrFail($id);

                if (! $demande->statut->peutPasserA($cible)) {
                    throw TransitionInterditeException::entre($demande->statut, $cible);
                }

                $ancien = $demande->statut;
                $demande->statut = $cible;
                $demande->motif_rejet = $cible === StatutDemande::Rejetee ? $motif : null;
                $demande->save();

                DemandeHistorique::create([
                    'demande_id' => $demande->id,
                    'user_id' => $acteurId,
                    'source' => $source,
                    'ancien_statut' => $ancien,
                    'nouveau_statut' => $cible,
                    'motif' => $demande->motif_rejet,
                ]);

                return $demande;
            }, 3);
        } catch (QueryException $e) {
            Log::error('Échec du changement de statut', ['demande' => $id, 'erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        match ($demande->statut) {
            StatutDemande::Validee => $this->notifications->demandeValidee($demande),
            StatutDemande::Rejetee => $this->notifications->demandeRejetee($demande),
            default => null,
        };

        return $demande;
    }
}
