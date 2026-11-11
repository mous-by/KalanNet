<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\Ecole;
use App\Models\RapportSecondaire;
use App\Services\Rapports\CanevasOfficiel;
use App\Support\ModelesBulletin;
use App\Support\RapportSecondaireDefinitions as Def;
use App\Support\RapportSecondaireDonnees as Donnees;
use App\Support\RapportSuccinctDonnees;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rapports de rentrée et de fin d'année du secondaire (général, technique et professionnel) : pré-remplis, téléchargeables
 * à tout moment, remis à l'académie (par KalanNet si elle a un compte, sinon sur papier). Le DAE suit ceux de son académie.
 */
class RapportSecondaireController extends Controller
{
    private function ecole(): Ecole
    {
        abort_unless(RapportSuccinctController::autorise(Auth::user()), 403);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail(session('idEcole') ?: Auth::user()->idEcole);
        abort_unless(RapportSuccinctDonnees::ecoleConcernee((int) $ecole->idEcole, 'secondaire'), 403, 'Ces rapports concernent le secondaire.');

        return $ecole;
    }

    private function type(string $type): string
    {
        abort_unless(isset(Def::TYPES[$type]), 404);

        return $type;
    }

    private function annees()
    {
        return AnneeScolaire::whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
    }

    private function annee(Request $request, Ecole $ecole, string $type = 'rentree'): AnneeScolaire
    {
        $annee = $this->annees()->firstWhere('id_anneeScolaire', $request->integer('annee'))
            ?? ($type === 'fin' ? $this->anneeQuiSeTermine() : null)
            ?? AnneeScolaire::enCours((int) $ecole->idEcole);
        abort_unless($annee, 404, "Aucune année scolaire n'est disponible.");

        return $annee;
    }

    /**
     * Le rapport de fin d'année porte sur l'année qui se termine (fin août, d'après sa date de fin) : celle-ci à partir de
     * mi-mai, sinon celle qui vient de se terminer.
     */
    private function anneeQuiSeTermine(): ?AnneeScolaire
    {
        return $this->annees()->filter(fn ($a) => $a->date_fin && \Illuminate\Support\Carbon::parse($a->date_fin)->lte(now()->addDays(105)))
            ->sortByDesc('date_fin')->first();
    }

