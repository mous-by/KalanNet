<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Évaluations communes : créées par un DAE (académie, ou certains CAP) ou
     * un DCAP (son CAP), avec un sujet confidentiel libéré à une heure donnée,
     * des accusés de réception par école et un corrigé publié après l'épreuve.
     */
    public function up(): void
    {
        Schema::create('evaluations_communes', function (Blueprint $table) {
            $table->id();
            $table->string('origine', 10); // 'academie' (DAE) | 'cap' (DCAP)
            $table->unsignedInteger('id_academie')->nullable();
            $table->unsignedInteger('id_cap')->nullable();
            $table->unsignedInteger('id_utilisateur');
            $table->string('titre');
            $table->unsignedInteger('id_classe_officielle')->nullable();
            $table->string('niveau_libelle')->nullable();
            $table->unsignedInteger('id_matiere')->nullable();
            $table->string('matiere_libelle')->nullable();
            $table->dateTime('debut_at');
            $table->unsignedSmallInteger('duree_minutes');
            $table->dateTime('liberation_sujet_at');
            $table->text('consignes')->nullable();
            $table->string('sujet_fichier');
            $table->string('sujet_nom');
            $table->string('corrige_fichier')->nullable();
            $table->string('corrige_nom')->nullable();
            $table->string('statut', 10)->default('publiee'); // publiee | annulee
            $table->timestamps();

            $table->index(['origine', 'id_academie']);
            $table->index(['origine', 'id_cap']);
        });

        Schema::create('evaluation_commune_caps', function (Blueprint $table) {
            $table->unsignedBigInteger('id_evaluation');
            $table->unsignedInteger('id_cap');
            $table->primary(['id_evaluation', 'id_cap']);
        });

        Schema::create('evaluation_commune_accuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_evaluation');
            $table->unsignedInteger('id_ecole');
            $table->unsignedInteger('id_utilisateur');
            $table->dateTime('consulte_at')->nullable();
            $table->dateTime('telecharge_at')->nullable();
            $table->timestamps();
            $table->unique(['id_evaluation', 'id_ecole']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_commune_accuses');
        Schema::dropIfExists('evaluation_commune_caps');
        Schema::dropIfExists('evaluations_communes');
    }
};
