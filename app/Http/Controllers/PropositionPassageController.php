<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\PropositionPassage;
use App\Support\PropositionPassageDonnees as Donnees;
use App\Support\RapportSuccinctDonnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Proposition de passage, redoublement, exclusion : le conseil des maîtres propose par classe, l'école télécharge la
 * fiche à tout moment et la remet au CAP (par KalanNet si son CAP a un compte, sinon sur papier). Le DCAP décide élève
 * par élève ; la réinscription reprend ensuite sa décision.
 */
class PropositionPassageController extends Controller
{
    public static function autorise($user): bool
    {
        return $user && ($user->droit === 'SupAdmin' || $user->userHasPermission('proposition_passage_gerer'));
    }

    private function ecole(): Ecole
    {
        abort_unless(self::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(Donnees::ecoleConcernee((int) $ecole->idEcole), 403, 'La proposition de passage concerne le fondamental.');

        return $ecole;
    }

    private function annees()
    {
        return AnneeScolaire::whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
    }

    /** @return array{0: ?Classe, 1: AnneeScolaire} */
    private function contexte(Request $request, Ecole $ecole, $classes): array
    {
        $annee = $this->annees()->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? AnneeScolaire::enCours((int) $ecole->idEcole);
        abort_unless($annee, 404, "Aucune année scolaire n'est disponible.");

        return [$classes->firstWhere('id_classe', $request->integer('classe')), $annee];
    }

    public function index(Request $request)
    {
        $ecole = $this->ecole();
        $classes = Donnees::classes((int) $ecole->idEcole);
        [$classe, $annee] = $this->contexte($request, $ecole, $classes);

        $fiches = PropositionPassage::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->get()->keyBy('id_classe');
        $fiche = $classe ? $fiches->get($classe->id_classe) : null;
        $lignes = [];
        if ($classe) {
            $lignes = $fiche?->lignes ?? Donnees::calculer($classe, (int) $annee->id_anneeScolaire);
            if ($request->boolean('recalculer')) {
                // Chiffres repris de KalanNet ; les observations déjà écrites sont gardées.
                $calcul = Donnees::calculer($classe, (int) $annee->id_anneeScolaire);
                foreach ($calcul as $id => &$ligne) {
                    $ligne['observation'] = $lignes[$id]['observation'] ?? null;
                }
                unset($ligne);
                $lignes = $calcul;
            }
        }

        return view('propositions-passage.index', [
            'ecole' => $ecole, 'classes' => $classes, 'classe' => $classe, 'annee' => $annee, 'annees' => $this->annees(), 'fiches' => $fiches,
            'fiche' => $fiche, 'lignes' => $lignes, 'recap' => Donnees::recap($lignes),
            'capSurKalanNet' => RapportSuccinctDonnees::capSurKalanNet($ecole->id_cap),
            'directeur' => old('directeur', $fiche?->directeur ?? Auth::user()->nomPrenom),
        ]);
    }

    public function store(Request $request)
    {
        $ecole = $this->ecole();
        [$classe, $annee] = $this->contexte($request, $ecole, Donnees::classes((int) $ecole->idEcole));
        abort_unless($classe, 404);

        // Grandes classes : tout arrive dans un seul champ, la limite de champs de PHP ne peut pas tronquer la liste.
        if ($request->filled('lignes_json')) {
            $request->merge(['lignes' => json_decode((string) $request->input('lignes_json'), true) ?: []]);
        }
        $data = $request->validate([
            'directeur' => 'nullable|string|max:150',
            'lignes' => 'required|array',
            'lignes.*.proposition' => ['nullable', Rule::in(array_keys(Donnees::DECISIONS))],
            'lignes.*.scolarite' => 'nullable|integer|min:0|max:30',
            'lignes.*.doublees' => 'nullable|string|max:60',
            'lignes.*.pere' => 'nullable|string|max:150',
            'lignes.*.mere' => 'nullable|string|max:150',
            'lignes.*.observation' => 'nullable|string|max:255',
        ]);

        // L'identité et la moyenne viennent toujours de KalanNet, jamais du formulaire.
        $lignes = Donnees::calculer($classe, (int) $annee->id_anneeScolaire);
        foreach ($lignes as $id => &$ligne) {
            foreach (['proposition', 'scolarite', 'doublees', 'pere', 'mere', 'observation'] as $champ) {
                if (array_key_exists($champ, $data['lignes'][$id] ?? [])) {
                    $ligne[$champ] = $data['lignes'][$id][$champ];
                }
            }
            $ligne['scolarite'] = $ligne['scolarite'] !== null ? (int) $ligne['scolarite'] : null;
        }
        unset($ligne);
        $lignes = Donnees::trier($lignes);

        $fiche = PropositionPassage::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'id_classe' => $classe->id_classe]);
        $fiche->lignes = $lignes;
        $fiche->directeur = $data['directeur'] ?? null;
        $fiche->statut ??= 'brouillon';
        $fiche->save();

