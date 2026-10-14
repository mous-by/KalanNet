<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Groupe de la matière dans la classe pour le bulletin « Matières
     * principales / secondaires » (secondaire). Vide : principale si le
     * coefficient est d'au moins 2, secondaire sinon.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('ligneclasse', 'groupe')) {
            Schema::table('ligneclasse', fn (Blueprint $table) => $table->string('groupe', 12)->nullable());
        }

        // Le modèle « Moderne » est remplacé : les classes qui l'avaient choisi reviennent au modèle par défaut.
        DB::table('classe')->where('modele_bulletin', 'moderne')->update(['modele_bulletin' => null]);
    }

    public function down(): void
    {
        Schema::table('ligneclasse', fn (Blueprint $table) => $table->dropColumn('groupe'));
    }
};
