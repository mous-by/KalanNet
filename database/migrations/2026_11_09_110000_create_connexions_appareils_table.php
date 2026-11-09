<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Appareils déjà utilisés par chaque compte : une connexion depuis un appareil inconnu déclenche une alerte. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connexions_appareils', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('empreinte', 64);
            $table->string('appareil', 190);
            $table->string('canal', 10); // web, mobile
            $table->string('ip', 45)->nullable();
            $table->timestamp('premiere_connexion_at')->nullable();
            $table->timestamp('derniere_connexion_at')->nullable();
            $table->unique(['user_id', 'empreinte']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connexions_appareils');
    }
};
