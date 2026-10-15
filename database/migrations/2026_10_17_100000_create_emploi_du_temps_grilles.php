<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires des lignes et récréations de l'emploi du temps d'une classe.
 * Auparavant gardés dans la session du navigateur : perdus à la déconnexion,
 * sur un autre appareil et sur l'application mobile.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('emploi_du_temps_grilles')) {
            return;
        }

        Schema::create('emploi_du_temps_grilles', function (Blueprint $table) {
            $table->id();
            $table->integer('id_classe');
            $table->integer('id_annee_scolaire');
            $table->json('horaires')->nullable();     // {"08:00": {"debut": "07:30", "fin": "08:30"}, ...}
            $table->json('recreations')->nullable();  // {"10:00": 15, ...} minutes après la ligne
            $table->timestamps();
            $table->unique(['id_classe', 'id_annee_scolaire']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emploi_du_temps_grilles');
    }
};
