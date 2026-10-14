<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Domaine par défaut des matières du catalogue franco-arabe (fondamental),
     * d'après le bulletin de 6ème année de médersa. Les matières absentes de
     * cette liste restent sans domaine : l'école le choisit dans la classe.
     */
    private const DOMAINE_DEFAUT = [
        'islamique' => [
            'Coran-Karim القرآن الكريم', 'Dine الدين', 'Chant islamique المحفوظة', 'Hadis الحديث',
            'Tawhid التوحيد', 'Éthique الأخلاق', 'Siratou Nabawy السيرة', 'Fiqou-Islam الفقه',
            'Tahzib التهذيب', 'Édu-Islam التربية الإسلامية',
        ],
        'langue_arabe' => [
            'Écriture - Lecture الخط والقراءة', 'Dictée الإملاء', 'Conversation المحادثة', 'Tacmilat التكملة',
            'Lecture التلاوة', 'Dictée - Questions الإملاء والتمارين', 'Conjugaison التصريف', 'Écriture الخط',
            'Écriture - Lecture الخط واللغة', 'Grammaire النحو', 'Rédaction الإنشاء', 'Conversation المحادثة والتعبير',
            'Langue اللغة', 'Conjugaison الصرف', 'Langue - Lecture القراءة واللغة', 'Dictée - Questions الإملاء والأسئلة',
            'Lecture القراءة', 'Gram-Conj النحو والصرف',
        ],
        'sciences_humaines' => [
            'Géographie الجغرافية', 'Histoire التاريخ', 'Histoire التاريخ القومي', 'Hist-Géo التاريخ والجغرافية',
        ],
        'developpement_personne' => ['ECM المدنية', 'EPS الرياضة البدنية'],
        'arts' => ['Dessin الرسم'],
        'langue_francaise' => ['Français الفرنسية'],
        'sciences_maths' => [
            'Calcul الحساب', 'Calcul - Géométrie الحساب والهندسة', 'Math الرياضيات', 'Bio الأشياء', 'Bio الأحياء',
            'Physique الفيزياء', 'Biologie العلوم', 'Phy-Chim الفيزياء والكيمياء',
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('domaines')) {
            Schema::create('domaines', function (Blueprint $table) {
                $table->id('id_domaine');
                $table->unsignedInteger('id_ecole')->index();
                $table->string('code', 40)->nullable();
                $table->string('nom', 120);
                $table->string('nom_ar', 120)->nullable();
                $table->string('partie', 10)->nullable();
                $table->unsignedSmallInteger('ordre')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('matiere', 'domaine_defaut')) {
            Schema::table('matiere', fn (Blueprint $table) => $table->string('domaine_defaut', 40)->nullable());
        }

        if (!Schema::hasColumn('ligneclasse', 'id_domaine')) {
            Schema::table('ligneclasse', fn (Blueprint $table) => $table->unsignedBigInteger('id_domaine')->nullable());
        }

        Schema::table('classe', function (Blueprint $table) {
            if (!Schema::hasColumn('classe', 'deux_maitres')) {
                $table->boolean('deux_maitres')->default(false);
            }
            if (!Schema::hasColumn('classe', 'id_maitre_arabe')) {
                $table->unsignedInteger('id_maitre_arabe')->nullable();
            }
            if (!Schema::hasColumn('classe', 'id_maitre_francais')) {
                $table->unsignedInteger('id_maitre_francais')->nullable();
            }
            if (!Schema::hasColumn('classe', 'modele_bulletin')) {
                $table->string('modele_bulletin', 30)->nullable();
            }
            if (!Schema::hasColumn('classe', 'note_classe_mode')) {
                $table->string('note_classe_mode', 10)->default('moyenne');
            }
        });

        foreach (self::DOMAINE_DEFAUT as $code => $noms) {
            DB::table('matiere')->whereNull('id_ecole')->where('est_franco_arabe', true)
                ->whereIn('nom_matiere', $noms)->update(['domaine_defaut' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('classe', function (Blueprint $table) {
            $table->dropColumn(['deux_maitres', 'id_maitre_arabe', 'id_maitre_francais', 'modele_bulletin', 'note_classe_mode']);
        });
        Schema::table('ligneclasse', fn (Blueprint $table) => $table->dropColumn('id_domaine'));
        Schema::table('matiere', fn (Blueprint $table) => $table->dropColumn('domaine_defaut'));
        Schema::dropIfExists('domaines');
    }
};
