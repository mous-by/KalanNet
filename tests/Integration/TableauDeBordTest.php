<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;

/** Audit 2026-10 : effectifs par classe du tableau de bord (requête groupée) = comptages directs. */
class TableauDeBordTest extends IntegrationTestCase
{
    public function test_effectifs_par_classe(): void
    {
        $admin = $this->adminComplet(1);
        $donnees = $this->withToken($admin->createToken('test')->plainTextToken)->getJson('/api/v1/dashboard')->assertOk()->json();
        $annee = $donnees['anneeEnCours']['id_anneeScolaire'] ?? null;

        foreach ($donnees['classesData'] as $ligne) {
            $classe = DB::table('classe')->where('idEcole', 1)->where('nom_classe', $ligne['nom'])->value('id_classe');
            $eleves = DB::table('eleve')->where('id_classe', $classe)->where('id_ecole', 1)->where('etat_dossier', 0)->when($annee, fn ($q) => $q->where('id_annee', $annee));
            $this->assertSame((clone $eleves)->count(), $ligne['total'], $ligne['nom'].' : total');
            $this->assertSame((clone $eleves)->whereIn('genre_eleve', ['Féminin', 'Feminin', 'F', 'Fille', 'Femme'])->count(), $ligne['filles'], $ligne['nom'].' : filles');
            $this->assertSame((clone $eleves)->whereIn('genre_eleve', ['Masculin', 'M', 'Garçon', 'Garcon', 'Homme', 'H'])->count(), $ligne['garcons'], $ligne['nom'].' : garçons');
        }
    }
}
