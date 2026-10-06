<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemandeActe;
use App\Services\PdfDemandeService;
use Illuminate\Http\Response;

/** Telechargement du PDF d'une demande validee depuis l'espace admin. */
class DemandePdfController extends Controller
{
    public function __invoke(string $id, PdfDemandeService $pdf): Response
    {
        return $pdf->telechargement(DemandeActe::query()->findOrFail($id));
    }
}
