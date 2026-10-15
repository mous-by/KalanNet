<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des accès d'assistance : un SupAdmin se connecte au compte d'un
 * client pour résoudre un problème, sans connaître son mot de passe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('acces_assistance')) {
            return;
        }

        Schema::create('acces_assistance', function (Blueprint $table) {
            $table->id();
            $table->integer('id_superviseur');      // SupAdmin qui se connecte
            $table->integer('id_utilisateur');      // compte du client
            $table->integer('id_ecole')->nullable();
            $table->string('motif', 255);
            $table->string('ip', 45)->nullable();
            $table->dateTime('debut_at');
            $table->dateTime('fin_at')->nullable();
            $table->string('fin_raison', 20)->nullable(); // termine, expire, deconnexion
            $table->timestamps();
            $table->index('id_ecole');
            $table->index('id_superviseur');
            $table->index('id_utilisateur');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acces_assistance');
    }
};
