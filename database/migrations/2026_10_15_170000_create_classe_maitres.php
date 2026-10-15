<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maîtres d'une classe du Jardin (préscolaire) : une classe sans matières
 * peut avoir un, deux maîtres ou plus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classe_maitres')) {
            return;
        }

        Schema::create('classe_maitres', function (Blueprint $table) {
            $table->id();
            $table->integer('id_classe');
            $table->integer('id_enseignant');
            $table->unique(['id_classe', 'id_enseignant']);
            $table->index('id_enseignant');
            $table->foreign('id_classe')->references('id_classe')->on('classe')->cascadeOnDelete();
            $table->foreign('id_enseignant')->references('id_enseignant')->on('enseignants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classe_maitres');
    }
};
