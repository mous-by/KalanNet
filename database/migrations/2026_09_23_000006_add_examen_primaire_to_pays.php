<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plusieurs pays (hors Mali) ont un 3e examen national, en fin de
        // primaire (6e annee, type CEPE) -- en plus des deux deja geres
        // (intermediaire, final). Nullable et non configure pour le Mali,
        // qui n'a pas cet examen (Fondamentale I n'en a jamais eu besoin).
        Schema::table('pays', function (Blueprint $table) {
            $table->unsignedTinyInteger('niveau_examen_primaire')->nullable()->after('actif');
            $table->string('nom_examen_primaire', 30)->nullable()->after('niveau_examen_primaire');
        });
    }

    public function down(): void
    {
        Schema::table('pays', function (Blueprint $table) {
            $table->dropColumn(['niveau_examen_primaire', 'nom_examen_primaire']);
        });
    }
};
