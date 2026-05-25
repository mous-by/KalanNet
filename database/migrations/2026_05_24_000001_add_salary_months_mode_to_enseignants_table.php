<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            if (!Schema::hasColumn('enseignants', 'salaire_mois_mode')) {
                $table->unsignedTinyInteger('salaire_mois_mode')->default(12)->after('salaire_enseignant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enseignants', function (Blueprint $table) {
            if (Schema::hasColumn('enseignants', 'salaire_mois_mode')) {
                $table->dropColumn('salaire_mois_mode');
            }
        });
    }
};
