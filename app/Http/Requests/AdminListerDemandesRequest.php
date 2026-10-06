<?php

namespace App\Http\Requests;

use App\Enums\StatutDemande;
use App\Enums\TypeActe;
use App\Services\DemandeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminListerDemandesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'statut' => ['nullable', Rule::enum(StatutDemande::class)],
            'type_acte' => ['nullable', Rule::enum(TypeActe::class)],
            'npi' => ['nullable', 'digits_between:1,10'],
            'numero' => ['nullable', 'regex:/^[A-Za-z0-9-]{1,20}$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,'.DemandeService::PAR_PAGE_MAX],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.enum' => 'Statut inconnu.',
            'type_acte.enum' => "Type d'acte inconnu.",
            'npi.digits_between' => 'Le NPI recherché ne doit contenir que des chiffres (10 au maximum).',
            'numero.regex' => 'Le numéro de demande ne doit contenir que des lettres, chiffres et tirets.',
            'page.integer' => 'La page doit être un nombre entier.',
            'per_page.between' => 'Le nombre de demandes par page doit être compris entre 1 et '.DemandeService::PAR_PAGE_MAX.'.',
        ];
    }
}
