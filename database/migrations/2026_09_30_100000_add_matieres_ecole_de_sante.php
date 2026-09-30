<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Matières enseignées dans les Écoles de Santé (liste fournie par une école
     * de santé) : catalogue commun (id_ecole null) rattaché à l'ordre
     * « École de Santé », donc visible par toutes les écoles de santé et
     * seulement par elles.
     */
    private const MATIERES = [
        'Anatomie - Physiologie',
        'Anesthésie - Réanimation',
        'Bactériologie',
        'Biochimie',
        'CCC',
        'Cytogénétique / Biologie de la reproduction',
        'Écologie',
        'Épidémiologie',
        'Génétique',
        'Gestion',
        'Gestion Administration',
        'Gynéco-obstétrique',
        'Hématologie',
        'Hygiène - Assainissement',
        'Immunologie',
        'Immunologie appliquée',
        'Législation du travail',
        'Maladies infectieuses',
        'Maladies parasitaires',
        'Microbiologie',
        'Nutrition',
        'Obstétrique',
        'Odonto-Stomatologie',
        'Ophtalmologie',
        'ORL',
        'Parasitologie',
        'Pathologie chirurgicale',
        'Pathologie médicale',
        'Pathologie obstétricale',
        'Pédiatrie',
        'Pharmacie',
        'Physiologie',
        'Planification familiale',
        'Psychiatrie',
        'Puériculture',
        'Radiologie',
        "Relation d'aide",
        'Santé de la reproduction',
        'Santé publique',
        'Santé sécurité au travail',
        'Sémiologie chirurgicale',
        'Sémiologie médicale',
        'Sérologie',
        'Statistique',
        'Transfusion sanguine',
        'TP Bactériologie',
        'TP Chirurgie',
        'TP Hématologie',
        'TP Médecine',
        'TP Obstétrique',
        'Virologie',
        'Stage',
    ];

    private const ORDRE = 'École de Santé';

    public function up(): void
    {
        foreach (self::MATIERES as $nom) {
            // Réutilise une matière Santé commune déjà présente (ex. Physiologie).
            $id = DB::table('matiere')
                ->whereNull('id_ecole')
                ->where('nom_matiere', $nom)
                ->whereExists(fn ($q) => $q->from('matiere_ordre')
                    ->whereColumn('matiere_ordre.id_matiere', 'matiere.id_matiere')
                    ->where('ordre_enseignement', self::ORDRE))
                ->value('id_matiere');

            if ($id) {
                continue;
            }

            $id = DB::table('matiere')->insertGetId([
                'nom_matiere' => $nom,
                'id_ecole' => null,
                'est_franco_arabe' => false,
            ]);
            DB::table('matiere_ordre')->insert(['id_matiere' => $id, 'ordre_enseignement' => self::ORDRE]);
        }
    }

    public function down(): void
    {
        // Ne retire que les matières créées ici et encore inutilisées (Physiologie
        // existait déjà avant cette migration).
        $noms = array_values(array_diff(self::MATIERES, ['Physiologie']));
        $ids = DB::table('matiere')
            ->whereNull('id_ecole')
            ->whereIn('nom_matiere', $noms)
            ->whereExists(fn ($q) => $q->from('matiere_ordre')
                ->whereColumn('matiere_ordre.id_matiere', 'matiere.id_matiere')
                ->where('ordre_enseignement', self::ORDRE))
            ->pluck('id_matiere');
        $utilisees = DB::table('ligneclasse')->whereIn('id_matiere', $ids)->pluck('id_matiere')->unique();
        $supprimables = $ids->diff($utilisees);

        DB::table('matiere_ordre')->whereIn('id_matiere', $supprimables)->delete();
        DB::table('matiere')->whereIn('id_matiere', $supprimables)->delete();
    }
};
