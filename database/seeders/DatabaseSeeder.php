<?php

namespace Database\Seeders;

use App\Enums\StatutDemande;
use App\Enums\TypeActe;
use App\Models\DemandeActe;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Donnees de demonstration : NPI 0123456789 et 9876543210 (une demande max par type d'acte). */
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        $jeux = [
            '0123456789' => [StatutDemande::Deposee, StatutDemande::EnCours, StatutDemande::Validee],
            '9876543210' => [StatutDemande::Rejetee, StatutDemande::EnCours, StatutDemande::Deposee],
        ];

        foreach ($jeux as $npi => $statuts) {
            foreach (TypeActe::cases() as $i => $type) {
                DemandeActe::factory()->pourNpi((string) $npi)->statut($statuts[$i])->create(['type_acte' => $type]);
            }
        }
    }
}
