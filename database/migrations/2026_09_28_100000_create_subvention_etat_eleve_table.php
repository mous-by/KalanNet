<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique année par année : l'État paie souvent une subvention
        // plusieurs années plus tard, il faut donc savoir qui était
        // subventionné (et dans quelle classe) pour l'année concernée,
        // indépendamment de la situation actuelle de l'élève.
        Schema::create('subvention_etat_eleve', function (Blueprint $table) {
            $table->id();
            $table->integer('id_eleve');
            $table->integer('id_annee');
            $table->integer('id_classe');
            $table->integer('id_ecole');
            $table->timestamps();

            $table->unique(['id_eleve', 'id_annee']);
            $table->index(['id_ecole', 'id_annee']);
        });

        $eligibles = DB::table('eleve as e')
            ->join('classe as c', 'c.id_classe', '=', 'e.id_classe')
            ->join('ecole as ec', 'ec.idEcole', '=', 'e.id_ecole')
            ->where('e.statut_paiement', 'subventionne')
            ->where('c.ordreEnseignement', 'like', 'secondaire%')
            ->whereRaw("LOWER(TRIM(COALESCE(ec.statut, ''))) <> 'public'")
            ->whereNotNull('e.id_annee')
            ->get(['e.id_eleve', 'e.id_annee', 'e.id_classe', 'e.id_ecole']);

        foreach ($eligibles as $row) {
            DB::table('subvention_etat_eleve')->insertOrIgnore([
                'id_eleve' => $row->id_eleve,
                'id_annee' => $row->id_annee,
                'id_classe' => $row->id_classe,
                'id_ecole' => $row->id_ecole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subvention_etat_eleve');
    }
};
