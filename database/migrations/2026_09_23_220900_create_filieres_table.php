<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filieres', function (Blueprint $table) {
            $table->id('id_filiere');
            $table->string('nom_filiere', 100);
            // Pas de contrainte FK : même pattern que le reste du schéma legacy
            // (ecole.idEcole est un int signé, incompatible avec un foreignId unsigned).
            $table->integer('id_ecole');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filieres');
    }
};
