<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatutDemande;
use App\Exceptions\ErreurTraitementException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminListerDemandesRequest;
use App\Http\Requests\ChangerStatutRequest;
use App\Http\Resources\AdminDemandeActeResource;
use App\Models\DemandeActe;
use App\Services\DemandeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** API JSON de l'espace admin (session + CSRF, middleware auth + admin). */
class DemandeController extends Controller
{
    public function __construct(private readonly DemandeService $demandes) {}

    public function index(AdminListerDemandesRequest $request): JsonResponse
    {
        try {
            $page = $this->demandes->listerPourAdmin(
                $request->only(['statut', 'type_acte', 'npi', 'numero']),
                (int) $request->input('per_page', DemandeService::PAR_PAGE_MAX),
            );
            $stats = $this->demandes->statistiques();
        } catch (QueryException $e) {
            Log::error('Échec de la liste admin', ['erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        return AdminDemandeActeResource::collection($page)
            ->additional(['compteurs' => $stats['par_statut'], 'total_general' => $stats['total']])
            ->response();
    }

    public function show(string $id): AdminDemandeActeResource
    {
        $demande = DemandeActe::query()->with('historiques.acteur')->findOrFail($id);

        return new AdminDemandeActeResource($demande);
    }

    public function changerStatut(ChangerStatutRequest $request, string $id): JsonResponse
    {
        $demande = $this->demandes->changerStatut(
            $id,
            StatutDemande::from($request->validated('statut')),
            $request->validated('motif_rejet'),
            $request->user()->id,
            'admin',
        );

        return (new AdminDemandeActeResource($demande))
            ->additional(['message' => 'Le statut de la demande a été mis à jour : '.mb_strtolower($demande->statut->label()).'.'])
            ->response();
    }
}
