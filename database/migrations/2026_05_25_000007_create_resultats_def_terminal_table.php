<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('resultats_def_terminal')) {
            return;
        }

        Schema::create('resultats_def_terminal', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_eleve')->nullable()->index();
            $table->unsignedInteger('id_annee')->nullable()->index();
            $table->enum('niveau_examen', ['DEF', 'BAC'])->nullable()->index();
            $table->string('decision', 20)->nullable()->index();
            $table->float('moyenne', 5, 2)->nullable();
            $table->string('observation', 255)->nullable();
            $table->date('date_resultat')->nullable();
            $table->unsignedInteger('id_classe')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultats_def_terminal');
    }
};
