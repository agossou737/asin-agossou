<?php

namespace App\Exceptions;

use App\Enums\StatutDemande;
use App\Models\DemandeActe;
use Exception;
use Illuminate\Http\JsonResponse;

/** Doublon : demande non rejetee du meme type deja existante pour ce NPI. Renvoyee en 409. */
class DemandeDejaExistanteException extends Exception
{
    public static function pour(DemandeActe $existante): self
    {
        $type = mb_strtolower($existante->type_acte->label());

        if ($existante->statut === StatutDemande::Validee) {
            return new self(sprintf(
                'Une demande de « %s » a déjà été validée pour ce NPI (demande %s). Il n\'est pas possible d\'en déposer une nouvelle.',
                $type,
                $existante->numero,
            ));
        }

        return new self(sprintf(
            'Une demande de « %s » est déjà en cours pour ce NPI (demande %s, statut : %s). Vous pourrez en déposer une nouvelle si elle est rejetée.',
            $type,
            $existante->numero,
            mb_strtolower($existante->statut->label()),
        ));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
