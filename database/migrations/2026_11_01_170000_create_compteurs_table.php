<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('installation_id')->nullable()->constrained('installations')->nullOnDelete();
            $table->string('reference', 50)->unique();
            $table->date('date_installation');
            $table->enum('statut', ['actif', 'inactif', 'en_panne'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compteurs');
    }
};
