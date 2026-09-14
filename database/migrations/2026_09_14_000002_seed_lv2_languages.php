<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Langues proposees par defaut — une ecole peut en ajouter d'autres
    // ensuite via l'ecran de gestion des matieres, il suffit de cocher
    // "LV2" sur la nouvelle matiere.
    private array $langues = ['Arabe', 'Allemand', 'Chinois', 'Russe'];

    private array $ordres = ['Secondaire Generale', 'Secondaire Technique et Professionnel'];

    public function up(): void
    {
        foreach ($this->langues as $langue) {
            $nomMatiere = $langue . ' LV2';

            $existing = DB::table('matiere')
                ->where('nom_matiere', $nomMatiere)
                ->whereNull('id_ecole')
                ->first();

            $idMatiere = $existing->id_matiere ?? DB::table('matiere')->insertGetId([
                'nom_matiere' => $nomMatiere,
                'id_ecole' => null,
                'est_lv2' => true,
            ]);

            if ($existing) {
                DB::table('matiere')->where('id_matiere', $idMatiere)->update(['est_lv2' => true]);
            }

            foreach ($this->ordres as $ordre) {
                $already = DB::table('matiere_ordre')
                    ->where('id_matiere', $idMatiere)
                    ->where('ordre_enseignement', $ordre)
                    ->exists();

                if (!$already) {
                    DB::table('matiere_ordre')->insert([
                        'id_matiere' => $idMatiere,
                        'ordre_enseignement' => $ordre,
                    ]);
                }
            }
        }

        // L'ancienne matiere generique "LV2" (partagee par toutes les
        // langues) n'est plus d'usage desormais que chaque langue a sa
        // propre ligne. On ne la supprime que si rien ne la reference deja
        // (verifie : c'est le cas sur les donnees actuelles), pour ne
        // jamais casser un enregistrement existant.
        $legacyLv2 = DB::table('matiere')
            ->where('nom_matiere', 'LV2')
            ->whereNull('id_ecole')
            ->first();

        if ($legacyLv2) {
            $stillUsed = DB::table('ligneclasse')->where('id_matiere', $legacyLv2->id_matiere)->exists()
                || DB::table('ligne_evaluation')->where('id_matiere', $legacyLv2->id_matiere)->exists();

            if (!$stillUsed) {
                DB::table('matiere_ordre')->where('id_matiere', $legacyLv2->id_matiere)->delete();
                DB::table('matiere')->where('id_matiere', $legacyLv2->id_matiere)->delete();
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('matiere')
            ->whereIn('nom_matiere', array_map(fn ($l) => $l . ' LV2', $this->langues))
            ->whereNull('id_ecole')
            ->pluck('id_matiere');

        DB::table('matiere_ordre')->whereIn('id_matiere', $ids)->delete();
        DB::table('matiere')->whereIn('id_matiere', $ids)->delete();
    }
};
