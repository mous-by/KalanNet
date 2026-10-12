<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Circuit académie → CAP → écoles : une évaluation du DAE est adressée à
     * des CAP ; chaque DCAP la publie ensuite à ses écoles (date, auteur et
     * consignes propres au CAP enregistrés ici).
     */
    public function up(): void
    {
        Schema::table('evaluation_commune_caps', function (Blueprint $table) {
            $table->dateTime('relayee_at')->nullable();
            $table->unsignedInteger('relayee_par')->nullable();
            $table->text('consignes_cap')->nullable();
        });

        // Évaluations d'académie existantes sans CAP précisé : adressées à tous ses CAP.
        $sansCap = DB::table('evaluations_communes as e')
            ->where('e.origine', 'academie')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('evaluation_commune_caps as c')->whereColumn('c.id_evaluation', 'e.id'))
            ->get(['e.id', 'e.id_academie']);
        foreach ($sansCap as $evaluation) {
            foreach (DB::table('cap')->where('id_academie', $evaluation->id_academie)->pluck('id_cap') as $idCap) {
                DB::table('evaluation_commune_caps')->insert(['id_evaluation' => $evaluation->id, 'id_cap' => $idCap]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('evaluation_commune_caps', function (Blueprint $table) {
            $table->dropColumn(['relayee_at', 'relayee_par', 'consignes_cap']);
        });
    }
};
