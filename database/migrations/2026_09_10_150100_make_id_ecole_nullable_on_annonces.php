<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL refuse de modifier une colonne utilisée par une clé étrangère :
        // on la retire, on rend la colonne nullable, puis on la restaure (une FK
        // MySQL accepte nativement les valeurs NULL, donc l'intégrité référentielle
        // reste garantie pour toute annonce rattachée à une école).
        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            $table->dropForeign('annonces_admin_gestionnaire_ibfk_2');
        });

        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            // null = annonce globale diffusée par le SupAdmin à toutes les écoles.
            // Signé, pour correspondre au type réel de ecole.idEcole (legacy) —
            // un type unsigned ferait échouer la FK (errno 150).
            $table->integer('id_ecole')->nullable()->change();
        });

        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            $table->foreign('id_ecole', 'annonces_admin_gestionnaire_ibfk_2')
                ->references('idEcole')->on('ecole');
        });
    }

    public function down(): void
    {
        // Les annonces globales existantes seraient orphelines sous la contrainte
        // NOT NULL — on les retire avant de revenir en arrière.
        DB::table('annonces_admin_gestionnaire')->whereNull('id_ecole')->delete();

        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            $table->dropForeign('annonces_admin_gestionnaire_ibfk_2');
        });

        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            $table->integer('id_ecole')->nullable(false)->change();
        });

        Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
            $table->foreign('id_ecole', 'annonces_admin_gestionnaire_ibfk_2')
                ->references('idEcole')->on('ecole');
        });
    }
};
