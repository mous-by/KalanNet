<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cahier de bord du secondaire : remis à l'académie. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cahiers_bord', function (Blueprint $table) {
            $table->unsignedBigInteger('id_academie')->nullable()->after('id_cap');
            $table->index(['id_academie', 'id_annee', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::table('cahiers_bord', function (Blueprint $table) {
            $table->dropIndex(['id_academie', 'id_annee', 'statut']);
            $table->dropColumn('id_academie');
        });
    }
};
