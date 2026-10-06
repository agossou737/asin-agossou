<?php

namespace App\Http\Requests;

use App\Enums\StatutDemande;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangerStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Une demande ne revient jamais a « deposee » : seuls les statuts suivants sont acceptes.
            'statut' => ['required', Rule::enum(StatutDemande::class)->except(StatutDemande::Deposee)],
            // Un rejet doit toujours etre motive.
            'motif_rejet' => ['required_if:statut,'.StatutDemande::Rejetee->value, 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le nouveau statut est obligatoire.',
            'statut.enum' => 'Statut invalide. Valeurs possibles : en_cours, validee, rejetee.',
            'motif_rejet.required_if' => 'Un rejet doit toujours être motivé : veuillez saisir le motif.',
            'motif_rejet.max' => 'Le motif ne doit pas dépasser 1000 caractères.',
        ];
    }
}
