<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained('installations')->cascadeOnDelete();
            $table->decimal('quantite_kwh', 10, 2);
            $table->decimal('prix_kwh', 10, 4);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', ['disponible', 'reservee', 'expiree'])->default('disponible');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
