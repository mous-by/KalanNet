<?php

namespace App\Http\Controllers;

use App\Support\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Matières qui caractérisent chaque série du secondaire général, pour la
 * tendance d'orientation du bulletin. Sans réglage, KalanNet propose une
 * liste de départ d'après le nom des matières.
 */
class SerieController extends Controller
{
    public function index()
    {
        $this->autoriser(['classes_apercu', 'classes_modification']);
        $idEcole = $this->ecoleCourante();
        $matieres = $this->matieresSecondaire($idEcole);

        return view('pedagogie.series', [
            'series' => Series::SERIES,
            'matieres' => $matieres,
            'choix' => Series::matieresParSerie($idEcole, $matieres),
            'personnalise' => DB::table('series_matieres')->where('id_ecole', $idEcole)->exists(),
            'peutModifier' => $this->peut(['classes_modification']),
        ]);
    }

    public function update(Request $request)
    {
        $this->autoriser(['classes_modification']);
        $idEcole = $this->ecoleCourante();
        $permises = $this->matieresSecondaire($idEcole)->pluck('id_matiere')->map(fn ($id) => (int) $id)->flip();

        if ($request->boolean('reinitialiser')) {
            DB::table('series_matieres')->where('id_ecole', $idEcole)->delete();

            return redirect()->route('pedagogie.series')->with('success', 'Liste de départ rétablie.');
        }

        $choix = $request->validate(['series' => 'nullable|array', 'series.*' => 'nullable|array'])['series'] ?? [];
        DB::transaction(function () use ($idEcole, $choix, $permises) {
            DB::table('series_matieres')->where('id_ecole', $idEcole)->delete();
            $maintenant = now();
            $lignes = [];
            foreach (array_keys(Series::SERIES) as $serie) {
                foreach ((array) ($choix[$serie] ?? []) as $idMatiere) {
                    if ($permises->has((int) $idMatiere)) {
                        $lignes[] = ['id_ecole' => $idEcole, 'serie' => $serie, 'id_matiere' => (int) $idMatiere, 'created_at' => $maintenant, 'updated_at' => $maintenant];
                    }
                }
            }
            DB::table('series_matieres')->insert($lignes);
        });

        return redirect()->route('pedagogie.series')->with('success', 'Matières des séries enregistrées.');
    }

    /**
     * Matières du secondaire général que l'école peut utiliser : le catalogue
     * du secondaire (franco-arabe pour une école franco-arabe), ses propres
     * matières du secondaire et celles déjà placées dans ses classes.
     */
    private function matieresSecondaire(int $idEcole)
    {
        $ecole = \App\Models\Ecole::withoutGlobalScopes()->find($idEcole);

        $catalogue = \App\Models\Matiere::withoutGlobalScopes()
            ->visiblesPourEcole($ecole)
            ->whereHas('ordres', fn ($q) => $q->where('ordre_enseignement', 'Secondaire Generale'))
            ->get(['id_matiere', 'nom_matiere', 'est_lv2']);

        $enClasse = DB::table('ligneclasse as lc')
            ->join('classe as c', 'c.id_classe', '=', 'lc.id_classe')
            ->join('matiere as m', 'm.id_matiere', '=', 'lc.id_matiere')
            ->where('c.idEcole', $idEcole)
            ->where('c.ordreEnseignement', 'secondairegenerale')
            ->get(['m.id_matiere', 'm.nom_matiere', 'm.est_lv2']);

        return $catalogue->map(fn ($m) => (object) ['id_matiere' => (int) $m->id_matiere, 'nom_matiere' => $m->nom_matiere, 'est_lv2' => (bool) $m->est_lv2])
            ->concat($enClasse->map(fn ($m) => (object) ['id_matiere' => (int) $m->id_matiere, 'nom_matiere' => $m->nom_matiere, 'est_lv2' => (bool) $m->est_lv2]))
            ->unique('id_matiere')
            ->sortBy(fn ($m) => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($m->nom_matiere)))
            ->values();
    }

    private function ecoleCourante(): int
    {
        $idEcole = (int) (session('idEcole') ?: Auth::user()->idEcole);
        abort_unless($idEcole, 403, "Sélectionnez d'abord une école.");

        return $idEcole;
    }

    private function peut(array $permissions): bool
    {
        $user = Auth::user();

        return $user && ($user->droit === 'SupAdmin' || $user->userHasAnyPermission($permissions));
    }

    private function autoriser(array $permissions): void
    {
        abort_unless($this->peut($permissions), 403, 'Permission insuffisante.');
    }
}
