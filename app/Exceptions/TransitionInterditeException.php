<?php

namespace App\Exceptions;

use App\Enums\StatutDemande;
use Exception;
use Illuminate\Http\JsonResponse;

/** Action interdite par le cycle de vie : renvoyee en 409 avec un message clair. */
class TransitionInterditeException extends Exception
{
    public static function entre(StatutDemande $actuel, StatutDemande $cible): self
    {
        if ($actuel->estFinal()) {
            return new self(sprintf(
                'Cette demande est déjà « %s » : son statut ne peut plus changer.',
                mb_strtolower($actuel->label()),
            ));
        }

        $autorisees = implode(' ou ', array_map(
            fn (StatutDemande $s) => '« '.mb_strtolower($s->label()).' »',
            $actuel->transitionsPossibles(),
        ));

        return new self(sprintf(
            'Transition interdite : une demande « %s » ne peut pas passer à « %s ». Statut autorisé : %s.',
            mb_strtolower($actuel->label()),
            mb_strtolower($cible->label()),
            $autorisees,
        ));
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