        private function rapport(Ecole $ecole, AnneeScolaire $annee, string $type): ?RapportSecondaire
    {
        return RapportSecondaire::where('id_ecole', $ecole->idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('type', $type)->first();
    }

    public function index(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole, $type);
        $rapport = $this->rapport($ecole, $annee, $type);
        $donnees = $rapport?->donnees ?? Donnees::calculer($ecole, $annee, $type);
        // Rapport enregistré avant l'ajout d'un tableau pré-rempli : ce tableau est complété avec KalanNet, le reste est gardé.
        if ($rapport && !$request->boolean('recalculer') && array_diff(Def::PRE_REMPLIS_AJOUTES[$type], array_keys($donnees['t'] ?? [])) !== []) {
            $calcul = Donnees::calculer($ecole, $annee, $type)['t'];
            foreach (Def::PRE_REMPLIS_AJOUTES[$type] as $code) {
                if (!isset($donnees['t'][$code]) && isset($calcul[$code])) {
                    $donnees['t'][$code] = $calcul[$code];
                }
            }
        }

        // « Reprendre les chiffres de KalanNet » : tableaux pré-remplis recalculés, le reste est gardé.
        if ($request->boolean('recalculer')) {
            $calcul = Donnees::calculer($ecole, $annee, $type);
            // Listes nominatives : les colonnes saisies par l'établissement (fonction, grade…) sont gardées.
            $listes = array_flip(Def::LISTES_PRE_REMPLIES);
            $donnees['t'] = array_replace($donnees['t'] ?? [], array_diff_key($calcul['t'], $listes));
            foreach (array_intersect_key($calcul['t'], $listes) as $code => $lignes) {
                $donnees['t'][$code] = array_replace_recursive($donnees['t'][$code] ?? [], $lignes);
            }
            foreach (['effectif_f', 'effectif_g', 'groupes', 'enseignants_f', 'enseignants_h'] as $champ) {
                $donnees['infos'][$champ] = $calcul['infos'][$champ];
            }
        }

        return view('rapports-secondaire.index', [
            'ecole' => $ecole, 'annee' => $annee, 'annees' => $this->annees(), 'rapport' => $rapport, 'donnees' => $donnees, 'type' => $type,
            'tableaux' => Donnees::tableaux($type, (int) $ecole->idEcole, $annee), 'infos' => Def::infos($type), 'redaction' => Def::redaction($type),
            'avecGeneral' => DB::table('classe')->where('idEcole', $ecole->idEcole)->where('ordreEnseignement', 'secondairegenerale')->exists(),
            'avecTechnique' => DB::table('classe')->where('idEcole', $ecole->idEcole)->where('ordreEnseignement', 'secondairetechniqueetprofessionnel')->exists(),
            'maquette' => $this->modeleChoisi($ecole),
            'surKalanNet' => RapportSuccinctDonnees::academieSurKalanNet($ecole->id_academie),
            'sansDate' => $type === 'rentree' ? Donnees::sansDateDeNaissance((int) $ecole->idEcole, (int) $annee->id_anneeScolaire) : 0,
            'sansDef' => $type === 'rentree' ? Donnees::sansAnneeDuDef((int) $ecole->idEcole, $annee) : 0,
            'manquants' => Donnees::champsManquants($donnees),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole, $type);

        $request->validate(['infos' => 'nullable|array', 'infos.*' => 'nullable|string|max:255', 'tableaux_json' => 'nullable|string',
            'redaction' => 'nullable|array', 'redaction.*' => 'nullable|string|max:20000']);
        $redaction = collect(Def::redaction($type))->map(fn ($r, $cle) => filled($request->input("redaction.$cle")) ? trim((string) $request->input("redaction.$cle")) : null)->all();
        $infos = collect(Def::infos($type))->map(fn ($l, $champ) => filled($request->input("infos.$champ")) ? trim((string) $request->input("infos.$champ")) : null)->all();
        // Tous les tableaux arrivent dans un seul champ : la limite de champs de PHP ne peut pas tronquer la saisie.
        $saisie = json_decode((string) $request->input('tableaux_json', '{}'), true);
        $donnees = ['type' => $type, 'infos' => $infos, 'redaction' => $redaction, 't' => Donnees::assainir(is_array($saisie) ? $saisie : [], Donnees::tableaux($type, (int) $ecole->idEcole, $annee))];

        $rapport = RapportSecondaire::firstOrNew(['id_ecole' => $ecole->idEcole, 'id_annee' => $annee->id_anneeScolaire, 'type' => $type]);
        $rapport->donnees = $donnees;
        $rapport->statut ??= 'brouillon';
        $rapport->save();

        $retour = ['type' => $type, 'annee' => $annee->id_anneeScolaire];
        if (in_array($request->input('action'), ['telecharger', 'excel'], true)) {
            return redirect()->route('rapport-secondaire.pdf', $retour + ($request->input('action') === 'excel' ? ['format' => 'excel'] : []));
        }
        if (in_array($request->input('action'), ['canevas_esg', 'canevas_choisi'], true)) {
            return redirect()->route('rapport-secondaire.canevas', $retour + ['modele' => $request->input('action') === 'canevas_esg' ? 'esg' : 'choisi']);
        }
        if ($request->input('action') !== 'remettre') {
            return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport enregistré.');
        }

        $manquants = Donnees::champsManquants($donnees);
        if ($manquants !== []) {
            throw ValidationException::withMessages(['rapport' => 'À compléter avant de remettre le rapport : '.implode(', ', $manquants).'.']);
        }
        $surKalanNet = RapportSuccinctDonnees::academieSurKalanNet($ecole->id_academie);
        $rapport->update(['statut' => $surKalanNet ? 'envoye' : 'remis', 'id_academie' => $ecole->id_academie, 'donnees_envoyees' => $donnees, 'envoye_at' => now(), 'envoye_par' => Auth::id()]);
        if (!$surKalanNet) {
            return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport marqué comme remis à votre académie.');
        }
        DB::table('utilisateurs')->where('droit', 'DAE')->where('id_academie', $ecole->id_academie)->where('statut', 1)->pluck('idUtilisateur')
            ->each(fn ($dae) => AppNotification::create([
                'user_id' => $dae, 'type' => 'rapport_secondaire', 'title' => Def::TYPES[$type].' reçu',
                'message' => $ecole->nomEcole.' a envoyé son '.Str::lower(Def::TYPES[$type]).' '.$annee->annee.'.',
                'link' => route('territoire.rapports-secondaire', ['type' => $type, 'annee' => $annee->id_anneeScolaire], false),
            ]));

        return redirect()->route('rapport-secondaire.index', $retour)->with('success', 'Rapport envoyé à votre académie.');
    }

    /**
     * Brouillon de Kalanbot pour une rubrique de la partie administrative, à partir de ce qui est à l'écran (même non
     * enregistré). Rien n'est enregistré ici : l'établissement relit, corrige, puis enregistre son rapport.
     */
    public function kalanbot(Request $request, string $type, \App\Services\Rapports\RedactionKalanbot $redaction)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole, $type);
        $request->validate(['rubrique' => 'required|string|max:40', 'infos' => 'nullable|array', 'infos.*' => 'nullable|string|max:255',
            'redaction' => 'nullable|array', 'redaction.*' => 'nullable|string|max:20000', 'tableaux_json' => 'nullable|string']);

