<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatutDemande;
use App\Exceptions\ErreurTraitementException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangerStatutRequest;
use App\Http\Requests\DeposerDemandeRequest;
use App\Http\Requests\ListerDemandesRequest;
use App\Http\Requests\SuiviDemandeRequest;
use App\Http\Resources\AdminDemandeActeResource;
use App\Http\Resources\DemandeActeResource;
use App\Models\DemandeActe;
use App\Services\DemandeService;
use App\Services\PdfDemandeService;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DemandeActeController extends Controller
{
    public function __construct(private readonly DemandeService $demandes) {}

    /** POST /api/demandes : deposer une demande (409 si ce NPI a deja une demande de ce type). */
    public function store(DeposerDemandeRequest $request): JsonResponse
    {
        $demande = $this->demandes->deposer($request->validated());

        return (new DemandeActeResource($demande))
            ->additional(['message' => 'Votre demande a été déposée avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/usagers/{npi}/demandes : demandes d'un usager (+ compteurs par statut). */
    public function indexParUsager(ListerDemandesRequest $request, string $npi): JsonResponse
    {
        $statut = $request->filled('statut') ? StatutDemande::from($request->input('statut')) : null;
        $parPage = (int) $request->input('per_page', DemandeService::PAR_PAGE_MAX);

        try {
            $page = $this->demandes->lister($npi, $statut, $parPage);
            $compteurs = $this->demandes->compteursParStatut($npi);
        } catch (QueryException $e) {
            Log::error('Échec de la consultation des demandes', ['npi' => $npi, 'erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        return DemandeActeResource::collection($page)
            ->additional(['compteurs' => $compteurs])
            ->response();
    }

    /** GET /api/suivi/{numero} : suivi public d'une demande par son numero. */
    public function suivi(SuiviDemandeRequest $request, string $numero): DemandeActeResource
    {
        try {
            $demande = $this->demandes->trouverParNumero($numero);
        } catch (QueryException $e) {
            Log::error('Échec du suivi par numéro', ['erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        abort_if($demande === null, 404);

        return new DemandeActeResource($demande);
    }

    /** GET /api/suivi/{numero}/pdf : telechargement du PDF d'une demande VALIDEE. */
    public function pdf(SuiviDemandeRequest $request, string $numero, PdfDemandeService $pdf): Response
    {
        try {
            $demande = $this->demandes->trouverParNumero($numero);
        } catch (QueryException $e) {
            Log::error('Échec du téléchargement du PDF', ['erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        abort_if($demande === null, 404);

        return $pdf->telechargement($demande);
    }

    /** GET /api/demandes/{id} : detail d'une demande (jeton administrateur requis). */
    public function show(string $id): AdminDemandeActeResource
    {
        return new AdminDemandeActeResource(DemandeActe::query()->with('historiques.acteur')->findOrFail($id));
    }

    /** PATCH /api/demandes/{id}/statut : faire avancer le cycle de vie (jeton administrateur requis). */
    public function changerStatut(ChangerStatutRequest $request, string $id): JsonResponse
    {
        $demande = $this->demandes->changerStatut(
            $id,
            StatutDemande::from($request->validated('statut')),
            $request->validated('motif_rejet'),
            null,
            'api',
        );

        return (new AdminDemandeActeResource($demande))
            ->additional(['message' => 'Le statut de la demande a été mis à jour : '.mb_strtolower($demande->statut->label()).'.'])
            ->response();
    }
}
