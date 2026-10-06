<?php

namespace App\Http\Resources;

use App\Models\DemandeActe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DemandeActe */
class DemandeActeResource extends JsonResource
{
    /** Public : email masque. Surcharge cote admin. */
    protected function affichageEmail(): ?string
    {
        return $this->emailMasque();
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'npi' => $this->npi,
            'email' => $this->affichageEmail(),
            'type_acte' => ['code' => $this->type_acte->value, 'libelle' => $this->type_acte->label()],
            'nombre_copies' => $this->nombre_copies,
            'statut' => [
                'code' => $this->statut->value,
                'libelle' => $this->statut->label(),
                'couleur' => $this->statut->couleur(),
            ],
            'motif_rejet' => $this->motif_rejet,
            'transitions_possibles' => array_map(
                fn ($s) => $s->value,
                $this->statut->transitionsPossibles(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
