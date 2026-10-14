<?php

namespace App\Http\Controllers;

use App\Support\Domaines;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Domaines (UE) de l'école, utilisés par les bulletins « par domaines » et
 * par les classes à deux maîtres (partie arabe / partie française).
 */
class DomaineController extends Controller
{
    public function index()
    {
        $this->autoriser(['classes_apercu', 'matieres_apercu']);
        $idEcole = $this->ecoleCourante();

        $domaines = Domaines::pourEcole($idEcole);
        $utilisations = DB::table('ligneclasse as lc')
            ->join('classe as c', 'c.id_classe', '=', 'lc.id_classe')
            ->where('c.idEcole', $idEcole)
            ->whereNotNull('lc.id_domaine')
            ->groupBy('lc.id_domaine')
            ->pluck(DB::raw('count(*)'), 'lc.id_domaine');

        return view('pedagogie.domaines', [
            'domaines' => $domaines,
            'utilisations' => $utilisations,
            'parties' => Domaines::PARTIES,
            'peutModifier' => $this->peut(['classes_modification']),
        ]);
    }

    public function store(Request $request)
    {
        $this->autoriser(['classes_modification']);
        $idEcole = $this->ecoleCourante();
        $data = $this->valider($request);

        DB::table('domaines')->insert($data + [
            'id_ecole' => $idEcole,
            'ordre' => (int) DB::table('domaines')->where('id_ecole', $idEcole)->max('ordre') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pedagogie.domaines')->with('success', 'Domaine ajouté.');
    }

    public function update(Request $request, int $id)
    {
        $this->autoriser(['classes_modification']);
        $domaine = $this->domaine($id);
        $data = $this->valider($request);

        DB::table('domaines')->where('id_domaine', $domaine->id_domaine)->update($data + ['updated_at' => now()]);

        return redirect()->route('pedagogie.domaines')->with('success', 'Domaine modifié.');
    }

    /** Déplace un domaine d'un cran vers le haut ou le bas dans sa partie. */
    public function deplacer(Request $request, int $id)
    {
        $this->autoriser(['classes_modification']);
        $domaine = $this->domaine($id);
        $sens = $request->validate(['sens' => 'required|in:haut,bas'])['sens'];

        $liste = Domaines::pourEcole((int) $domaine->id_ecole)->where('partie', $domaine->partie)->values();
        $position = $liste->search(fn ($d) => $d->id_domaine === $domaine->id_domaine);
        $voisin = $liste->get($sens === 'haut' ? $position - 1 : $position + 1);

        if ($voisin) {
            DB::transaction(function () use ($liste, $position, $voisin, $sens) {
                $ordre = $liste->pluck('id_domaine')->all();
                $cible = $sens === 'haut' ? $position - 1 : $position + 1;
                [$ordre[$position], $ordre[$cible]] = [$ordre[$cible], $ordre[$position]];
                foreach ($ordre as $rang => $idDomaine) {
                    DB::table('domaines')->where('id_domaine', $idDomaine)->update(['ordre' => $rang + 1]);
                }
            });
        }

        return redirect()->route('pedagogie.domaines');
    }

    public function destroy(int $id)
    {
        $this->autoriser(['classes_modification']);
        $domaine = $this->domaine($id);

        DB::transaction(function () use ($domaine) {
            // Les matières de ce domaine restent dans leurs classes, sans domaine.
            DB::table('ligneclasse')->where('id_domaine', $domaine->id_domaine)->update(['id_domaine' => null]);
            DB::table('domaines')->where('id_domaine', $domaine->id_domaine)->delete();
        });

        return redirect()->route('pedagogie.domaines')->with('success', 'Domaine supprimé.');
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'nom' => 'required|string|max:120',
            'nom_ar' => 'nullable|string|max:120',
            'partie' => ['nullable', Rule::in(array_keys(Domaines::PARTIES))],
        ], [
            'nom.required' => 'Le nom du domaine est obligatoire.',
        ]);
    }

    private function domaine(int $id): object
    {
        $domaine = DB::table('domaines')->where('id_domaine', $id)->first();
        abort_unless($domaine && (int) $domaine->id_ecole === $this->ecoleCourante(), 404);

        return $domaine;
    }

    private function ecoleCourante(): int
    {
        $idEcole = (int) (session('idEcole') ?: Auth::user()->idEcole);
        abort_unless($idEcole, 403, "Sélectionnez d'abord une école.");

        return $idEcole;
    }

    /** Les domaines font partie de la configuration des classes de l'école. */
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
