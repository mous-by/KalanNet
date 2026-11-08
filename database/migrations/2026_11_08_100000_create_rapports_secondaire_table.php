<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rapports de rentrée et de fin d'année du secondaire, remis à l'académie. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_secondaire', function (Blueprint $table) {
            $table->id();
            $table->integer('id_ecole');
            $table->integer('id_annee');
            $table->string('type', 20); // rentree, fin
            $table->unsignedBigInteger('id_academie')->nullable();
            $table->string('statut', 20)->default('brouillon'); // brouillon, envoye (académie sur KalanNet), remis
            $table->json('donnees');
            $table->json('donnees_envoyees')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->integer('envoye_par')->nullable();
            $table->timestamps();
            $table->unique(['id_ecole', 'id_annee', 'type']);
            $table->index(['id_academie', 'id_annee', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_secondaire');
    }
};
