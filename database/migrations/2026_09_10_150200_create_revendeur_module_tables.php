<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revendeurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 150);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('revendeur_offres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_revendeur')->constrained('revendeurs')->cascadeOnDelete();
            $table->foreignId('id_offre')->constrained('abonnement_offres')->cascadeOnDelete();
            // Prix que CE revendeur facture aux écoles qu'il apporte — distinct du
            // prix de gros (abonnement_offres.montant) fixé par le développeur.
            $table->decimal('montant_revente', 12, 2);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['id_revendeur', 'id_offre']);
        });

        // Colonnes de liaison "en dur", nullable, sans contrainte de clé étrangère —
        // même pattern que utilisateurs.id_enseignant / id_parent sur ce schéma
        // legacy (un vrai FK entre ces tables produit un conflit de type signé/non
        // signé avec ecole.idEcole).
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->integer('id_revendeur')->nullable()->after('id_parent');
        });

        Schema::table('ecole', function (Blueprint $table) {
            $table->integer('id_revendeur')->nullable()->after('id_cap');
        });

        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::table('ecole', function (Blueprint $table) {
            $table->dropColumn('id_revendeur');
        });

        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->dropColumn('id_revendeur');
        });

        Schema::dropIfExists('revendeur_offres');
        Schema::dropIfExists('revendeurs');
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['revendeur_apercu', 'revendeur_tarifs'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
};
