<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consommations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compteur_id')->constrained('compteurs')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('energie_consommee_kwh', 10, 2);
            $table->timestamps();

            $table->unique(['compteur_id', 'date']); // un seul relevé par compteur et par jour
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consommations');
    }
};
