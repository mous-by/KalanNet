<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cahiers_bord', function (Blueprint $table) {
            $table->id();
            $table->integer('id_ecole');
            $table->integer('id_annee');
            $table->string('cycle', 20); // jardin, premier, second
            $table->integer('id_cap')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon, envoye (CAP sur KalanNet), remis
            $table->json('donnees');
            $table->json('donnees_envoyees')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->integer('envoye_par')->nullable();
            $table->timestamps();
            $table->unique(['id_ecole', 'id_annee', 'cycle']);
            $table->index(['id_cap', 'id_annee', 'statut']);
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(['name' => 'cahier_bord_gerer'], ['name' => 'cahier_bord_gerer']);
            $id = DB::table('permissions')->where('name', 'cahier_bord_gerer')->value('id');
            DB::table('utilisateurs')->where('droit', 'Admin')->pluck('idUtilisateur')->each(
                fn ($user) => DB::table('user_permission')->updateOrInsert(['user_id' => $user, 'permission_id' => $id])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cahiers_bord');
        $id = DB::table('permissions')->where('name', 'cahier_bord_gerer')->value('id');
        if ($id) {
            DB::table('user_permission')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
    }
};
