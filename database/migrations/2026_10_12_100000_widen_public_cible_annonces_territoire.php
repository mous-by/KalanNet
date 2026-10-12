<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Annonces de l'académie (DAE) et du CAP (DCAP) : public ciblé par rôle
     * (tous, admin, gestionnaires, enseignants, parents, dcap) au lieu des
     * trois valeurs d'origine. Les anciennes valeurs sont ramenées à « tous ».
     */
    public function up(): void
    {
        foreach (['annonces_academie', 'annonces_cap'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) {
                $t->string('public_cible', 50)->default('tous')->change();
            });
            DB::table($table)->whereIn('public_cible', ['cap_only', 'ecoles_only'])->update(['public_cible' => 'tous']);
        }
    }

    public function down(): void
    {
        // Pas de retour aux trois valeurs : des annonces ciblées existent peut-être.
    }
};
