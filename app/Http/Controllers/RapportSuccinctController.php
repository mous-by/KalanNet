<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportSuccinct;
use App\Support\RapportSuccinctDonnees as Donnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Rapport succinct de rentrée : l'école vérifie la fiche pré-remplie, la télécharge à tout moment et la remet au CAP
 * (par KalanNet si son CAP a un compte, sinon sur papier). Le DCAP suit les fiches de son CAP.
 */
class RapportSuccinctController extends Controller
{
    public static function autorise($user): bool
    {
        return $user && ($user->droit === 'SupAdmin' || $user->userHasPermission('rapport_succinct_gerer'));
    }

    private function ecole(): Ecole
    {
        abort_unless(self::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(Donnees::ecoleConcernee((int) $ecole->idEcole), 403, "Le rapport succinct concerne le préscolaire et le fondamental.");

        return $ecole;
    }

    private function annees()
    {
        return AnneeScolaire::whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
    }

    private function annee(Request $request, Ecole $ecole): AnneeScolaire
    {
        $annees = $this->annees();
        $choisie = $request->integer('annee') ? $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) : null;
        $annee = $choisie ?? AnneeScolaire::enCours((int) $ecole->idEcole);
        abort_unless($annee, 404, "Aucune année scolaire n'est disponible.");

        return $annee;
    }

