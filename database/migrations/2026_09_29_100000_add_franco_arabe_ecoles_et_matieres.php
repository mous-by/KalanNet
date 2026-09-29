<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matières des écoles franco-arabes (médersas) : catalogue commun, rattaché
     * au Fondamental I (1ère à 6ème année) et/ou au Fondamental II (7ème à
     * 9ème année). Noms bilingues : français puis arabe.
     */
    private const MATIERES = [
        ['Coran-Karim', 'القرآن الكريم', ['Fondamentale I', 'Fondamentale II']],
        ['Dine', 'الدين', ['Fondamentale I']],
        ['Écriture - Lecture', 'الخط والقراءة', ['Fondamentale I']],
        ['Dictée', 'الإملاء', ['Fondamentale I']],
        ['Conversation', 'المحادثة', ['Fondamentale I']],
        ['Chant islamique', 'المحفوظة', ['Fondamentale I']],
        ['Calcul', 'الحساب', ['Fondamentale I']],
        ['Tacmilat', 'التكملة', ['Fondamentale I']],
        ['Dessin', 'الرسم', ['Fondamentale I']],
        ['Hadis', 'الحديث', ['Fondamentale I']],
        ['Lecture', 'التلاوة', ['Fondamentale I']],
        ['Dictée - Questions', 'الإملاء والتمارين', ['Fondamentale I']],
        ['Conjugaison', 'التصريف', ['Fondamentale I']],
        ['Écriture', 'الخط', ['Fondamentale I']],
        ['Français', 'الفرنسية', ['Fondamentale I', 'Fondamentale II']],
        ['Tawhid', 'التوحيد', ['Fondamentale I']],
        ['Éthique', 'الأخلاق', ['Fondamentale I']],
        ['Siratou Nabawy', 'السيرة', ['Fondamentale I']],
        ['Écriture - Lecture', 'الخط واللغة', ['Fondamentale I']],
        ['Grammaire', 'النحو', ['Fondamentale I']],
        ['Rédaction', 'الإنشاء', ['Fondamentale I', 'Fondamentale II']],
        ['Bio', 'الأشياء', ['Fondamentale I']],
        ['Conversation', 'المحادثة والتعبير', ['Fondamentale I']],
        ['Fiqou-Islam', 'الفقه', ['Fondamentale I']],
        ['Langue', 'اللغة', ['Fondamentale I', 'Fondamentale II']],
        ['Écriture - Dessin', 'الخط والرسم', ['Fondamentale I']],
        ['Conjugaison', 'الصرف', ['Fondamentale I']],
        ['Calcul - Géométrie', 'الحساب والهندسة', ['Fondamentale I']],
        ['Géographie', 'الجغرافية', ['Fondamentale I']],
        ['Bio', 'الأحياء', ['Fondamentale I']],
        ['Langue - Lecture', 'القراءة واللغة', ['Fondamentale I']],
        ['Histoire', 'التاريخ', ['Fondamentale I']],
        ['Tahzib', 'التهذيب', ['Fondamentale I']],
        ['Dictée - Questions', 'الإملاء والأسئلة', ['Fondamentale I', 'Fondamentale II']],
        ['Lecture', 'القراءة', ['Fondamentale I']],
        ['Histoire', 'التاريخ القومي', ['Fondamentale I']],
        ['ECM', 'المدنية', ['Fondamentale I']],
        ['Agriculture', 'الزراعة', ['Fondamentale I']],
        ['Physique', 'الفيزياء', ['Fondamentale I']],
        ['Hist-Géo', 'التاريخ والجغرافية', ['Fondamentale II']],
        ['Math', 'الرياضيات', ['Fondamentale II']],
        ['Biologie', 'العلوم', ['Fondamentale II']],
        ['Phy-Chim', 'الفيزياء والكيمياء', ['Fondamentale II']],
        ['Édu-Islam', 'التربية الإسلامية', ['Fondamentale II']],
        ['Gram-Conj', 'النحو والصرف', ['Fondamentale II']],
        ['Anglais', 'الإنجليزية', ['Fondamentale II']],
        ['EPS', 'الرياضة البدنية', ['Fondamentale II']],
    ];

    public function up(): void
    {
        if (!Schema::hasColumn('ecole', 'franco_arabe')) {
            Schema::table('ecole', function (Blueprint $table) {
                $table->boolean('franco_arabe')->default(false);
            });
        }

        if (!Schema::hasColumn('matiere', 'est_franco_arabe')) {
            Schema::table('matiere', function (Blueprint $table) {
                $table->boolean('est_franco_arabe')->default(false);
            });
        }

        foreach (self::MATIERES as [$francais, $arabe, $ordres]) {
            $nom = $francais . ' ' . $arabe;
            $existante = DB::table('matiere')->whereNull('id_ecole')->where('nom_matiere', $nom)->value('id_matiere');
            $id = $existante ?: DB::table('matiere')->insertGetId([
                'nom_matiere' => $nom,
                'id_ecole' => null,
                'est_franco_arabe' => true,
            ]);

            foreach ($ordres as $ordre) {
                $dejaLiee = DB::table('matiere_ordre')->where('id_matiere', $id)->where('ordre_enseignement', $ordre)->exists();
                if (!$dejaLiee) {
                    DB::table('matiere_ordre')->insert(['id_matiere' => $id, 'ordre_enseignement' => $ordre]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('matiere')->whereNull('id_ecole')->where('est_franco_arabe', true)->pluck('id_matiere');
        $utilisees = DB::table('ligneclasse')->whereIn('id_matiere', $ids)->pluck('id_matiere')->unique();
        $supprimables = $ids->diff($utilisees);
        DB::table('matiere_ordre')->whereIn('id_matiere', $supprimables)->delete();
        DB::table('matiere')->whereIn('id_matiere', $supprimables)->delete();

        if ($utilisees->isEmpty()) {
            Schema::table('matiere', fn (Blueprint $table) => $table->dropColumn('est_franco_arabe'));
            Schema::table('ecole', fn (Blueprint $table) => $table->dropColumn('franco_arabe'));
        }
    }
};
