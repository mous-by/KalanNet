<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Proposition de passage, redoublement, exclusion : une fiche par classe et par année. `lignes` est le brouillon de
     * l'école, `lignes_envoyees` la version remise au CAP (avec sa décision). Permission donnée aux Admin déjà en place.
     */
    public function up(): void
    {
        Schema::create('propositions_passage', function (Blueprint $table) {
            $table->id();
            $table->integer('id_ecole');
            $table->integer('id_annee');
            $table->integer('id_classe');
            $table->integer('id_cap')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon, envoye, remis (sur papier), validee (par le CAP)
            $table->string('directeur', 150)->nullable();
            $table->json('lignes');
            $table->json('lignes_envoyees')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->integer('envoye_par')->nullable();
            $table->timestamp('valide_at')->nullable();
            $table->integer('valide_par')->nullable();
            $table->timestamps();
            $table->unique(['id_ecole', 'id_annee', 'id_classe']);
            $table->index(['id_cap', 'id_annee', 'statut']);
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(['name' => 'proposition_passage_gerer'], ['name' => 'proposition_passage_gerer']);
            $id = DB::table('permissions')->where('name', 'proposition_passage_gerer')->value('id');
            DB::table('utilisateurs')->where('droit', 'Admin')->pluck('idUtilisateur')->each(
                fn ($user) => DB::table('user_permission')->updateOrInsert(['user_id' => $user, 'permission_id' => $id])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('propositions_passage');
        $id = DB::table('permissions')->where('name', 'proposition_passage_gerer')->value('id');
        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
