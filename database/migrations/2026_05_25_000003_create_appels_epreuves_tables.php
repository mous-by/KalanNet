<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('controle_eleve')) {
            Schema::create('controle_eleve', function (Blueprint $table) {
                $table->id('id_controle_eleve');
                $table->unsignedBigInteger('id_eleve');
                $table->unsignedBigInteger('id_classe');
                $table->unsignedBigInteger('id_matiere');
                $table->unsignedBigInteger('id_annee_scolaire');
                $table->unsignedBigInteger('id_trimestre');
                $table->unsignedBigInteger('id_ecole');
                $table->date('date');
                $table->string('libelle', 255);
                $table->time('heure_debut');
                $table->time('heure_fin');
                $table->boolean('notifier_parent')->default(false);
                $table->unsignedBigInteger('id_controle');
                $table->timestamp('date_enregistrement')->useCurrent();

                $table->index(['id_ecole', 'id_classe', 'id_annee_scolaire', 'id_trimestre']);
                $table->index(['id_eleve', 'id_controle']);
            });
        }

        if (!Schema::hasTable('conduite')) {
            Schema::create('conduite', function (Blueprint $table) {
                $table->id('id_conduite');
                $table->unsignedBigInteger('id_annee_scolaire');
                $table->unsignedBigInteger('id_classe');
                $table->unsignedBigInteger('id_trimestre');
                $table->unsignedBigInteger('id_eleve');
                $table->float('note_conduite')->nullable();
                $table->unique(['id_annee_scolaire', 'id_classe', 'id_trimestre', 'id_eleve'], 'conduite_unique_period');
            });
        }

        if (Schema::hasTable('permissions')) {
            foreach (['controle_apercu', 'controle_creation', 'controle_création', 'controle_modification'] as $name) {
                DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
