<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulletin_publications')) {
            Schema::create('bulletin_publications', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('id_ecole');
                $table->unsignedInteger('id_classe');
                $table->unsignedInteger('id_annee');
                $table->unsignedInteger('id_trimestre')->nullable();
                $table->unsignedTinyInteger('mois')->nullable();
                $table->unsignedInteger('published_by')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['id_ecole', 'id_classe', 'id_annee', 'id_trimestre', 'mois'], 'bulletin_publications_unique_period');
                $table->index(['id_ecole', 'id_classe', 'id_annee']);
            });
        }

        if (Schema::hasTable('transfert')) {
            Schema::table('transfert', function (Blueprint $table) {
                if (!Schema::hasColumn('transfert', 'date_transfert')) {
                    $table->timestamp('date_transfert')->nullable()->after('conduite');
                }
                if (!Schema::hasColumn('transfert', 'date_retour')) {
                    $table->timestamp('date_retour')->nullable()->after('date_transfert');
                }
                if (!Schema::hasColumn('transfert', 'motif_retour')) {
                    $table->string('motif_retour')->nullable()->after('date_retour');
                }
                if (!Schema::hasColumn('transfert', 'retour_effectue_par')) {
                    $table->unsignedInteger('retour_effectue_par')->nullable()->after('motif_retour');
                }
            });
        }

        if (!Schema::hasTable('annonces_admin_gestionnaire')) {
            Schema::create('annonces_admin_gestionnaire', function (Blueprint $table) {
                $table->increments('id_annonce');
                $table->unsignedInteger('id_ecole');
                $table->string('titre');
                $table->text('contenu');
                $table->string('public_cible', 50)->default('tous');
                $table->unsignedInteger('id_utilisateur');
                $table->string('fichier_joint')->nullable();
                $table->string('type_fichier', 100)->nullable();
                $table->integer('taille_fichier')->nullable();
                $table->string('statut_annonce', 30)->default('publie');
                $table->dateTime('date_publication')->nullable();
            });
        } else {
            Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
                if (!Schema::hasColumn('annonces_admin_gestionnaire', 'statut_annonce')) {
                    $table->string('statut_annonce', 30)->default('publie')->after('taille_fichier');
                }
            });
        }

        if (!Schema::hasTable('annonces_fichiers')) {
            Schema::create('annonces_fichiers', function (Blueprint $table) {
                $table->increments('id_fichier');
                $table->unsignedInteger('id_annonce');
                $table->string('type_annonce', 40)->default('admin_gestionnaire');
                $table->string('titre')->nullable();
                $table->string('nom_fichier');
                $table->string('nom_original')->nullable();
                $table->string('type_mime', 100)->nullable();
                $table->integer('taille')->nullable();
                $table->dateTime('date_ajout')->nullable();
                $table->index(['id_annonce', 'type_annonce']);
            });
        }

        if (!Schema::hasTable('annonces_lues')) {
            Schema::create('annonces_lues', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_utilisateur');
                $table->unsignedInteger('id_annonce');
                $table->string('type_annonce', 40)->default('admin_gestionnaire');
                $table->dateTime('date_lecture');
                $table->timestamps();
                $table->unique(['id_utilisateur', 'id_annonce', 'type_annonce'], 'unique_annonce_lue');
            });
        }

        foreach ([
            'utilisateurs_supprimer',
            'annonces_apercu',
            'annonces_creation',
            'annonces_supprimer',
            'bulletins_publication',
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['name' => $permission], ['name' => $permission]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_publications');

        if (Schema::hasTable('transfert')) {
            Schema::table('transfert', function (Blueprint $table) {
                foreach (['retour_effectue_par', 'motif_retour', 'date_retour', 'date_transfert'] as $column) {
                    if (Schema::hasColumn('transfert', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('annonces_admin_gestionnaire') && Schema::hasColumn('annonces_admin_gestionnaire', 'statut_annonce')) {
            Schema::table('annonces_admin_gestionnaire', function (Blueprint $table) {
                $table->dropColumn('statut_annonce');
            });
        }
    }
};
