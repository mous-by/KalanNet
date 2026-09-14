<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matiere', function (Blueprint $table) {
            // Marque les matieres qui representent un choix de langue LV2
            // (Arabe LV2, Allemand LV2, Chinois LV2...) — permet au code de
            // savoir quand appliquer le filtre "choix de l'eleve" sans avoir
            // a lister ces matieres par nom un peu partout.
            $table->boolean('est_lv2')->default(false)->after('nom_matiere');
        });

        Schema::table('eleve', function (Blueprint $table) {
            // Langue LV2 choisie par l'eleve — null si pas encore choisie.
            // Signe (integer, pas unsignedInteger) pour correspondre au type
            // reel de matiere.id_matiere (int(11) signe, legacy).
            $table->integer('id_matiere_lv2')->nullable()->after('id_classe');
        });

        Schema::table('eleve', function (Blueprint $table) {
            $table->foreign('id_matiere_lv2')
                ->references('id_matiere')->on('matiere')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eleve', function (Blueprint $table) {
            $table->dropForeign(['id_matiere_lv2']);
        });

        Schema::table('eleve', function (Blueprint $table) {
            $table->dropColumn('id_matiere_lv2');
        });

        Schema::table('matiere', function (Blueprint $table) {
            $table->dropColumn('est_lv2');
        });
    }
};
