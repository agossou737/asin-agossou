<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_actes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 20)->unique(); // numero de suivi communique a l'usager
            $table->char('npi', 10);
            $table->string('email')->nullable();    // facultatif
            $table->string('type_acte', 30);
            $table->unsignedTinyInteger('nombre_copies');
            $table->string('statut', 20)->default('deposee');
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            // Consultation par usager, filtre par statut, tri du plus recent au plus ancien.
            $table->index(['npi', 'statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_actes');
    }
};
