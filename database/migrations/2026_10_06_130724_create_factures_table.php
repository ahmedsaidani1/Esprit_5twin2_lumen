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
    Schema::create('factures', function (Blueprint $table) {
        $table->id();
        $table->foreignId('echange_id')->unique()->constrained('echanges')->cascadeOnDelete();
        $table->decimal('montant_total', 12, 2);
        $table->date('date_emission');
        $table->date('date_echeance');
        $table->enum('statut', ['payee', 'impayee', 'annulee'])->default('impayee');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
