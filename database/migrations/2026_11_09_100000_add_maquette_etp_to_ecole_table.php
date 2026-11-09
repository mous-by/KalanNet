<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Maquette officielle des rapports du technique et professionnel choisie par l'école (est, esp, esp_pro, agro). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ecole', 'maquette_etp')) {
            Schema::table('ecole', fn (Blueprint $table) => $table->string('maquette_etp', 20)->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecole', 'maquette_etp')) {
            Schema::table('ecole', fn (Blueprint $table) => $table->dropColumn('maquette_etp'));
        }
    }
};
