<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_esg', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ecole');
            $table->unsignedBigInteger('id_annee');
            $table->string('type', 20); // rentree | fin
            $table->unsignedBigInteger('id_academie')->nullable();
            $table->string('statut', 20); // envoye | remis
            $table->string('fichier')->nullable();
            $table->string('fichier_nom')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->unsignedBigInteger('envoye_par')->nullable();
            $table->timestamps();
            $table->unique(['id_ecole', 'id_annee', 'type']);
            $table->index(['id_academie', 'id_annee', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports_esg');
    }
};
