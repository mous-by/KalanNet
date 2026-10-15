<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Fin de validité du mot de passe temporaire (72 heures après sa création). */
    public function up(): void
    {
        if (!Schema::hasColumn('utilisateurs', 'mdp_temporaire_expire_at')) {
            Schema::table('utilisateurs', fn (Blueprint $table) => $table->dateTime('mdp_temporaire_expire_at')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('utilisateurs', fn (Blueprint $table) => $table->dropColumn('mdp_temporaire_expire_at'));
    }
};
