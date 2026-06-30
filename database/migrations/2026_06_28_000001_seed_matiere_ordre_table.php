<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if already populated
        if (DB::table('matiere_ordre')->exists()) {
            return;
        }

        $map = [
            'Sciences Naturelle'        => ['Fondamentale II', 'Secondaire Generale'],
            'Mathématiques '            => ['Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'],
            'physique'                  => ['Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'],
            'Chimie'                    => ['Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'],
            'philosophie '              => ['Secondaire Generale'],
            'Français '                 => ['Secondaire Generale', 'Secondaire Technique et Professionnel'],
            'Rédaction '                => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'Anglais'                   => ['Fondamentale II', 'Secondaire Generale', 'Secondaire Technique et Professionnel'],
            'Musique'                   => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'ECM'                       => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'Histoire '                 => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'Géographie '               => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'Lecture'                   => ['Fondamentale I', 'Fondamentale II'],
            'Récitation '               => ['Fondamentale I', 'Fondamentale II'],
            'Dictée et Question'        => ['Fondamentale I', 'Fondamentale II'],
            'Grammaire'                 => ['Fondamentale I', 'Fondamentale II', 'Secondaire Generale'],
            'LV2'                       => ['Secondaire Generale', 'Secondaire Technique et Professionnel'],
        ];

        foreach ($map as $nomMatiere => $ordres) {
            $matiere = DB::table('matiere')
                ->where('nom_matiere', $nomMatiere)
                ->whereNull('id_ecole')
                ->first();

            if (!$matiere) {
                continue;
            }

            foreach ($ordres as $ordre) {
                DB::table('matiere_ordre')->insert([
                    'id_matiere'         => $matiere->id_matiere,
                    'ordre_enseignement' => $ordre,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('matiere_ordre')
            ->whereIn('id_matiere', function ($query) {
                $query->select('id_matiere')->from('matiere')->whereNull('id_ecole');
            })
            ->delete();
    }
};
