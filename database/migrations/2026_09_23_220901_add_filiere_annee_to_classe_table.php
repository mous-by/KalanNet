<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classe', function (Blueprint $table) {
            // Utilisés à la place de ordreEnseignement pour les classes d'une École de Santé
            // (une classe santé est définie par sa filière + son année, pas par un ordre
            // Fondamentale/Secondaire).
            $table->integer('id_filiere')->nullable()->after('id_classe_officielle');
            $table->unsignedTinyInteger('annee')->nullable()->after('id_filiere');
        });
    }

    public function down(): void
    {
        Schema::table('classe', function (Blueprint $table) {
            $table->dropColumn(['id_filiere', 'annee']);
        });
    }
};
