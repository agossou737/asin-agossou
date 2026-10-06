<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifierTokenAdminApi;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'token.admin' => VerifierTokenAdminApi::class,
        ]);

        $middleware->append(SecurityHeaders::class);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toute erreur sur /api/*, /admin/api/* ou toute requete AJAX est renvoyee en JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'admin/api/*') || $request->expectsJson() || $request->ajax(),
        );

        // Filet de securite : aucune erreur technique (SQL, chemins, pile d'appels...) n'est jamais
        // renvoyee a l'utilisateur. Le detail reste dans storage/logs/laravel.log.
        $exceptions->render(function (Throwable $e, Request $request) {
            $json = $request->is('api/*', 'admin/api/*') || $request->expectsJson() || $request->ajax();

            // Erreurs de validation (422) et exceptions metier (409...) ont deja un message propre.
            if (! $json || $e instanceof ValidationException || method_exists($e, 'render')) {
                return null;
            }

            $headers = [];
            $message = 'Une erreur technique est survenue. Veuillez réessayer dans un instant.';
            $status = 500;

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                [$status, $message] = [404, 'Ressource introuvable.'];
            } elseif ($e instanceof AuthenticationException) {
                [$status, $message] = [401, 'Authentification requise. Veuillez vous connecter.'];
            } elseif ($e instanceof AuthorizationException) {
                [$status, $message] = [403, 'Accès refusé.'];
            } elseif ($e instanceof TokenMismatchException) {
                [$status, $message] = [419, 'Votre session a expiré. Veuillez recharger la page.'];
            } elseif ($e instanceof ThrottleRequestsException) {
                [$status, $message, $headers] = [429, 'Trop de tentatives. Veuillez patienter avant de réessayer.', $e->getHeaders()];
            } elseif ($e instanceof MethodNotAllowedHttpException) {
                [$status, $message] = [405, 'Méthode non autorisée.'];
            } elseif ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                $status = $e->getStatusCode();
                $message = $status === 403
                    ? (str_starts_with($e->getMessage(), 'Accès') ? $e->getMessage() : 'Accès refusé.')
                    : 'Requête invalide.';
            }

            return response()->json(['message' => $message], $status, $headers);
        });
    })->create();
