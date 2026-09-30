<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cas social « Autre » : l'élève paie, pour l'année, un frais fixé par
     * l'école à la place du montant de sa formule. Gardé année par année sur
     * la ligne d'inscription (comme la formule elle-même).
     */
    public function up(): void
    {
        Schema::table('ligne_inscription', function (Blueprint $table) {
            if (!Schema::hasColumn('ligne_inscription', 'cas_social_nature')) {
                $table->string('cas_social_nature', 255)->nullable();
            }
            if (!Schema::hasColumn('ligne_inscription', 'montant_cas_social')) {
                $table->decimal('montant_cas_social', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ligne_inscription', function (Blueprint $table) {
            $table->dropColumn(['cas_social_nature', 'montant_cas_social']);
        });
    }
};
