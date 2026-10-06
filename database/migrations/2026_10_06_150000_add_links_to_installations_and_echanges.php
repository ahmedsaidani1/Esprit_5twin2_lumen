<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->foreignId('communaute_id')->nullable()->after('user_id')
                ->constrained('communautes')->nullOnDelete();
        });

        Schema::table('echanges', function (Blueprint $table) {
            $table->foreignId('offre_id')->nullable()->after('communaute_id')
                ->constrained('offres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('echanges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offre_id');
        });

        Schema::table('installations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('communaute_id');
        });
    }
};
