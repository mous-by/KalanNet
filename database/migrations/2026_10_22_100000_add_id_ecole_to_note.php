<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les types de notes appartiennent à une école (modèle Note, filtre par école) :
     * la colonne existait sur les bases installées depuis une sauvegarde, mais
     * aucune migration ne la créait — une installation neuve (git clone + migrate)
     * échouait. Sans effet si la colonne existe déjà.
     */
    public function up(): void
    {
        if (!Schema::hasTable('note') || Schema::hasColumn('note', 'id_ecole')) {
            return;
        }
        Schema::table('note', function (Blueprint $table) {
            $table->integer('id_ecole')->nullable()->index();
        });
    }

    public function down(): void
    {
        // Colonne utilisée par l'application : on ne la retire pas.
    }
};
