<?php

namespace App\Models;

use App\Enums\StatutDemande;
use App\Enums\TypeActe;
use Database\Factories\DemandeActeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandeActe extends Model
{
    /** @use HasFactory<DemandeActeFactory> */
    use HasFactory, HasUuids;

    protected $table = 'demandes_actes';

    /** Alphabet sans caracteres ambigus (pas de 0/O, 1/I). */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Format d'un numero de suivi : DEM-20261006-7K3QX9 */
    public const FORMAT_NUMERO = '/^DEM-\d{8}-[A-HJ-NP-Z2-9]{6}$/';

    protected static function booted(): void
    {
        static::creating(function (self $demande) {
            if (blank($demande->numero)) {
                $demande->numero = self::genererNumero();
            }
        });
    }

    /** Genere un numero de suivi unique. */
    public static function genererNumero(): string
    {
        do {
            $suffixe = '';
            for ($i = 0; $i < 6; $i++) {
                $suffixe .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $numero = 'DEM-'.now()->format('Ymd').'-'.$suffixe;
        } while (self::query()->where('numero', $numero)->exists());

        return $numero;
    }

    protected $fillable = ['numero', 'npi', 'email', 'type_acte', 'nombre_copies', 'statut', 'motif_rejet'];

    protected function casts(): array
    {
        return [
            'type_acte' => TypeActe::class,
            'statut' => StatutDemande::class,
            'nombre_copies' => 'integer',
        ];
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(DemandeHistorique::class, 'demande_id')->orderBy('id');
    }

    /** Email partiellement masque pour l'affichage public : u***@exemple.com */
    public function emailMasque(): ?string
    {
        if (blank($this->email)) {
            return null;
        }

        [$local, $domaine] = array_pad(explode('@', (string) $this->email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$domaine;
    }
}