        $retour = ['classe' => $classe->id_classe, 'annee' => $annee->id_anneeScolaire];
        if ($request->input('action') === 'telecharger') {
            return redirect()->route('proposition-passage.pdf', $retour);
        }
        if ($request->input('action') === 'remettre') {
            $this->remettre($fiche, $ecole, $classe, $annee, $lignes);

            return redirect()->route('proposition-passage.index', $retour)->with('success', $fiche->statut === 'envoye' ? 'Proposition envoyée à votre CAP.' : 'Proposition marquée comme remise à votre CAP.');
        }

        return redirect()->route('proposition-passage.index', $retour)->with('success', 'Proposition enregistrée.');
    }

    private function remettre(PropositionPassage $fiche, Ecole $ecole, Classe $classe, AnneeScolaire $annee, array $lignes): void
    {
        if ($lignes === []) {
            throw ValidationException::withMessages(['lignes' => 'Aucun élève dans cette classe pour cette année.']);
        }
        $sansDecision = collect($lignes)->filter(fn ($l) => empty($l['proposition']));
        if ($sansDecision->isNotEmpty()) {
            throw ValidationException::withMessages(['lignes' => 'Choisissez une proposition pour : '.$sansDecision->map(fn ($l) => $l['prenom'].' '.$l['nom'])->take(5)->implode(', ').($sansDecision->count() > 5 ? '…' : '').'.']);
        }

        $surKalanNet = RapportSuccinctDonnees::capSurKalanNet($ecole->id_cap);
        // Une nouvelle remise remplace la précédente : le CAP décide sur la dernière version.
        $fiche->update(['statut' => $surKalanNet ? 'envoye' : 'remis', 'id_cap' => $ecole->id_cap, 'lignes_envoyees' => $lignes, 'envoye_at' => now(), 'envoye_par' => Auth::id(), 'valide_at' => null, 'valide_par' => null]);

        if ($surKalanNet) {
            DB::table('utilisateurs')->where('droit', 'DCAP')->where('id_cap', $ecole->id_cap)->where('statut', 1)->pluck('idUtilisateur')
                ->each(fn ($dcap) => AppNotification::create([
                    'user_id' => $dcap, 'type' => 'proposition_passage', 'title' => 'Proposition de passage reçue',
                    'message' => $ecole->nomEcole.' a envoyé la proposition de passage de la classe '.$classe->nom_classe.' ('.$annee->annee.').',
                    'link' => route('territoire.propositions-passage.show', $fiche->id, false),
                ]));
        }
    }

    /** Toujours disponible : la fiche remplie, à imprimer et remettre. */
    public function pdf(Request $request)
    {
        $ecole = $this->ecole();
        [$classe, $annee] = $this->contexte($request, $ecole, Donnees::classes((int) $ecole->idEcole));
        abort_unless($classe, 404);
        $fiche = PropositionPassage::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('id_classe', $classe->id_classe)->first();

        // Après la décision du CAP, la fiche porte sa décision.
        $lignes = $fiche?->validee() ? $fiche->lignes_envoyees : ($fiche?->lignes ?? Donnees::calculer($classe, (int) $annee->id_anneeScolaire));

        return $this->fichier($classe, $annee, $lignes, $fiche, !$request->boolean('voir'));
    }

    private function fichier(Classe $classe, $annee, array $lignes, ?PropositionPassage $fiche, bool $telecharger)
    {
        $pdf = Pdf::loadView('pdf.proposition_passage', [
            'e' => Donnees::entete($classe, $annee), 'lignes' => $lignes, 'recap' => Donnees::recap($lignes),
            'recapCap' => $fiche?->validee() ? Donnees::recap($lignes, 'decision_cap') : null, 'fiche' => $fiche,
            'directeur' => $fiche?->directeur ?? Auth::user()->nomPrenom, 'date' => $fiche?->envoye_at ?? now(),
        ])->setPaper('a4', 'landscape');
        $nom = 'proposition-passage-'.Str::slug($classe->nom_classe).'-'.$annee->annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /* ——— Espace du CAP ——— */

    private function dcap()
    {
        $user = Auth::user();
        abort_unless($user->droit === 'DCAP' && $user->id_cap, 403);

        return $user;
    }

    private function ficheDuCap(int $id): PropositionPassage
    {
        $fiche = PropositionPassage::withoutGlobalScopes()->whereIn('statut', ['envoye', 'remis', 'validee'])->findOrFail($id);
        abort_unless((int) DB::table('ecole')->where('idEcole', $fiche->id_ecole)->value('id_cap') === (int) $this->dcap()->id_cap, 404);

        return $fiche;
    }

    public function territoire(Request $request)
    {
        $user = $this->dcap();
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');

        $classes = DB::table('classe as c')->join('ecole as e', 'e.idEcole', '=', 'c.idEcole')->where('e.id_cap', $user->id_cap)->whereIn('c.ordreEnseignement', Donnees::ORDRES)
            ->orderBy('e.nomEcole')->orderBy('c.nom_classe')->get(['c.id_classe', 'c.nom_classe', 'e.idEcole', 'e.nomEcole']);
        $fiches = PropositionPassage::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('statut', '!=', 'brouillon')->whereIn('id_classe', $classes->pluck('id_classe'))->get()->keyBy('id_classe');

        $lignes = $classes->map(fn ($c) => (object) ['classe' => $c, 'fiche' => $fiches->get($c->id_classe), 'recap' => isset($fiches[$c->id_classe]) ? Donnees::recap($fiches[$c->id_classe]->lignes_envoyees ?? []) : null]);

        return view('territoire.propositions-passage', compact('lignes', 'annees', 'annee'));
    }

    public function show(int $id)
    {
        $fiche = $this->ficheDuCap($id);

        return view('territoire.proposition-passage', [
            'fiche' => $fiche, 'lignes' => $fiche->lignes_envoyees ?? [],
            'classe' => DB::table('classe')->where('id_classe', $fiche->id_classe)->value('nom_classe'),
            'ecole' => DB::table('ecole')->where('idEcole', $fiche->id_ecole)->value('nomEcole'),
            'annee' => DB::table('anneescolaire')->where('id_anneeScolaire', $fiche->id_annee)->value('annee'),
            'recap' => Donnees::recap($fiche->lignes_envoyees ?? []),
        ]);
    }

    /** Décision du CAP, élève par élève : préremplie avec la proposition du conseil des maîtres. */
    public function decider(Request $request, int $id)
    {
        $fiche = $this->ficheDuCap($id);
        $data = $request->validate([
            'decisions' => 'required|array',
            'decisions.*.decision' => ['required', Rule::in(array_keys(Donnees::DECISIONS))],
            'decisions.*.observation' => 'nullable|string|max:255',
        ]);

        $lignes = $fiche->lignes_envoyees ?? [];
        foreach ($lignes as $idEleve => &$ligne) {
            if (!isset($data['decisions'][$idEleve])) {
                throw ValidationException::withMessages(['decisions' => 'Une décision est demandée pour chaque élève.']);
            }
            $ligne['decision_cap'] = $data['decisions'][$idEleve]['decision'];
            $ligne['observation_cap'] = $data['decisions'][$idEleve]['observation'] ?? null;
        }
        unset($ligne);

        $fiche->update(['lignes_envoyees' => $lignes, 'statut' => 'validee', 'valide_at' => now(), 'valide_par' => Auth::id()]);

        $classe = DB::table('classe')->where('id_classe', $fiche->id_classe)->value('nom_classe');
        DB::table('utilisateurs')->where('idEcole', $fiche->id_ecole)->where('droit', 'Admin')->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($admin) => AppNotification::create([
                'user_id' => $admin, 'type' => 'proposition_passage_decision', 'title' => 'Décision du CAP reçue',
                'message' => 'Le CAP a rendu sa décision sur la proposition de passage de la classe '.$classe.'.',
                'link' => route('proposition-passage.index', ['classe' => $fiche->id_classe, 'annee' => $fiche->id_annee], false),
            ]));

        return redirect()->route('territoire.propositions-passage.show', $id)->with('success', 'Décision enregistrée et transmise à l\'école.');
    }

    public function pdfTerritoire(Request $request, int $id)
    {
        $fiche = $this->ficheDuCap($id);
        $classe = Classe::withoutGlobalScopes()->findOrFail($fiche->id_classe);
        $annee = DB::table('anneescolaire')->where('id_anneeScolaire', $fiche->id_annee)->first();

        return $this->fichier($classe, $annee, $fiche->lignes_envoyees ?? [], $fiche, $request->boolean('telecharger'));
    }

    /**
     * Décisions du CAP pour la réinscription (id_eleve => décision), seulement une fiche validée.
     *
     * @return array<int, array{decision: string, observation: ?string}>
     */
    public static function decisionsCap(int $idEcole, int $idClasse, int $idAnnee): array
    {
        $fiche = DB::table('propositions_passage')->where('id_ecole', $idEcole)->where('id_classe', $idClasse)->where('id_annee', $idAnnee)->where('statut', 'validee')->value('lignes_envoyees');

        return collect(json_decode((string) $fiche, true) ?: [])->filter(fn ($l) => !empty($l['decision_cap']))
            ->map(fn ($l) => ['decision' => $l['decision_cap'], 'observation' => $l['observation_cap'] ?? null])->all();
    }
}
