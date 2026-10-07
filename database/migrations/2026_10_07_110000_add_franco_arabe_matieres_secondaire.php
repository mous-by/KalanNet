<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Matières du secondaire des écoles franco-arabes (10ème C1, 11ème SES,
     * 11ème L, Terminale TSS et TLL) : catalogue commun aux écoles
     * franco-arabes, rattaché au Secondaire Général.
     */
    private const MATIERES = [
        'Éducation islamique',
        'Histoire',
        'Géographie',
        'Histoire et Géographie',
        'Informatique',
        'Français',
        'Anglais',
        'Sociologie',
        'Philosophie',
        'Franco-arabe',
        'Littérature arabe',
        'Mathématiques',
        'ECM',
        'Langue nationale',
        'EPS',
        'Arts',
        'Économie',
        'Comptabilité',
        'SVT',
        'Sciences physiques',
    ];

    private const ORDRE = 'Secondaire Generale';

    public function up(): void
    {
        foreach (self::MATIERES as $nom) {
            $id = $this->matiereFrancoArabeSecondaire($nom);
            if ($id) {
                continue;
            }

            $id = DB::table('matiere')->insertGetId([
                'nom_matiere' => $nom,
                'id_ecole' => null,
                'est_franco_arabe' => true,
            ]);
            DB::table('matiere_ordre')->insert(['id_matiere' => $id, 'ordre_enseignement' => self::ORDRE]);
        }
    }

    public function down(): void
    {
        $ids = collect(self::MATIERES)->map(fn ($nom) => $this->matiereFrancoArabeSecondaire($nom))->filter();
        $utilisees = DB::table('ligneclasse')->whereIn('id_matiere', $ids)->pluck('id_matiere')->unique();
        $supprimables = $ids->diff($utilisees);

        DB::table('matiere_ordre')->whereIn('id_matiere', $supprimables)->delete();
        DB::table('matiere')->whereIn('id_matiere', $supprimables)->delete();
    }

    private function matiereFrancoArabeSecondaire(string $nom): ?int
    {
        $id = DB::table('matiere')
            ->whereNull('id_ecole')
            ->where('est_franco_arabe', true)
            ->where('nom_matiere', $nom)
            ->whereExists(fn ($q) => $q->from('matiere_ordre')
                ->whereColumn('matiere_ordre.id_matiere', 'matiere.id_matiere')
                ->where('ordre_enseignement', self::ORDRE))
            ->value('id_matiere');

        return $id ? (int) $id : null;
    }
};
