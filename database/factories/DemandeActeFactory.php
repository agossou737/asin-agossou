<?php

namespace Database\Factories;

use App\Enums\StatutDemande;
use App\Enums\TypeActe;
use App\Models\DemandeActe;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DemandeActe> */
class DemandeActeFactory extends Factory
{
    protected $model = DemandeActe::class;

    public function definition(): array
    {
        return [
            'npi' => (string) fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'type_acte' => fake()->randomElement(TypeActe::cases()),
            'nombre_copies' => fake()->numberBetween(1, 5),
            'statut' => StatutDemande::Deposee,
            'motif_rejet' => null,
        ];
    }

    public function pourNpi(string $npi): static
    {
        return $this->state(fn () => ['npi' => $npi]);
    }

    public function statut(StatutDemande $statut): static
    {
        return $this->state(fn () => [
            'statut' => $statut,
            'motif_rejet' => $statut === StatutDemande::Rejetee ? 'Pièces justificatives illisibles.' : null,
        ]);
    }
}
