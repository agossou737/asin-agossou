<?php

namespace App\Enums;

/**
 * Cycle de vie : deposee -> en_cours -> (validee | rejetee).
 * Une demande validee ou rejetee est dans un etat final.
 */
enum StatutDemande: string
{
    case Deposee = 'deposee';
    case EnCours = 'en_cours';
    case Validee = 'validee';
    case Rejetee = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::Deposee => 'Déposée',
            self::EnCours => 'En cours de traitement',
            self::Validee => 'Validée',
            self::Rejetee => 'Rejetée',
        };
    }

    /** Couleur Bootstrap utilisee par l'interface. */
    public function couleur(): string
    {
        return match ($this) {
            self::Deposee => 'secondary',
            self::EnCours => 'warning',
            self::Validee => 'success',
            self::Rejetee => 'danger',
        };
    }

    /** @return list<self> */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::Deposee => [self::EnCours],
            self::EnCours => [self::Validee, self::Rejetee],
            self::Validee, self::Rejetee => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles(), true);
    }

    public function estFinal(): bool
    {
        return $this->transitionsPossibles() === [];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
