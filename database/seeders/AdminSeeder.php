<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /** Cree (ou met a jour) le compte administrateur : ADMIN_EMAIL / ADMIN_PASSWORD du fichier .env. */
    public function run(): void
    {
        $email = (string) config('demandes.admin_email');
        $motDePasse = (string) (config('demandes.admin_password') ?: Str::password(16, symbols: false));

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->name = 'Administrateur';
        $admin->password = $motDePasse; // hache automatiquement (cast « hashed »)
        $admin->is_admin = true;        // jamais en mass-assignment
        $admin->save();

        $this->command?->info("Compte administrateur : {$email}");
        if (! config('demandes.admin_password')) {
            $this->command?->warn("Mot de passe généré (à noter, il ne sera plus affiché) : {$motDePasse}");
        }
    }
}
