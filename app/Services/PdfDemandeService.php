<?php

namespace App\Services;

use App\Enums\StatutDemande;
use App\Exceptions\DocumentIndisponibleException;
use App\Exceptions\ErreurTraitementException;
use App\Models\DemandeActe;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Generation du PDF « recapitulatif de demande validee » (dompdf). */
class PdfDemandeService
{
    /**
     * Contenu binaire du PDF.
     *
     * @throws Throwable si dompdf n'est pas installe ou si le rendu echoue
     */
    public function generer(DemandeActe $demande): string
    {
        $demande->loadMissing('historiques');

        $validation = $demande->historiques
            ->filter(fn ($h) => $h->nouveau_statut === StatutDemande::Validee)
            ->last();

        $html = view('pdf.demande', [
            'demande' => $demande,
            'dateValidation' => $validation?->created_at ?? $demande->updated_at,
            'genereLe' => now(),
        ])->render();

        $dossierTemp = storage_path('app/dompdf');
        if (! is_dir($dossierTemp)) {
            mkdir($dossierTemp, 0775, true);
        }

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans'); // accents et guillemets francais
        $options->set('isRemoteEnabled', false);      // aucune ressource externe
        $options->set('tempDir', $dossierTemp);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Reponse de telechargement HTTP (verifie que la demande est validee).
     *
     * @throws DocumentIndisponibleException
     * @throws ErreurTraitementException
     */
    public function telechargement(DemandeActe $demande): Response
    {
        if ($demande->statut !== StatutDemande::Validee) {
            throw new DocumentIndisponibleException;
        }

        try {
            $contenu = $this->generer($demande);
        } catch (Throwable $e) {
            Log::error('Échec de la génération du PDF', ['demande' => $demande->id, 'erreur' => $e->getMessage()]);
            throw new ErreurTraitementException;
        }

        return response($contenu, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="demande-'.$demande->numero.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
