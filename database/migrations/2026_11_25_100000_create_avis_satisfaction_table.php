<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avis de satisfaction sur KalanNet, demandés juste après une action réussie, et publications de ces avis
 * (témoignages, rapport) sur le site de Computer Service Barry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_satisfaction', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('id_ecole')->nullable();
            // repondu, plus_tard (redemandé plus tard), jamais (ne plus demander avant longtemps)
            $table->string('statut', 12);
            $table->unsignedTinyInteger('note')->nullable(); // 1 à 5
            $table->json('points')->nullable();              // ce qui plaît / ce qui gêne
            $table->text('commentaire')->nullable();
            $table->string('contexte', 60)->nullable();      // module de l'action qui vient de réussir
            $table->string('droit', 30)->nullable();         // rôle de l'auteur au moment de l'avis
            $table->boolean('accord_publication')->default(false);
            $table->timestamp('publie_at')->nullable();      // témoignage en ligne sur le site de CSB
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['statut', 'created_at']);
        });

        Schema::create('satisfaction_publications', function (Blueprint $table) {
            $table->id();
            $table->json('contenu');      // chiffres du rapport tels que publiés
            $table->integer('publie_par')->nullable();
            $table->timestamp('publie_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_publications');
        Schema::dropIfExists('avis_satisfaction');
    }
};
