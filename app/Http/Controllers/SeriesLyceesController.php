<?php

namespace App\Http\Controllers;

use App\Support\RapportSuccinctDonnees;
use App\Support\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Espace du SupAdmin : la série des classes du secondaire général de TOUS les établissements déjà créés, réglée en une
 * fois (les rapports et canevas classent les classes d'après elle). La série reconnue d'après le nom est proposée ; un
 * bouton applique d'un coup toutes les propositions sûres, et les classes non reconnues se choisissent à la main.
 */
class SeriesLyceesController extends Controller
{
    private function autoriser(): void
    {
        abort_unless(Auth::user()?->droit === 'SupAdmin', 403);
    }

    /** Classes du général avec leur série actuelle, la proposition d'après le nom et leur état. */
    private function classes(?string $recherche = null)
    {
        return DB::table('classe as c')->join('ecole as e', 'e.idEcole', '=', 'c.idEcole')
            ->where('c.ordreEnseignement', 'secondairegenerale')
            ->when($recherche, fn ($q) => $q->where('e.nomEcole', 'like', '%'.$recherche.'%'))
            ->orderBy('e.nomEcole')->orderBy('c.nom_classe')
            ->get(['c.id_classe', 'c.nom_classe', 'c.serie', 'e.idEcole', 'e.nomEcole'])
            ->map(function ($c) {
                $cle = RapportSuccinctDonnees::serie($c->nom_classe, $c->serie);
                $c->choisie = isset(Series::RAPPORTS[$c->serie]) && $cle === Series::RAPPORTS[$c->serie][2];
                $c->proposee = Series::codePourCle($cle);

                return $c;
            });
    }

    public function index(Request $request)
    {
        $this->autoriser();
        $filtre = $request->query('filtre', 'a_verifier');
        $toutes = $this->classes($request->query('q'));
        $classes = $filtre === 'toutes' ? $toutes : $toutes->reject(fn ($c) => $c->choisie);

        return view('series-lycees.index', [
            'parEcole' => $classes->groupBy('nomEcole'),
            'filtre' => $filtre,
            'recherche' => $request->query('q'),
            'stats' => [
                'total' => $toutes->count(),
                'choisies' => $toutes->where('choisie', true)->count(),
                'proposees' => $toutes->where('choisie', false)->whereNotNull('proposee')->count(),
                'inconnues' => $toutes->where('choisie', false)->whereNull('proposee')->count(),
                'ecoles' => $toutes->reject(fn ($c) => $c->choisie)->pluck('idEcole')->unique()->count(),
            ],
        ]);
    }

    /** Séries choisies à la main dans la liste (une partie des classes affichées). */
    public function enregistrer(Request $request)
    {
        $this->autoriser();
        $json = json_decode((string) $request->input('series_json', ''), true);
        if (is_array($json)) {
            $request->merge(['serie' => $json]);
        }
        $data = $request->validate(['serie' => 'required|array', 'serie.*' => ['nullable', Rule::in(array_keys(Series::RAPPORTS))]]);
        $n = 0;
        foreach (array_filter($data['serie']) as $idClasse => $code) {
            $n += DB::table('classe')->where('id_classe', (int) $idClasse)->where('ordreEnseignement', 'secondairegenerale')->update(['serie' => $code]);
        }

        return back()->with('success', "{$n} classe(s) mise(s) à jour.");
    }

    /** D'un coup : toutes les classes non vérifiées dont la série est reconnue d'après le nom reçoivent cette série. */
    public function appliquerPropositions()
    {
        $this->autoriser();
        $n = 0;
        foreach ($this->classes()->reject(fn ($c) => $c->choisie)->whereNotNull('proposee') as $c) {
            $n += DB::table('classe')->where('id_classe', $c->id_classe)->update(['serie' => $c->proposee]);
        }

        return back()->with('success', "{$n} classe(s) ont reçu la série reconnue d'après leur nom. Il reste à choisir à la main celles qui n'ont pas été reconnues.");
    }
}
