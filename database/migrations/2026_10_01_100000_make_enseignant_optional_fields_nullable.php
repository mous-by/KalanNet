<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enregistrement d'un enseignant : seuls le nom-prénom et le téléphone
     * sont obligatoires, le reste peut être complété plus tard.
     */
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            $table->string('genre_enseignant', 50)->nullable()->change();
            $table->string('email_enseignant', 255)->nullable()->change();
            $table->string('date_naissance_enseignant', 100)->nullable()->change();
            $table->string('lieu_naissance_enseignant', 100)->nullable()->change();
            $table->string('diplome_enseignant', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Pas de retour en NOT NULL : des enseignants peuvent déjà avoir ces champs vides.
    }
};
