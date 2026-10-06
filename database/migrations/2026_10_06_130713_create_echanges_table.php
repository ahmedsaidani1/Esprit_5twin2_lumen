<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('echanges', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendeur_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('acheteur_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('communaute_id')->constrained('communautes')->cascadeOnDelete();
        $table->decimal('quantite_kwh', 10, 2);
        $table->decimal('prix_unitaire', 10, 4);
        $table->dateTime('date_echange');
        $table->enum('statut', ['propose', 'accepte', 'refuse', 'termine'])->default('propose');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('echanges');
    }
};
