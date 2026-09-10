<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnement_offres', function (Blueprint $table) {
            // null = offre valable pour tout type d'école (comportement actuel).
            $table->enum('type_ecole_cible', ['public', 'prive'])->nullable()->after('duree_jours');
        });
    }

    public function down(): void
    {
        Schema::table('abonnement_offres', function (Blueprint $table) {
            $table->dropColumn('type_ecole_cible');
        });
    }
};
