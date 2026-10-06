<?php

namespace App\Http\Requests;

use App\Models\DemandeActe;
use Illuminate\Foundation\Http\FormRequest;

class SuiviDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Le numero vient de l'URL : on le normalise (majuscules) puis on le valide comme un champ. */
    protected function prepareForValidation(): void
    {
        $this->merge(['numero' => mb_strtoupper(trim((string) $this->route('numero')))]);
    }

    public function rules(): array
    {
        return [
            'numero' => ['required', 'regex:'.DemandeActe::FORMAT_NUMERO],
        ];
    }

    public function messages(): array
    {
        return [
            'numero.regex' => 'Le numéro de demande est invalide. Format attendu : DEM-20261006-ABC123.',
        ];
    }
}
