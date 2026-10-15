<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accès d'assistance soumis à l'accord d'un Admin de l'école : la demande
 * expire après 2 minutes sans réponse.
 * Statuts : en_attente -> accepte -> en_cours -> termine ; ou refuse / expire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acces_assistance', function (Blueprint $table) {
            if (!Schema::hasColumn('acces_assistance', 'statut')) {
                $table->string('statut', 20)->default('en_attente')->after('motif');
                $table->dateTime('expire_demande_at')->nullable()->after('statut');
                $table->integer('decide_par')->nullable()->after('expire_demande_at');
                $table->dateTime('decide_at')->nullable()->after('decide_par');
                $table->index(['id_ecole', 'statut']);
            }
        });
        // Accès ouverts avant l'approbation : déjà terminés.
        \Illuminate\Support\Facades\DB::table('acces_assistance')->where('statut', 'en_attente')->whereNotNull('fin_at')->update(['statut' => 'termine']);
    }

    public function down(): void
    {
        Schema::table('acces_assistance', function (Blueprint $table) {
            $table->dropIndex(['id_ecole', 'statut']);
            $table->dropColumn(['statut', 'expire_demande_at', 'decide_par', 'decide_at']);
        });
    }
};
