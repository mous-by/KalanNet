<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Frais d'adhésion payés une fois par une nouvelle école (annoncés dans le
     * message de bienvenue), réglés par le SupAdmin sur la page Abonnements.
     */
    public function up(): void
    {
        if (Schema::hasTable('frais_adhesion')) {
            return;
        }
        Schema::create('frais_adhesion', function (Blueprint $table) {
            $table->id();
            $table->string('statut', 20)->unique(); // public | prive
            $table->decimal('montant', 12, 2);
            $table->string('devise', 10)->default('XOF');
            $table->unsignedBigInteger('modifie_par')->nullable();
            $table->timestamps();
        });
        DB::table('frais_adhesion')->insert([
            ['statut' => 'public', 'montant' => 15000, 'devise' => 'XOF', 'created_at' => now(), 'updated_at' => now()],
            ['statut' => 'prive', 'montant' => 75000, 'devise' => 'XOF', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_adhesion');
    }
};
