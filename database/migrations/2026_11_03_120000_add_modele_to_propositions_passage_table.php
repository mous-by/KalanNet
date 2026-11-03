<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propositions_passage', function (Blueprint $table) {
            $table->string('modele', 20)->default('standard')->after('statut');
            $table->json('renseignements')->nullable()->after('directeur');
        });
    }

    public function down(): void
    {
        Schema::table('propositions_passage', function (Blueprint $table) {
            $table->dropColumn(['modele', 'renseignements']);
        });
    }
};
