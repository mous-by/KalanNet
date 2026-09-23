<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pays')) {
            return;
        }

        Schema::create('pays', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('code_iso', 2)->unique(); // ISO 3166-1 alpha-2 (ML, GN...)
            $table->string('indicatif_telephone', 6); // +223, +224...
            $table->unsignedTinyInteger('telephone_longueur'); // nombre de chiffres locaux attendus (hors indicatif)
            $table->string('telephone_premier_chiffre_min', 1)->nullable(); // ex: Mali exige un 1er chiffre >= 2
            $table->string('devise_code', 3); // ISO 4217 (XOF, GNF...)
            $table->string('devise_symbole', 10); // libelle affiche (FCFA, FG...)
            $table->unsignedTinyInteger('devise_decimales')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pays');
    }
};
