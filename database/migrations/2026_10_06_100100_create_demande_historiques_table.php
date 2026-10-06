<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demande_historiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('demande_id')->constrained('demandes_actes')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 10); // depot | admin | api
            $table->string('ancien_statut', 20)->nullable();
            $table->string('nouveau_statut', 20);
            $table->text('motif')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demande_historiques');
    }
};