    public function index(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $rapport = RapportSuccinct::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->first();
        $donnees = $rapport?->donnees ?? Donnees::calculer($ecole, (int) $annee->id_anneeScolaire);

        // « Reprendre les chiffres de KalanNet » : tableaux recalculés, le reste de la fiche est gardé.
        if ($request->boolean('recalculer')) {
            $calcul = Donnees::calculer($ecole, (int) $annee->id_anneeScolaire);
            $salles = collect($donnees['maitres'] ?? [])->map(fn ($m) => $m['salles'] ?? null);
            $donnees['groupes'] = $calcul['groupes'];
            $donnees['maitres'] = collect($calcul['maitres'])->map(fn ($m, $cycle) => ['salles' => $salles[$cycle] ?? null] + $m)->all();
        }

        return view('rapports-succincts.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(), 'rapport' => $rapport, 'donnees' => $donnees,
            'capSurKalanNet' => Donnees::capSurKalanNet($ecole->id_cap),
            'manquants' => Donnees::champsManquants($donnees),
        ]);
    }

    /** Boutons du formulaire : enregistrer, enregistrer et télécharger, enregistrer et remettre au CAP. */
    public function store(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $donnees = $this->valider($request, $ecole, (int) $annee->id_anneeScolaire);

        $rapport = RapportSuccinct::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire]);
        $rapport->donnees = $donnees;
        $rapport->statut ??= 'brouillon';
        $rapport->save();

        $retour = ['annee' => $annee->id_anneeScolaire];
        if ($request->input('action') === 'telecharger') {
            return redirect()->route('rapport-succinct.pdf', $retour);
        }
        if ($request->input('action') === 'remettre') {
            return $this->remettre($rapport, $ecole, $annee, $donnees);
        }

        return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport enregistré.');
    }

    private function remettre(RapportSuccinct $rapport, Ecole $ecole, AnneeScolaire $annee, array $donnees)
    {
        $retour = ['annee' => $annee->id_anneeScolaire];
        $manquants = Donnees::champsManquants($donnees);
        if ($manquants !== []) {
            throw ValidationException::withMessages(['rapport' => 'À compléter avant de remettre le rapport : '.implode(', ', $manquants).'.']);
        }

        $surKalanNet = Donnees::capSurKalanNet($ecole->id_cap);
        $rapport->update([
            'statut' => $surKalanNet ? 'envoye' : 'remis', 'id_cap' => $ecole->id_cap, 'donnees_envoyees' => $donnees,
            'envoye_at' => now(), 'envoye_par' => Auth::id(),
        ]);

        if (!$surKalanNet) {
            return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport marqué comme remis à votre CAP.');
        }

        DB::table('utilisateurs')->where('droit', 'DCAP')->where('id_cap', $ecole->id_cap)->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($dcap) => AppNotification::create([
                'user_id' => $dcap, 'type' => 'rapport_succinct', 'title' => 'Rapport succinct reçu',
                'message' => $ecole->nomEcole.' a envoyé son rapport succinct de rentrée '.$annee->annee.'.',
                'link' => route('territoire.rapports-succincts', ['annee' => $annee->id_anneeScolaire], false),
            ]));

        return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport envoyé à votre CAP.');
    }

    /** @return array<string, mixed> */
    private function valider(Request $request, Ecole $ecole, int $idAnnee): array
    {
        $entier = ['required', 'integer', 'min:0', 'max:99999'];
        $data = $request->validate([
            'quartier' => 'nullable|string|max:150', 'commune' => 'nullable|string|max:150', 'cap' => 'nullable|string|max:150',
            'cercle' => 'nullable|string|max:150', 'academie' => 'nullable|string|max:150', 'langue' => 'nullable|string|max:100',
            'cycle' => ['required', Rule::in(array_keys(Donnees::CYCLES))],
            'statut' => ['nullable', Rule::in(array_keys(Donnees::STATUTS))],
            'enseignement' => ['required', Rule::in(array_keys(Donnees::ENSEIGNEMENTS))],
            'recrutement' => ['nullable', Rule::in(array_keys(Donnees::RECRUTEMENTS))],
            'groupes' => 'required|array', 'groupes.*.groupes' => $entier, 'groupes.*.g' => $entier, 'groupes.*.f' => $entier,
            'groupes.*.rg' => $entier, 'groupes.*.rf' => $entier,
            'maitres' => 'required|array', 'maitres.*.permanents' => $entier, 'maitres.*.contractuels' => $entier,
            'maitres.*.salles' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], ['quartier' => 'village / quartier', 'statut' => 'statut administratif', 'recrutement' => 'mode de recrutement']);

        // Lignes et cycles de référence : les libellés ne viennent jamais du formulaire.
        $reference = Donnees::calculer($ecole, $idAnnee);
        $groupes = collect($reference['groupes'])->map(function ($ligne) use ($data) {
            $saisie = $data['groupes'][$ligne['cle']] ?? [];
            foreach (['groupes', 'g', 'f', 'rg', 'rf'] as $champ) {
                $ligne[$champ] = (int) ($saisie[$champ] ?? 0);
            }
            if ($ligne['rg'] > $ligne['g'] || $ligne['rf'] > $ligne['f']) {
                throw ValidationException::withMessages(['groupes' => $ligne['libelle'].' : il y a plus de redoublants que d\'élèves.']);
            }

            return $ligne;
        })->all();
        $maitres = collect($reference['maitres'])->map(fn ($m, $cycle) => [
            'permanents' => (int) ($data['maitres'][$cycle]['permanents'] ?? 0),
            'contractuels' => (int) ($data['maitres'][$cycle]['contractuels'] ?? 0),
            'salles' => isset($data['maitres'][$cycle]['salles']) ? (int) $data['maitres'][$cycle]['salles'] : null,
        ])->all();

        return ['ecole' => (string) $ecole->nomEcole, 'groupes' => $groupes, 'maitres' => $maitres]
            + collect($data)->only(['quartier', 'commune', 'cap', 'cercle', 'academie', 'langue', 'cycle', 'statut', 'enseignement', 'recrutement'])
                ->map(fn ($v, $k) => in_array($k, ['cycle', 'statut', 'enseignement', 'recrutement'], true) && $v !== null ? (int) $v : $v)
                ->all()
            + ['langue' => null, 'statut' => null, 'recrutement' => null];
    }

    /** Toujours disponible, même sans CAP sur KalanNet : la fiche remplie, à imprimer et remettre. */
    public function pdf(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $rapport = RapportSuccinct::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->first();

        return $this->fichier($rapport?->donnees ?? Donnees::calculer($ecole, (int) $annee->id_anneeScolaire), $annee->annee, (string) $ecole->nomEcole, !$request->boolean('voir'));
    }

    private function fichier(array $donnees, string $annee, string $nomEcole, bool $telecharger)
    {
        $pdf = Pdf::loadView('pdf.rapport_succinct', ['d' => $donnees, 'annee' => $annee, 'totaux' => Donnees::totaux($donnees)])->setPaper('a4', 'portrait');
        $nom = 'rapport-succinct-'.Str::slug($nomEcole).'-'.$annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /* ——— Espace du CAP ——— */

    private function dcap()
    {
        $user = Auth::user();
        abort_unless($user->droit === 'DCAP' && $user->id_cap, 403);

        return $user;
    }

    /** Écoles concernées du CAP, reçues ou non, pour l'année choisie. */
    public function territoire(Request $request)
    {
        $user = $this->dcap();
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.id_cap', $user->id_cap)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->whereIn('classe.ordreEnseignement', \App\Support\Ordres::SOUS_CAP))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse']);

        // Même année, commune ou créée par l'école elle-même.
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $rapports = RapportSuccinct::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('statut', '!=', 'brouillon')
            ->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->keyBy('id_ecole');

        $lignes = $ecoles->map(fn ($e) => (object) ['ecole' => $e, 'rapport' => $rapports->get($e->idEcole), 'totaux' => isset($rapports[$e->idEcole]) ? Donnees::totaux($rapports[$e->idEcole]->donnees_envoyees ?? []) : null]);

        return view('territoire.rapports-succincts', compact('lignes', 'annees', 'annee'));
    }

    /** La fiche remise, seulement pour une école du territoire. */
    public function pdfTerritoire(Request $request, int $id)
    {
        $user = $this->dcap();
        $rapport = RapportSuccinct::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->findOrFail($id);
        $ecole = DB::table('ecole')->where('idEcole', $rapport->id_ecole)->first(['nomEcole', 'id_cap']);
        abort_unless($ecole && (int) $ecole->id_cap === (int) $user->id_cap, 404);

        $annee = DB::table('anneescolaire')->where('id_anneeScolaire', $rapport->id_annee)->value('annee');

        return $this->fichier($rapport->donnees_envoyees, (string) $annee, (string) $ecole->nomEcole, $request->boolean('telecharger'));
    }
}
