<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Séries du secondaire général (11ème : Sciences, SES, LL ; 12ème : TExp,
     * TSE, TSS, TSECO, LAT, LL) et matières qui orientent vers chaque série,
     * réglables par école, pour la tendance d'orientation du bulletin.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('classe', 'serie')) {
            Schema::table('classe', fn (Blueprint $table) => $table->string('serie', 10)->nullable());
        }

        if (!Schema::hasTable('series_matieres')) {
            Schema::create('series_matieres', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_ecole');
                $table->string('serie', 10);
                $table->unsignedInteger('id_matiere');
                $table->timestamps();
                $table->unique(['id_ecole', 'serie', 'id_matiere']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('series_matieres');
        Schema::table('classe', fn (Blueprint $table) => $table->dropColumn('serie'));
    }
};
