<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnement_offres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('nom', 120);
            $table->text('description')->nullable();
            $table->decimal('montant', 12, 2);
            $table->string('devise', 8)->default('XOF');
            $table->unsignedInteger('duree_jours')->default(30);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id');
            $table->foreignId('offre_id')->constrained('abonnement_offres');
            $table->string('statut', 30)->default('en_attente');
            $table->timestamp('debut_at')->nullable();
            $table->timestamp('fin_at')->nullable();
            $table->unsignedBigInteger('dernier_paiement_id')->nullable();
            $table->timestamps();

            $table->index(['ecole_id', 'statut', 'fin_at']);
        });

        Schema::create('abonnement_paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abonnement_id')->nullable()->constrained('abonnements')->nullOnDelete();
            $table->unsignedBigInteger('ecole_id');
            $table->foreignId('offre_id')->constrained('abonnement_offres');
            $table->string('fournisseur', 40);
            $table->string('reference', 80)->unique();
            $table->string('reference_fournisseur', 120)->nullable()->index();
            $table->string('numero_payeur', 40)->nullable();
            $table->decimal('montant', 12, 2);
            $table->string('devise', 8)->default('XOF');
            $table->string('statut', 30)->default('en_attente');
            $table->string('checkout_url', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paye_at')->nullable();
            $table->timestamps();

            $table->index(['ecole_id', 'statut']);
            $table->index(['fournisseur', 'statut']);
        });

        $this->seedDefaultOffers();
        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnement_paiements');
        Schema::dropIfExists('abonnements');
        Schema::dropIfExists('abonnement_offres');
    }

    private function seedDefaultOffers(): void
    {
        DB::table('abonnement_offres')->insertOrIgnore([
            [
                'code' => 'mensuel',
                'nom' => 'Abonnement mensuel',
                'description' => 'Accès complet à KalanNet pour un établissement pendant 30 jours.',
                'montant' => 15000,
                'devise' => 'XOF',
                'duree_jours' => 30,
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'trimestriel',
                'nom' => 'Abonnement trimestriel',
                'description' => 'Accès complet à KalanNet pour un établissement pendant 90 jours.',
                'montant' => 40000,
                'devise' => 'XOF',
                'duree_jours' => 90,
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'annuel',
                'nom' => 'Abonnement annuel',
                'description' => 'Accès complet à KalanNet pour un établissement pendant une année.',
                'montant' => 140000,
                'devise' => 'XOF',
                'duree_jours' => 365,
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['abonnements_apercu', 'abonnements_paiement', 'abonnements_configuration'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
};
