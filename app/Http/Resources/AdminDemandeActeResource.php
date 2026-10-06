<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Vue administrateur : email complet et historique des changements de statut (si charge). */
class AdminDemandeActeResource extends DemandeActeResource
{
    protected function affichageEmail(): ?string
    {
        return $this->email;
    }

    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'historique' => $this->whenLoaded('historiques', fn () => $this->historiques->map(fn ($h) => [
                'source' => $h->source,
                'ancien_statut' => $h->ancien_statut?->label(),
                'nouveau_statut' => $h->nouveau_statut->label(),
                'motif' => $h->motif,
                'acteur' => $h->acteur?->name ?? ($h->source === 'depot' ? 'Usager' : ($h->source === 'api' ? 'API (jeton)' : 'Inconnu')),
                'date' => $h->created_at?->toIso8601String(),
            ])->all()),
        ];
    }
}
