<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Note de classe d'une matière pour une période (trimestre ou mois) :
     * moyenne de tous les devoirs (par défaut, sans ligne) ou un seul devoir,
     * choisi par l'enseignant ou par un utilisateur autorisé.
     */
    public function up(): void
    {
        if (Schema::hasTable('note_classe_choix')) {
            return;
        }

        Schema::create('note_classe_choix', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_classe');
            $table->unsignedInteger('id_matiere');
            $table->unsignedInteger('id_annee');
            $table->unsignedInteger('id_trimestre')->nullable();
            $table->unsignedTinyInteger('mois')->nullable();
            $table->string('mode', 10)->default('moyenne');
            $table->unsignedInteger('id_evaluation')->nullable();
            $table->unsignedInteger('modifie_par')->nullable();
            $table->timestamps();
            $table->unique(['id_classe', 'id_matiere', 'id_annee', 'id_trimestre', 'mois'], 'note_classe_choix_periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_classe_choix');
    }
};
