<?php

namespace App\Http\Requests;

use App\Enums\TypeActe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeposerDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'npi' => ['required', 'digits:10'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'type_acte' => ['required', Rule::enum(TypeActe::class)],
            'nombre_copies' => ['required', 'integer', 'between:1,5'],
        ];
    }

    public function messages(): array
    {
        return [
            'npi.required' => 'Le NPI est obligatoire.',
            'npi.digits' => 'Le NPI doit comporter exactement 10 chiffres.',
            'email.email' => "L'adresse email n'est pas valide.",
            'email.max' => "L'adresse email ne doit pas dépasser 255 caractères.",
            'type_acte.required' => "Le type d'acte est obligatoire.",
            'type_acte.enum' => "Le type d'acte doit être : acte de naissance, casier judiciaire ou certificat de résidence.",
            'nombre_copies.required' => 'Le nombre de copies est obligatoire.',
            'nombre_copies.integer' => 'Le nombre de copies doit être un nombre entier.',
            'nombre_copies.between' => 'Le nombre de copies doit être compris entre 1 et 5.',
        ];
    }
}
