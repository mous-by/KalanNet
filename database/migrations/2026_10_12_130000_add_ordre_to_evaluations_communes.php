<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ordre d'enseignement visé : le fondamental passe par les CAP, le
     * secondaire va directement aux établissements de l'académie.
     */
    public function up(): void
    {
        Schema::table('evaluations_communes', function (Blueprint $table) {
            $table->string('ordre_enseignement', 60)->nullable()->after('id_classe_officielle');
        });

        foreach (DB::table('evaluations_communes')->whereNotNull('id_classe_officielle')->get(['id', 'id_classe_officielle']) as $e) {
            DB::table('evaluations_communes')->where('id', $e->id)->update([
                'ordre_enseignement' => DB::table('classes_officielles')->where('id_classe_officielle', $e->id_classe_officielle)->value('ordre_enseignement'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('evaluations_communes', fn (Blueprint $table) => $table->dropColumn('ordre_enseignement'));
    }
};
