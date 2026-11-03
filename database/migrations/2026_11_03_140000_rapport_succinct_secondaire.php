<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** Rapport succinct du secondaire, remis à l'académie : une fiche par établissement, année et portée. Les canevas Excel sont retirés. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapports_succincts', function (Blueprint $table) {
            $table->string('portee', 20)->default('fondamental')->after('id_annee');
            $table->unsignedBigInteger('id_academie')->nullable()->after('id_cap');
            $table->dropUnique(['id_ecole', 'id_annee']);
            $table->unique(['id_ecole', 'id_annee', 'portee']);
            $table->index(['id_academie', 'id_annee', 'statut']);
        });

        Schema::dropIfExists('rapports_esg');
        Storage::disk('local')->deleteDirectory('rapports-esg');
    }

    public function down(): void
    {
        Schema::table('rapports_succincts', function (Blueprint $table) {
            $table->dropIndex(['id_academie', 'id_annee', 'statut']);
            $table->dropUnique(['id_ecole', 'id_annee', 'portee']);
            $table->unique(['id_ecole', 'id_annee']);
            $table->dropColumn(['portee', 'id_academie']);
        });
    }
};
