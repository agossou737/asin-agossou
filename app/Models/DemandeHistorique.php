<?php

namespace App\Models;

use App\Enums\StatutDemande;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Journal d'audit : qui a fait passer une demande de quel statut a quel statut, et quand. */
class DemandeHistorique extends Model
{
    use HasUuids;

    protected $table = 'demande_historiques';

    protected $fillable = ['demande_id', 'user_id', 'source', 'ancien_statut', 'nouveau_statut', 'motif'];

    protected function casts(): array
    {
        return [
            'ancien_statut' => StatutDemande::class,
            'nouveau_statut' => StatutDemande::class,
        ];
    }

    public function acteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
