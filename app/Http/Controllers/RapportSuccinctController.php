<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportSuccinct;
use App\Support\Ordres;
use App\Support\RapportSuccinctDonnees as Donnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Rapport succinct de rentrée : l'établissement vérifie la fiche pré-remplie, la télécharge à tout moment et la remet
 * par KalanNet si le destinataire a un compte, sinon sur papier. Préscolaire et fondamental : au CAP, suivi par le DCAP.
 * Secondaire, général ou technique : à l'académie, suivi par le DAE.
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
        abort_unless(Donnees::portees((int) $ecole->idEcole) !== [], 403, "Cet établissement n'a pas de classe concernée par le rapport succinct.");

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

    /** Fondamental (CAP) ou secondaire (académie) : ce que l'établissement remet, sinon 403. */
    private function portee(Request $request, Ecole $ecole): string
    {
        $portees = Donnees::portees((int) $ecole->idEcole);
        $demandee = $request->input('portee');

        return in_array($demandee, $portees, true) ? $demandee : $portees[0];
    }

    private function surKalanNet(Ecole $ecole, string $portee): bool
    {
        return $portee === 'secondaire' ? Donnees::academieSurKalanNet($ecole->id_academie) : Donnees::capSurKalanNet($ecole->id_cap);
    }

    private function rapport(Ecole $ecole, AnneeScolaire $annee, string $portee): ?RapportSuccinct
    {
        return RapportSuccinct::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('portee', $portee)->first();
    }

    public function index(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $portee = $this->portee($request, $ecole);
        $rapport = $this->rapport($ecole, $annee, $portee);
        $donnees = $rapport?->donnees ?? Donnees::calculer($ecole, (int) $annee->id_anneeScolaire, $portee);

        // « Reprendre les chiffres de KalanNet » : tableaux recalculés, le reste de la fiche est gardé.
        if ($request->boolean('recalculer')) {
            $calcul = Donnees::calculer($ecole, (int) $annee->id_anneeScolaire, $portee);
            $salles = collect($donnees['maitres'] ?? [])->map(fn ($m) => $m['salles'] ?? null);
            $donnees['groupes'] = $calcul['groupes'];
            $donnees['maitres'] = collect($calcul['maitres'])->map(fn ($m, $cycle) => ['salles' => $salles[$cycle] ?? null] + $m)->all();
            if ($portee === 'secondaire') {
                $donnees['enseignants'] = $calcul['enseignants'];
            }
        }
        // Fiche du secondaire enregistrée avant le modèle de l'académie : les nouveaux tableaux sont complétés par KalanNet.
        if ($portee === 'secondaire' && !isset($donnees['enseignants'])) {
            $calcul ??= Donnees::calculer($ecole, (int) $annee->id_anneeScolaire, $portee);
            $donnees += array_intersect_key($calcul, array_flip(['enseignants', 'salles_niveaux', 'telephone']));
            $donnees['groupes'] = collect($donnees['groupes'] ?? [])->map(fn ($l) => $l + ['niveau' => collect($calcul['groupes'])->firstWhere('cle', $l['cle'])['niveau'] ?? 'n1'])->all();
        }

        return view('rapports-succincts.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(), 'rapport' => $rapport, 'donnees' => $donnees,
            'portee' => $portee, 'portees' => Donnees::portees((int) $ecole->idEcole),
            'surKalanNet' => $this->surKalanNet($ecole, $portee),
            'manquants' => Donnees::champsManquants($donnees),
        ]);
    }

    /** Boutons du formulaire : enregistrer, enregistrer et télécharger, enregistrer et remettre. */
    public function store(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $portee = $this->portee($request, $ecole);
        $donnees = $this->valider($request, $ecole, (int) $annee->id_anneeScolaire, $portee);

        $rapport = RapportSuccinct::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'portee' => $portee]);
        $rapport->donnees = $donnees;
        $rapport->statut ??= 'brouillon';
        $rapport->save();

        $retour = ['annee' => $annee->id_anneeScolaire, 'portee' => $portee];
        if (in_array($request->input('action'), ['telecharger', 'excel'], true)) {
            return redirect()->route('rapport-succinct.pdf', $retour + ($request->input('action') === 'excel' ? ['format' => 'excel'] : []));
        }
        if ($request->input('action') === 'remettre') {
            return $this->remettre($rapport, $ecole, $annee, $donnees, $portee);
        }

        return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport enregistré.');
    }

    private function remettre(RapportSuccinct $rapport, Ecole $ecole, AnneeScolaire $annee, array $donnees, string $portee)
    {
        $retour = ['annee' => $annee->id_anneeScolaire, 'portee' => $portee];
        $manquants = Donnees::champsManquants($donnees);
        if ($manquants !== []) {
            throw ValidationException::withMessages(['rapport' => 'À compléter avant de remettre le rapport : '.implode(', ', $manquants).'.']);
        }

        $secondaire = $portee === 'secondaire';
        $destinataire = $secondaire ? 'votre académie' : 'votre CAP';
        $surKalanNet = $this->surKalanNet($ecole, $portee);
        $rapport->update([
            'statut' => $surKalanNet ? 'envoye' : 'remis', 'id_cap' => $secondaire ? null : $ecole->id_cap, 'id_academie' => $secondaire ? $ecole->id_academie : null,
            'donnees_envoyees' => $donnees, 'envoye_at' => now(), 'envoye_par' => Auth::id(),
        ]);

        if (!$surKalanNet) {
            return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport marqué comme remis à '.$destinataire.'.');
        }

        DB::table('utilisateurs')->when($secondaire,
            fn ($q) => $q->where('droit', 'DAE')->where('id_academie', $ecole->id_academie),
            fn ($q) => $q->where('droit', 'DCAP')->where('id_cap', $ecole->id_cap),
        )->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($superviseur) => AppNotification::create([
                'user_id' => $superviseur, 'type' => 'rapport_succinct', 'title' => 'Rapport succinct reçu',
                'message' => $ecole->nomEcole.' a envoyé son rapport succinct de rentrée '.$annee->annee.'.',
                'link' => route('territoire.rapports-succincts', ['annee' => $annee->id_anneeScolaire], false),
            ]));

        return redirect()->route('rapport-succinct.index', $retour)->with('success', 'Rapport envoyé à '.$destinataire.'.');
    }

    /** @return array<string, mixed> */
    private function valider(Request $request, Ecole $ecole, int $idAnnee, string $portee): array
    {
        $secondaire = $portee === 'secondaire';
        $entier = ['required', 'integer', 'min:0', 'max:99999'];
        $data = $request->validate([
            'quartier' => 'nullable|string|max:150', 'commune' => 'nullable|string|max:150', 'cap' => 'nullable|string|max:150',
            'cercle' => 'nullable|string|max:150', 'academie' => 'nullable|string|max:150', 'langue' => 'nullable|string|max:100',
            'cycle' => ['required', Rule::in(array_keys($secondaire ? Donnees::CYCLES_SECONDAIRE : Donnees::CYCLES))],
            'statut' => ['nullable', Rule::in(array_keys(Donnees::STATUTS))],
            'enseignement' => [$secondaire ? 'nullable' : 'required', Rule::in(array_keys(Donnees::ENSEIGNEMENTS))],
            'recrutement' => ['nullable', Rule::in(array_keys(Donnees::RECRUTEMENTS))],
            'groupes' => 'nullable|array', 'groupes.*.groupes' => $entier, 'groupes.*.g' => $entier, 'groupes.*.f' => $entier,
            'groupes.*.rg' => $entier, 'groupes.*.rf' => $entier,
            'maitres' => 'nullable|array', 'maitres.*.permanents' => $entier, 'maitres.*.contractuels' => $entier,
            'maitres.*.salles' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'telephone' => 'nullable|string|max:60',
            'enseignants' => 'nullable|array', 'enseignants.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'salles_niveaux' => 'nullable|array', 'salles_niveaux.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], ['quartier' => 'village / quartier', 'statut' => 'statut administratif', 'recrutement' => 'mode de recrutement']);

        // Lignes et cycles de référence : les libellés ne viennent jamais du formulaire.
        $reference = Donnees::calculer($ecole, $idAnnee, $portee);
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

        if ($secondaire) {
            // Modèle de l'académie : enseignants par genre et par statut, salles par niveau ; les totaux par cycle en découlent.
            $enseignants = collect(['ph', 'pf', 'vh', 'vf'])->mapWithKeys(fn ($c) => [$c => (int) ($data['enseignants'][$c] ?? 0)])->all();
            $salles = collect(['n1', 'n2', 'n3'])->mapWithKeys(fn ($n) => [$n => isset($data['salles_niveaux'][$n]) ? (int) $data['salles_niveaux'][$n] : null])->all();
            $maitres = ['general' => ['permanents' => $enseignants['ph'] + $enseignants['pf'], 'contractuels' => $enseignants['vh'] + $enseignants['vf'],
                'salles' => collect($salles)->filter(fn ($n) => $n !== null)->sum() ?: null]];

            return ['portee' => $portee, 'ecole' => (string) $ecole->nomEcole, 'groupes' => $groupes, 'maitres' => $maitres, 'enseignants' => $enseignants,
                'salles_niveaux' => $salles, 'telephone' => $data['telephone'] ?? null]
                + collect($data)->only(['quartier', 'commune', 'cercle', 'academie', 'cycle', 'statut'])
                    ->map(fn ($v, $k) => in_array($k, ['cycle', 'statut'], true) && $v !== null ? (int) $v : $v)->all()
                + ['statut' => null];
        }

        return ['portee' => $portee, 'ecole' => (string) $ecole->nomEcole, 'groupes' => $groupes, 'maitres' => $maitres]
            + collect($data)->only(['quartier', 'commune', 'cap', 'cercle', 'academie', 'langue', 'cycle', 'statut', 'enseignement', 'recrutement'])
                ->map(fn ($v, $k) => in_array($k, ['cycle', 'statut', 'enseignement', 'recrutement'], true) && $v !== null ? (int) $v : $v)
                ->all()
            + ['langue' => null, 'statut' => null, 'recrutement' => null];
    }

    /** Toujours disponible, même si le destinataire n'est pas sur KalanNet : la fiche remplie, à imprimer et remettre. */
    public function pdf(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $portee = $this->portee($request, $ecole);
        $rapport = $this->rapport($ecole, $annee, $portee);

        return $this->fichier($rapport?->donnees ?? Donnees::calculer($ecole, (int) $annee->id_anneeScolaire, $portee), $annee->annee, (string) $ecole->nomEcole, !$request->boolean('voir'), $request->input('format') === 'excel');
    }

    private function fichier(array $donnees, string $annee, string $nomEcole, bool $telecharger, bool $excel = false)
    {
        if ($excel) {
            return \App\Services\Rapports\ExportExcel::rapportSuccinct($donnees, $annee)->telecharger('rapport-succinct-'.Str::slug($nomEcole).'-'.$annee);
        }
        // Secondaire : modèle de l'académie (rapport succinct de rentrée ESG, cinq tableaux), en paysage pour les colonnes par série.
        $pdf = ($donnees['portee'] ?? '') === 'secondaire'
            ? Pdf::loadView('pdf.rapport_succinct_secondaire', ['d' => $donnees, 'annee' => $annee])->setPaper('a4', 'landscape')
            : Pdf::loadView('pdf.rapport_succinct', ['d' => $donnees, 'annee' => $annee, 'totaux' => Donnees::totaux($donnees)])->setPaper('a4', 'portrait');
        $nom = 'rapport-succinct-'.Str::slug($nomEcole).'-'.$annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /* ——— Espace du CAP et de l'académie ——— */

    /** Le DCAP suit le fondamental de son CAP, le DAE le secondaire de son académie. @return array{0: string, 1: int, 2: string} */
    private function superviseur(): array
    {
        $user = Auth::user();
        if ($user->droit === 'DCAP' && $user->id_cap) {
            return ['id_cap', (int) $user->id_cap, 'fondamental'];
        }
        abort_unless($user->droit === 'DAE' && $user->id_academie, 403);

        return ['id_academie', (int) $user->id_academie, 'secondaire'];
    }

    /** Établissements concernés du territoire, reçus ou non, pour l'année choisie. */
    public function territoire(Request $request)
    {
        [$colonne, $id, $portee] = $this->superviseur();
        $ordres = $portee === 'secondaire' ? Donnees::SECONDAIRE : Ordres::SOUS_CAP;
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.'.$colonne, $id)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->whereIn('classe.ordreEnseignement', $ordres))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse', 'e.typeEcole'])
            ->reject(fn ($e) => \App\Support\ModelesBulletin::estEcoleSante($e->typeEcole))->values();

        // Même année, commune ou créée par l'école elle-même.
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $rapports = RapportSuccinct::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('portee', $portee)->where('statut', '!=', 'brouillon')
            ->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->keyBy('id_ecole');

        $lignes = $ecoles->map(fn ($e) => (object) ['ecole' => $e, 'rapport' => $rapports->get($e->idEcole), 'totaux' => isset($rapports[$e->idEcole]) ? Donnees::totaux($rapports[$e->idEcole]->donnees_envoyees ?? []) : null]);

        return view('territoire.rapports-succincts', compact('lignes', 'annees', 'annee', 'portee'));
    }

    /** La fiche remise, seulement pour un établissement du territoire et de la bonne portée. */
    public function pdfTerritoire(Request $request, int $id)
    {
        [$colonne, $idTerritoire, $portee] = $this->superviseur();
        $rapport = RapportSuccinct::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->where('portee', $portee)->findOrFail($id);
        $ecole = DB::table('ecole')->where('idEcole', $rapport->id_ecole)->first(['nomEcole', 'id_cap', 'id_academie']);
        abort_unless($ecole && (int) $ecole->{$colonne} === $idTerritoire, 404);

        $annee = DB::table('anneescolaire')->where('id_anneeScolaire', $rapport->id_annee)->value('annee');

        return $this->fichier($rapport->donnees_envoyees, (string) $annee, (string) $ecole->nomEcole, $request->boolean('telecharger'), $request->input('format') === 'excel');
    }
}
