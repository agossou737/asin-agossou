<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protege l'API de traitement : jeton Bearer compare en temps constant. */
class VerifierTokenAdminApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $attendu = (string) config('demandes.api_token');
        $fourni = (string) $request->bearerToken();

        if ($attendu === '' || $fourni === '' || ! hash_equals($attendu, $fourni)) {
            return response()
                ->json(['message' => 'Accès refusé : authentification administrateur requise.'], 401)
                ->header('WWW-Authenticate', 'Bearer');
        }

        return $next($request);
    }
}