        $tableaux = Donnees::tableaux($type, (int) $ecole->idEcole, $annee);
        $saisie = json_decode((string) $request->input('tableaux_json', '{}'), true);
        $donnees = ['type' => $type, 'infos' => (array) $request->input('infos', []), 'redaction' => (array) $request->input('redaction', []),
            't' => Donnees::assainir(is_array($saisie) ? $saisie : [], $tableaux)];
        $resultat = $redaction->proposer($type, (string) $request->input('rubrique'), $donnees, $tableaux, (string) $ecole->nomEcole, (string) $annee->annee);

        return response()->json($resultat, $resultat['ok'] ? 200 : 503);
    }

    /**
     * Série de chaque classe du général, choisie dans une liste : les rapports se fient à ce choix, plus au nom de la classe.
     * Chaque classe arrive avec la série reconnue d'après son nom ; l'établissement vérifie et enregistre.
     */
    public function seriesClasses(Request $request)
    {
        $ecole = $this->ecole();
        $classes = DB::table('classe')->where('idEcole', $ecole->idEcole)->where('ordreEnseignement', 'secondairegenerale')
            ->orderBy('nom_classe')->get(['id_classe', 'nom_classe', 'serie'])
            ->map(function ($c) {
                $c->choisie = isset(\App\Support\Series::RAPPORTS[$c->serie]) && \App\Support\RapportSuccinctDonnees::serie($c->nom_classe, $c->serie) === \App\Support\Series::RAPPORTS[$c->serie][2];
                $c->proposee = \App\Support\Series::codePourCle(\App\Support\RapportSuccinctDonnees::serie($c->nom_classe, $c->serie));

                return $c;
            });

        return view('rapports-secondaire.series', ['ecole' => $ecole, 'classes' => $classes, 'retour' => $request->query('retour')]);
    }

    public function enregistrerSeries(Request $request)
    {
        $ecole = $this->ecole();
        $data = $request->validate(['serie' => 'required|array', 'serie.*' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Support\Series::RAPPORTS))]],
            ['serie.*.required' => 'Choisissez la série de chaque classe.', 'serie.*.in' => 'Série inconnue.']);
        $n = 0;
        foreach ($data['serie'] as $idClasse => $code) {
            $n += DB::table('classe')->where('id_classe', (int) $idClasse)->where('idEcole', $ecole->idEcole)->where('ordreEnseignement', 'secondairegenerale')->update(['serie' => $code]);
        }
        $retour = $request->input('retour');

        return redirect()->route('rapport-secondaire.series', $retour ? ['retour' => $retour] : [])
            ->with('success', "Séries enregistrées ({$n} classe(s) modifiée(s)). Les rapports classent désormais vos classes d'après ces séries. Pensez à « Reprendre les chiffres de KalanNet » dans vos rapports.");
    }

    /** Classes du général dont la série n'a pas été choisie dans la liste (bandeau des rapports). */
    public static function classesSansSerie(int $idEcole): int
    {
        return DB::table('classe')->where('idEcole', $idEcole)->where('ordreEnseignement', 'secondairegenerale')
            ->where(fn ($q) => $q->whereNull('serie')->orWhereNotIn('serie', array_keys(\App\Support\Series::RAPPORTS)))->count();
    }

    /** Année du DEF (CG) des élèves du général, classe par classe : la scolarité des tableaux T9 et T11 en dépend. */
    public function anneesDef(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        $classes = array_filter(Donnees::lignes((int) $ecole->idEcole)['classes'], fn ($cle) => !str_starts_with($cle, 'c'));
        $eleves = DB::table('eleve as e')->join('classe as c', 'c.id_classe', '=', 'e.id_classe')
            ->where('e.id_ecole', $ecole->idEcole)->where('e.id_annee', $annee->id_anneeScolaire)->where('e.etat_dossier', 0)
            ->whereIn('e.id_classe', array_keys($classes))->orderBy('c.nom_classe')->orderBy('e.nom_eleve')->orderBy('e.prenom_eleve')
            ->get(['e.id_eleve', 'e.prenom_eleve', 'e.nom_eleve', 'e.matricule', 'e.annee_def', 'c.nom_classe', 'c.id_classe']);

        return view('rapports-secondaire.def', ['ecole' => $ecole, 'annee' => $annee, 'parClasse' => $eleves->groupBy('nom_classe'),
            'proposees' => Donnees::anneesDefProposees((int) $ecole->idEcole, $annee),
            'debut' => (int) substr((string) $annee->date_debut, 0, 4)]);
    }

    public function enregistrerAnneesDef(Request $request)
    {
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole);
        // Les années arrivent dans un seul champ JSON (grands lycées : la limite de champs de PHP ne tronque rien) ; def[] reste accepté.
        $json = json_decode((string) $request->input('def_json', ''), true);
        if (is_array($json)) {
            $request->merge(['def' => $json]);
        }
        $data = $request->validate(['def' => 'nullable|array', 'def.*' => 'nullable|integer|between:1980,'.(date('Y') + 1)],
            ['def.*.between' => "Une année du DEF doit être comprise entre 1980 et l'an prochain.", 'def.*.integer' => 'Une année du DEF doit être une année (ex. 2026).']);
        $n = 0;
        $remplies = 0;
        foreach ($data['def'] ?? [] as $idEleve => $valeur) {
            $valeur = $valeur !== null && $valeur !== '' ? (int) $valeur : null;
            $remplies += $valeur ? 1 : 0;
            $n += DB::table('eleve')->where('id_eleve', (int) $idEleve)->where('id_ecole', $ecole->idEcole)->update(['annee_def' => $valeur]);
        }

        return redirect()->route('rapport-secondaire.def', ['annee' => $annee->id_anneeScolaire])
            ->with('success', "Enregistré : {$remplies} élève(s) ont leur année du DEF ({$n} modification(s)). Dans le rapport de rentrée, cliquez sur « Reprendre les chiffres de KalanNet » pour mettre à jour T9.");
    }

    public function pdf(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole, $type);
        $donnees = $this->rapport($ecole, $annee, $type)?->donnees ?? Donnees::calculer($ecole, $annee, $type);

        return $this->fichier($donnees, $type, (int) $ecole->idEcole, $annee, !$request->boolean('voir'), $request->input('format') === 'excel');
    }

    private function academie(int $idEcole): string
    {
        return (string) DB::table('ecole as e')->leftJoin('academie as a', 'a.id_academie', '=', 'e.id_academie')->where('e.idEcole', $idEcole)->value(DB::raw('COALESCE(a.nom_academie, e.academie)'));
    }

    private function fichier(array $donnees, string $type, int $idEcole, object $annee, bool $telecharger, bool $excel = false)
    {
        $annexes = Donnees::annexes($type, $idEcole, $annee);
        if ($excel) {
            return \App\Services\Rapports\ExportExcel::rapportSecondaire($donnees, $type, (string) $annee->annee, Donnees::tableaux($type, $idEcole, $annee), $annexes)
                ->telecharger('rapport-'.$type.'-'.Str::slug($donnees['infos']['nom'] ?? 'etablissement').'-'.$annee->annee);
        }
        $pdf = Pdf::loadView('pdf.rapport_secondaire', [
            'd' => $donnees, 'type' => $type, 'annee' => (string) $annee->annee, 'academie' => $this->academie($idEcole),
            'tableaux' => Donnees::tableaux($type, $idEcole, $annee), 'infos' => Def::infos($type), 'redaction' => Def::redaction($type), 'annexes' => $annexes,
        ])->setPaper('a4', 'landscape');
        $nom = 'rapport-'.$type.'-'.Str::slug($donnees['infos']['nom'] ?? 'etablissement').'-'.$annee->annee.'.pdf';

        return $telecharger ? $pdf->download($nom) : $pdf->stream($nom);
    }

    /**
     * Le canevas officiel du ministère, rempli avec le rapport : « esg » pour le général, « etp » pour le technique
     * (dans la maquette choisie par l'école).
     */
    /** Modèle de l'établissement : celui qu'il a choisi, sinon l'ESG s'il a des classes du général. */
    private function modeleChoisi(Ecole $ecole): ?string
    {
        if (isset(CanevasOfficiel::MODELES[$ecole->maquette_etp])) {
            return $ecole->maquette_etp;
        }

        return DB::table('classe')->where('idEcole', $ecole->idEcole)->where('ordreEnseignement', 'secondairegenerale')->exists() ? 'esg' : null;
    }

    private function canevasReponse(string $demande, string $type, array $donnees, Ecole $ecole, object $annee)
    {
        // « choisi » : le modèle de l'établissement ; sinon un modèle nommé (esg, est, esp, esp_pro, agro).
        $modele = $demande === 'choisi' ? $this->modeleChoisi($ecole) : $demande;
        abort_unless(isset(CanevasOfficiel::MODELES[$modele]), 404, 'Choisissez d\'abord le modèle de rapport demandé par votre académie.');
        $limite = ini_get('memory_limit');
        if ($limite !== '-1' && ini_parse_quantity($limite) < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        $classes = Donnees::lignes((int) $ecole->idEcole)['classes'];
        $classeur = (new CanevasOfficiel)->remplir($modele, $type, $donnees, $ecole, (string) $annee->annee, $this->academie((int) $ecole->idEcole), CanevasOfficiel::enseignants((int) $ecole->idEcole, $classes),
            $type === 'fin' ? Donnees::annexes('fin', (int) $ecole->idEcole, $annee) : []);
        $ecrivain = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($classeur, 'Xlsx');
        $ecrivain->setPreCalculateFormulas(false); // Excel recalcule les formules du canevas à l'ouverture
        $chemin = tempnam(sys_get_temp_dir(), 'canevas');
        $ecrivain->save($chemin);
        $classeur->disconnectWorksheets();

        return response()->download($chemin, 'canevas-'.$modele.'-'.$type.'-'.Str::slug($ecole->nomEcole).'-'.$annee->annee.'.xlsx')->deleteFileAfterSend();
    }

    public function canevas(Request $request, string $type)
    {
        $type = $this->type($type);
        $ecole = $this->ecole();
        $annee = $this->annee($request, $ecole, $type);
        return $this->canevasReponse((string) $request->input('modele', 'choisi'), $type, $this->rapport($ecole, $annee, $type)?->donnees ?? Donnees::calculer($ecole, $annee, $type), $ecole, $annee);
    }

    /** L'école choisit la maquette officielle de son technique et professionnel ; elle est gardée pour les années suivantes. */
    public function maquette(Request $request)
    {
        $ecole = $this->ecole();
        $data = $request->validate(['maquette' => ['required', \Illuminate\Validation\Rule::in(array_keys(CanevasOfficiel::MODELES))]]);
        DB::table('ecole')->where('idEcole', $ecole->idEcole)->update(['maquette_etp' => $data['maquette']]);

        return back()->with('success', 'Modèle enregistré : '.CanevasOfficiel::MODELES[$data['maquette']].'.');
    }

    /* ——— Espace de l'académie ——— */

    private function dae()
    {
        $user = Auth::user();
        abort_unless($user->droit === 'DAE' && $user->id_academie, 403);

        return $user;
    }

    public function territoire(Request $request)
    {
        $user = $this->dae();
        $type = isset(Def::TYPES[$request->input('type')]) ? $request->input('type') : 'rentree';
        $annees = DB::table('anneescolaire')->whereNull('id_ecole')->whereDate('date_debut', '<=', now()->toDateString())->orderByDesc('date_debut')->get();
        $annee = $annees->firstWhere('id_anneeScolaire', $request->integer('annee')) ?? $annees->first();

        $ecoles = DB::table('ecole as e')->where('e.id_academie', $user->id_academie)
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('classe')->whereColumn('classe.idEcole', 'e.idEcole')->whereIn('classe.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE))
            ->orderBy('e.nomEcole')->get(['e.idEcole', 'e.nomEcole', 'e.adresse', 'e.typeEcole', 'e.maquette_etp'])
            ->reject(fn ($e) => ModelesBulletin::estEcoleSante($e->typeEcole))->values();
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee?->annee)->pluck('id_anneeScolaire');
        $rapports = RapportSecondaire::withoutGlobalScopes()->whereIn('id_annee', $idsAnnee)->where('type', $type)->where('statut', '!=', 'brouillon')
            ->whereIn('id_ecole', $ecoles->pluck('idEcole'))->get()->keyBy('id_ecole');
        $lignes = $ecoles->map(fn ($e) => (object) ['ecole' => $e, 'rapport' => $rapports->get($e->idEcole)]);

        return view('territoire.rapports-secondaire', compact('lignes', 'annees', 'annee', 'type'));
    }

    public function pdfTerritoire(Request $request, int $id)
    {
        $user = $this->dae();
        $rapport = RapportSecondaire::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->findOrFail($id);
        abort_unless((int) DB::table('ecole')->where('idEcole', $rapport->id_ecole)->value('id_academie') === (int) $user->id_academie, 404);

        return $this->fichier($rapport->donnees_envoyees, $rapport->type, (int) $rapport->id_ecole, DB::table('anneescolaire')->where('id_anneeScolaire', $rapport->id_annee)->first(), $request->boolean('telecharger'), $request->input('format') === 'excel');
    }

    /** L'académie télécharge aussi le canevas officiel rempli avec le rapport reçu. */
    public function canevasTerritoire(Request $request, int $id)
    {
        $user = $this->dae();
        $rapport = RapportSecondaire::withoutGlobalScopes()->where('statut', '!=', 'brouillon')->findOrFail($id);
        $ecole = Ecole::withoutGlobalScopes()->findOrFail($rapport->id_ecole);
        abort_unless((int) $ecole->id_academie === (int) $user->id_academie, 404);

        return $this->canevasReponse((string) $request->input('modele', 'choisi'), $rapport->type, $rapport->donnees_envoyees, $ecole, DB::table('anneescolaire')->where('id_anneeScolaire', $rapport->id_annee)->first());
    }
}
