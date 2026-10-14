<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Matières franco-arabes du Fondamental I (5ème et 6ème année) organisées
     * par domaine (UE). Les matières existantes ne sont pas modifiées : celles-ci
     * s'ajoutent au catalogue. Partie arabe : noms en arabe ; partie française :
     * noms en français, suivis de « (français) » pour les distinguer.
     */
    private const MATIERES = [
        // Partie française
        'langue_francaise' => ['Dictée (français)', 'Lecture (français)', 'Écriture (français)', 'Expression orale (français)', 'Vocabulaire', 'Conjugaison (français)'],
        'sciences_maths' => ['Mathématiques (français)', 'Technologie', 'Sciences'],
        // Partie arabe
        'islamique' => ['القرآن', 'الحديث', 'التوحيد', 'الفقه', 'السيرة النبوية'],
        'langue_arabe' => ['القراءة', 'الكتابة', 'التعبير الشفوي'],
        'sciences_humaines' => ['التاريخ', 'الجغرافيا'],
        'developpement_personne' => ['التربية المدنية والأخلاقية', 'التربية البدنية'],
        'arts_arabe' => ['الفنون التمثيلية', 'الرسم', 'النشيد'],
    ];

    /** Nouveau domaine : les Arts du maître arabe (le domaine « Arts » existant reste côté français). */
    private const DOMAINE_ARTS_ARABE = ['arts_arabe', 'Arts (arabe)', 'الفنون', 'arabe'];

    public function up(): void
    {
        foreach (self::MATIERES as $domaine => $noms) {
            foreach ($noms as $nom) {
                $id = DB::table('matiere')->whereNull('id_ecole')->where('nom_matiere', $nom)->value('id_matiere')
                    ?: DB::table('matiere')->insertGetId([
                        'nom_matiere' => $nom,
                        'id_ecole' => null,
                        'est_franco_arabe' => true,
                        'domaine_defaut' => $domaine,
                    ]);
                if (!DB::table('matiere_ordre')->where('id_matiere', $id)->where('ordre_enseignement', 'Fondamentale I')->exists()) {
                    DB::table('matiere_ordre')->insert(['id_matiere' => $id, 'ordre_enseignement' => 'Fondamentale I']);
                }
            }
        }

        // Écoles qui ont déjà leur liste de domaines : on y ajoute « Arts (arabe) ».
        [$code, $nom, $nomAr, $partie] = self::DOMAINE_ARTS_ARABE;
        foreach (DB::table('domaines')->distinct()->pluck('id_ecole') as $idEcole) {
            if (DB::table('domaines')->where('id_ecole', $idEcole)->where('code', $code)->exists()) {
                continue;
            }
            DB::table('domaines')->insert([
                'id_ecole' => $idEcole,
                'code' => $code,
                'nom' => $nom,
                'nom_ar' => $nomAr,
                'partie' => $partie,
                'ordre' => (int) DB::table('domaines')->where('id_ecole', $idEcole)->max('ordre') + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $noms = collect(self::MATIERES)->flatten()->all();
        $ids = DB::table('matiere')->whereNull('id_ecole')->whereIn('nom_matiere', $noms)->pluck('id_matiere');
        $supprimables = $ids->diff(DB::table('ligneclasse')->whereIn('id_matiere', $ids)->pluck('id_matiere'));
        DB::table('matiere_ordre')->whereIn('id_matiere', $supprimables)->delete();
        DB::table('matiere')->whereIn('id_matiere', $supprimables)->delete();

        $domaines = DB::table('domaines')->where('code', self::DOMAINE_ARTS_ARABE[0])->pluck('id_domaine');
        DB::table('ligneclasse')->whereIn('id_domaine', $domaines)->update(['id_domaine' => null]);
        DB::table('domaines')->whereIn('id_domaine', $domaines)->delete();
    }
};
