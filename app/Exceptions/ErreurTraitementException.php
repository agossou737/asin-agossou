<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/** Erreur technique inattendue (base de donnees...) : message propre pour l'utilisateur, detail dans les logs. */
class ErreurTraitementException extends Exception
{
    public function __construct(string $message = 'Une erreur technique est survenue. Veuillez réessayer dans un instant.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 500);
    }
}
