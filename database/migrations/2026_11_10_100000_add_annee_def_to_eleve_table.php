<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Année d'obtention du DEF (« CG » : CG26 = DEF 2026) : la scolarité au lycée des rapports du secondaire (T9, T11) se
 * compte à partir d'elle (en 2026-2027, CG26 = 1 an, CG25 = 2 ans).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eleve', 'annee_def')) {
            Schema::table('eleve', fn (Blueprint $table) => $table->unsignedSmallInteger('annee_def')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('eleve', 'annee_def')) {
            Schema::table('eleve', fn (Blueprint $table) => $table->dropColumn('annee_def'));
        }
    }
};
