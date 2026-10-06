<?php

namespace App\Http\Requests;

use App\Enums\StatutDemande;
use App\Services\DemandeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListerDemandesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Le NPI vient de l'URL : on le valide comme un champ ordinaire. */
    protected function prepareForValidation(): void
    {
        $this->merge(['npi' => $this->route('npi')]);
    }

    public function rules(): array
    {
        return [
            'npi' => ['required', 'digits:10'],
            'statut' => ['nullable', Rule::enum(StatutDemande::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.DemandeService::PAR_PAGE_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'npi.digits' => 'Le NPI doit comporter exactement 10 chiffres.',
            'statut.enum' => 'Statut inconnu. Valeurs possibles : '.implode(', ', StatutDemande::values()).'.',
            'page.integer' => 'La page doit être un nombre entier.',
            'page.min' => 'La page doit être supérieure ou égale à 1.',
            'per_page.integer' => 'Le nombre de demandes par page doit être un entier.',
            'per_page.between' => 'Le nombre de demandes par page doit être compris entre 1 et '.DemandeService::PAR_PAGE_MAX.'.',
        ];
    }
}
