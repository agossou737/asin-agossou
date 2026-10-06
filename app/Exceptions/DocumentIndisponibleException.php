<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/** Le PDF n'existe que pour une demande validee. Renvoyee en 409. */
class DocumentIndisponibleException extends Exception
{
    public function __construct(string $message = "Le document n'est disponible que pour une demande validée.")
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
