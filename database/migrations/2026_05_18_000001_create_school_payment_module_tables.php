<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_scolaires', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id');
            $table->unsignedBigInteger('classe_id')->nullable();
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->string('type_frais', 80);
            $table->decimal('montant', 12, 2)->default(0);
            $table->boolean('obligatoire')->default(true);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['ecole_id', 'classe_id', 'annee_scolaire_id', 'type_frais'], 'frais_scolaires_unique_scope');
            $table->index(['ecole_id', 'annee_scolaire_id', 'actif']);
        });

        Schema::create('reduction_paiement_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id');
            $table->unsignedBigInteger('annee_scolaire_id')->nullable();
            $table->string('statut_paiement', 40);
            $table->string('type_reduction', 40)->default('aucune');
            $table->decimal('valeur', 12, 2)->default(0);
            $table->string('payeur_libelle', 120)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['ecole_id', 'annee_scolaire_id', 'statut_paiement'], 'reduction_configs_unique_scope');
        });

        Schema::create('paiement_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ecole_id')->nullable();
            $table->string('type', 40);
            $table->unsignedBigInteger('dernier_numero')->default(0);
            $table->timestamps();

            $table->unique(['ecole_id', 'type']);
        });

        Schema::create('plans_paiement', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('eleve_id');
            $table->unsignedBigInteger('ecole_id');
            $table->unsignedBigInteger('classe_id');
            $table->unsignedBigInteger('annee_scolaire_id');
            $table->string('mode_paiement', 40);
            $table->string('statut_paiement', 40)->default('normal');
            $table->decimal('montant_total', 12, 2)->default(0);
            $table->decimal('reduction', 12, 2)->default(0);
            $table->decimal('montant_final', 12, 2)->default(0);
            $table->string('payeur_type', 40)->default('parent');
            $table->string('payeur_libelle', 120)->nullable();
            $table->json('details_frais')->nullable();
            $table->timestamps();

            $table->index(['ecole_id', 'annee_scolaire_id', 'classe_id']);
            $table->unique(['eleve_id', 'annee_scolaire_id'], 'plans_paiement_eleve_annee_unique');
        });

        Schema::create('echeances_paiement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_paiement_id')->constrained('plans_paiement')->cascadeOnDelete();
            $table->string('libelle', 120);
            $table->decimal('montant_prevu', 12, 2)->default(0);
            $table->date('date_limite');
            $table->string('statut', 40)->default('en_attente');
            $table->timestamps();

            $table->index(['plan_paiement_id', 'date_limite']);
        });

        Schema::table('eleve', function (Blueprint $table) {
            if (!Schema::hasColumn('eleve', 'statut_paiement')) {
                $table->string('statut_paiement', 40)->default('normal')->after('mode_paiement');
            }
        });

        Schema::table('paiement', function (Blueprint $table) {
            if (!Schema::hasColumn('paiement', 'echeance_id')) {
                $table->unsignedBigInteger('echeance_id')->nullable()->after('id_eleve');
            }
            if (!Schema::hasColumn('paiement', 'encaissement_id')) {
                $table->unsignedBigInteger('encaissement_id')->nullable()->after('echeance_id');
            }
            if (!Schema::hasColumn('paiement', 'montant_paye')) {
                $table->decimal('montant_paye', 12, 2)->nullable()->after('montant');
            }
            if (!Schema::hasColumn('paiement', 'mode_reglement')) {
                $table->string('mode_reglement', 40)->default('especes')->after('date_paiement');
            }
            if (!Schema::hasColumn('paiement', 'statut')) {
                $table->string('statut', 40)->default('valide')->after('mode_reglement');
            }
            if (!Schema::hasColumn('paiement', 'annule_at')) {
                $table->timestamp('annule_at')->nullable()->after('statut');
            }
            if (!Schema::hasColumn('paiement', 'annule_par')) {
                $table->unsignedBigInteger('annule_par')->nullable()->after('annule_at');
            }
            if (!Schema::hasColumn('paiement', 'motif_annulation')) {
                $table->string('motif_annulation', 255)->nullable()->after('annule_par');
            }
        });

        Schema::table('encaissement', function (Blueprint $table) {
            if (!Schema::hasColumn('encaissement', 'paiement_id')) {
                $table->unsignedBigInteger('paiement_id')->nullable()->after('id_encaissement');
            }
            if (!Schema::hasColumn('encaissement', 'statut')) {
                $table->string('statut', 40)->default('valide')->after('montant_encaissement');
            }
        });

        $this->convertMoneyColumns();
        $this->addIndexesSafely();
        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('echeances_paiement');
        Schema::dropIfExists('plans_paiement');
        Schema::dropIfExists('paiement_sequences');
        Schema::dropIfExists('reduction_paiement_configs');
        Schema::dropIfExists('frais_scolaires');
    }

    private function convertMoneyColumns(): void
    {
        foreach ([
            'paiement.montant',
            'encaissement.montant_encaissement',
            'caisse.montant_initial',
            'caisse.montant_net',
            'planification.montant_planification',
        ] as $column) {
            [$table, $field] = explode('.', $column);
            if (Schema::hasColumn($table, $field)) {
                DB::statement("ALTER TABLE {$table} MODIFY {$field} DECIMAL(12,2) NOT NULL DEFAULT 0");
            }
        }
    }

    private function addIndexesSafely(): void
    {
        try {
            Schema::table('paiement', function (Blueprint $table) {
                $table->unique('reference', 'paiement_reference_unique');
            });
        } catch (Throwable) {
            // Existing duplicate legacy data can be cleaned before enforcing the index.
        }

        try {
            Schema::table('paiement', function (Blueprint $table) {
                $table->unique(['idEcole', 'numero_recu'], 'paiement_ecole_numero_recu_unique');
            });
        } catch (Throwable) {
            // Existing duplicate legacy data can be cleaned before enforcing the index.
        }

        try {
            Schema::table('encaissement', function (Blueprint $table) {
                $table->index('paiement_id', 'encaissement_paiement_id_index');
            });
        } catch (Throwable) {
            //
        }
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach (['paiements_apercu', 'paiements_faire', 'historique_paiement_apercu', 'historique_paiement_export'] as $name) {
            DB::table('permissions')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
};
