<?php

namespace App\Http\Controllers;

use App\Support\Territoire\EvaluationsCommunes;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Évaluations communes (étape 1). Fondamental : DAE → CAP → écoles (le DCAP
 * publie à ses écoles). Secondaire : DAE → établissements directement. Les
 * écoles téléchargent le sujet à l'heure de libération, le corrigé après
 * l'épreuve.
 */
class EvaluationCommuneController extends Controller
{
    private const FICHIERS = 'mimes:pdf,doc,docx,jpg,jpeg,png|max:20480';

    /* ---------------------------- DAE / DCAP ---------------------------- */

    public function index()
    {
        $user = $this->superviseur();

        return view('evaluations-communes.territoire.index', [
            'evaluations' => EvaluationsCommunes::pourTerritoire($user),
            // Le DCAP ne gère que le fondamental ; le DAE, tous les ordres.
            'niveaux' => DB::table('classes_officielles')
                ->when($user->droit === 'DCAP', fn ($q) => $q->whereIn('ordre_enseignement', EvaluationsCommunes::ORDRES_FONDAMENTAL))
                ->orderBy('ordre_enseignement')->orderBy('id_classe_officielle')
                ->get(['id_classe_officielle', 'nom_classe_officielle', 'ordre_enseignement']),
            'matieres' => DB::table('matiere')->whereNull('id_ecole')->orderBy('nom_matiere')->get(['id_matiere', 'nom_matiere']),
            'caps' => $user->droit === 'DAE' ? DB::table('cap')->where('id_academie', $user->id_academie)->orderBy('nom_cap')->get(['id_cap', 'nom_cap']) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->superviseur();
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'id_classe_officielle' => 'required|integer|exists:classes_officielles,id_classe_officielle',
            'id_matiere' => 'nullable|integer|exists:matiere,id_matiere',
            'debut_at' => 'required|date|after:now',
            'duree_minutes' => 'required|integer|min:10|max:600',
            'liberation_minutes' => 'required|integer|min:0|max:10080',
            'consignes' => 'nullable|string|max:5000',
            'sujet' => 'required|file|'.self::FICHIERS,
            'corrige' => 'nullable|file|'.self::FICHIERS,
            'caps' => 'nullable|array',
            'caps.*' => ['integer', Rule::exists('cap', 'id_cap')->where('id_academie', $user->id_academie)],
        ], [
            'debut_at.after' => "La date de l'épreuve doit être dans le futur.",
            'sujet.required' => 'Le sujet est obligatoire.',
            'id_classe_officielle.required' => "Choisissez le niveau : il détermine si l'évaluation passe par les CAP (fondamental) ou va directement aux établissements (secondaire).",
        ]);

        $ordre = DB::table('classes_officielles')->where('id_classe_officielle', $data['id_classe_officielle'])->value('ordre_enseignement');
        $fondamental = in_array($ordre, EvaluationsCommunes::ORDRES_FONDAMENTAL, true);
        if ($user->droit === 'DCAP' && !$fondamental) {
            return back()->withInput()->withErrors(['id_classe_officielle' => "Un CAP n'organise que des évaluations du fondamental."]);
        }
        $capsVises = [];
        if ($user->droit === 'DAE' && $fondamental) {
            // Fondamental : adressé aux CAP cochés, ou à tous les CAP de l'académie.
            $capsVises = array_values(array_unique($data['caps'] ?? [])) ?: DB::table('cap')->where('id_academie', $user->id_academie)->pluck('id_cap')->all();
            if (!$capsVises) {
                return back()->withInput()->withErrors(['caps' => "Votre académie n'a aucun CAP enregistré."]);
            }
        }

        $debut = Carbon::parse($data['debut_at']);
        $sujet = $this->ranger($request->file('sujet'));
        $corrige = $request->hasFile('corrige') ? $this->ranger($request->file('corrige')) : null;

        $id = DB::transaction(function () use ($user, $data, $debut, $sujet, $corrige, $ordre, $capsVises) {
            $id = DB::table('evaluations_communes')->insertGetId([
                'origine' => $user->droit === 'DAE' ? 'academie' : 'cap',
                'id_academie' => $user->droit === 'DAE' ? $user->id_academie : DB::table('cap')->where('id_cap', $user->id_cap)->value('id_academie'),
                'id_cap' => $user->droit === 'DCAP' ? $user->id_cap : null,
                'id_utilisateur' => $user->idUtilisateur,
                'titre' => $data['titre'],
                'id_classe_officielle' => $data['id_classe_officielle'],
                'ordre_enseignement' => $ordre,
                'niveau_libelle' => isset($data['id_classe_officielle']) ? trim((string) DB::table('classes_officielles')->where('id_classe_officielle', $data['id_classe_officielle'])->value('nom_classe_officielle')) : null,
                'id_matiere' => $data['id_matiere'] ?? null,
                'matiere_libelle' => isset($data['id_matiere']) ? DB::table('matiere')->where('id_matiere', $data['id_matiere'])->value('nom_matiere') : null,
                'debut_at' => $debut,
                'duree_minutes' => $data['duree_minutes'],
                'liberation_sujet_at' => $debut->copy()->subMinutes((int) $data['liberation_minutes']),
                'consignes' => $data['consignes'] ?? null,
                'sujet_fichier' => $sujet['chemin'],
                'sujet_nom' => $sujet['nom'],
                'corrige_fichier' => $corrige['chemin'] ?? null,
                'corrige_nom' => $corrige['nom'] ?? null,
                'statut' => 'publiee',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($capsVises as $idCap) {
                DB::table('evaluation_commune_caps')->insert(['id_evaluation' => $id, 'id_cap' => $idCap]);
            }

            $this->annoncer($user, $data['titre'], $debut, $capsVises);

            return $id;
        });

        $message = match (true) {
            $user->droit === 'DCAP' => 'Évaluation publiée à vos écoles.',
            (bool) $capsVises => 'Évaluation adressée à '.count($capsVises).' CAP. Chaque DCAP la publiera à ses écoles.',
            default => "Évaluation envoyée aux établissements du secondaire de l'académie.",
        };

        return redirect()->route('territoire.evaluations.show', $id)->with('success', $message);
    }

    public function show(int $id)
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        return view('evaluations-communes.territoire.show', [
            'evaluation' => $evaluation,
            'gerable' => EvaluationsCommunes::peutGerer($user, $evaluation),
            'aPublier' => EvaluationsCommunes::aPublierParDcap($user, $evaluation),
            'passeParCap' => EvaluationsCommunes::passeParCap($evaluation),
            // Le DAE suit, CAP par CAP, qui a publié à ses écoles.
            'caps' => $user->droit === 'DAE' && $evaluation->origine === 'academie' ? EvaluationsCommunes::caps($evaluation) : collect(),
            // Un DCAP qui suit une évaluation de l'académie ne voit que ses écoles.
            'ecoles' => EvaluationsCommunes::suivi($evaluation, $user->droit === 'DCAP' && $evaluation->origine === 'academie' ? (int) $user->id_cap : null),
        ]);
    }

    /** Le DCAP publie à ses écoles une évaluation du fondamental reçue de l'académie. */
    public function publierAuxEcoles(Request $request, int $id)
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation && EvaluationsCommunes::aPublierParDcap($user, $evaluation), 403);
        $data = $request->validate(['consignes_cap' => 'nullable|string|max:5000']);

        DB::transaction(function () use ($user, $evaluation, $data) {
            DB::table('evaluation_commune_caps')->where('id_evaluation', $evaluation->id)->where('id_cap', $user->id_cap)
                ->update(['relayee_at' => now(), 'relayee_par' => $user->idUtilisateur, 'consignes_cap' => $data['consignes_cap'] ?? null]);
            DB::table('annonces_cap')->insert($this->annonce($user, $evaluation->titre, Carbon::parse($evaluation->debut_at)) + ['id_cap' => $user->id_cap]);
        });

        return back()->with('success', 'Évaluation publiée à vos écoles. Leurs Admin ont été prévenus.');
    }

    public function storeCorrige(Request $request, int $id)
    {
        $evaluation = $this->gerable($id);
        $request->validate(['corrige' => 'required|file|'.self::FICHIERS]);

        if ($evaluation->corrige_fichier) {
            Storage::delete($evaluation->corrige_fichier);
        }
        $corrige = $this->ranger($request->file('corrige'));
        DB::table('evaluations_communes')->where('id', $id)->update(['corrige_fichier' => $corrige['chemin'], 'corrige_nom' => $corrige['nom'], 'updated_at' => now()]);

        return back()->with('success', "Corrigé enregistré. Il sera visible par les écoles après la fin de l'épreuve.");
    }

    public function annuler(int $id)
    {
        $this->gerable($id);
        DB::table('evaluations_communes')->where('id', $id)->update(['statut' => 'annulee', 'updated_at' => now()]);

        return back()->with('success', "Évaluation annulée : les écoles n'y ont plus accès.");
    }

    /** Le DAE / DCAP télécharge son propre sujet ou corrigé, à tout moment. */
    public function telechargerTerritoire(int $id, string $piece)
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        return $this->servir($evaluation, $piece);
    }

    /* ------------------------------ École ------------------------------ */

    public function ecoleIndex()
    {
        $idEcole = $this->ecoleAutorisee();

        return view('evaluations-communes.ecole', [
            'evaluations' => EvaluationsCommunes::pourEcole($idEcole),
            'accuses' => DB::table('evaluation_commune_accuses')->where('id_ecole', $idEcole)->get()->keyBy('id_evaluation'),
        ]);
    }

    public function telechargerEcole(int $id, string $piece)
    {
        $idEcole = $this->ecoleAutorisee();
        $evaluation = EvaluationsCommunes::pourEcole($idEcole)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        if ($piece === 'sujet' && !$evaluation->sujet_disponible) {
            return back()->with('error', 'Le sujet sera disponible le '.Carbon::parse($evaluation->liberation_sujet_at)->format('d/m/Y à H:i').'.');
        }
        if ($piece === 'corrige' && !$evaluation->corrige_disponible) {
            return back()->with('error', "Le corrigé n'est pas encore disponible.");
        }

        EvaluationsCommunes::accuser($evaluation->id, $idEcole, (int) Auth::id());

        return $this->servir($evaluation, $piece);
    }

    /* ---------------------------- Outils ---------------------------- */

    private function superviseur()
    {
        $user = Auth::user();
        abort_unless(
            ($user->droit === 'DAE' && $user->id_academie) || ($user->droit === 'DCAP' && $user->id_cap),
            403
        );

        return $user;
    }

    private function gerable(int $id): object
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation && EvaluationsCommunes::peutGerer($user, $evaluation), 403);

        return $evaluation;
    }

    /** Sujet confidentiel : réservé à l'Admin et au Gestionnaire de l'école. */
    private function ecoleAutorisee(): int
    {
        $user = Auth::user();
        $idEcole = (int) (session('idEcole') ?: $user->idEcole);
        abort_unless($idEcole && in_array($user->droit, ['Admin', 'Gestionnaire', 'SupAdmin'], true), 403);

        return $idEcole;
    }

    private function ranger($fichier): array
    {
        $nom = Str::uuid().'.'.strtolower($fichier->getClientOriginalExtension());

        return ['chemin' => $fichier->storeAs(EvaluationsCommunes::DOSSIER, $nom), 'nom' => $fichier->getClientOriginalName()];
    }

    private function servir(object $evaluation, string $piece)
    {
        abort_unless(in_array($piece, ['sujet', 'corrige'], true), 404);
        $chemin = $piece === 'sujet' ? $evaluation->sujet_fichier : $evaluation->corrige_fichier;
        abort_unless($chemin && Storage::exists($chemin), 404);

        return Storage::download($chemin, $piece === 'sujet' ? $evaluation->sujet_nom : $evaluation->corrige_nom);
    }

    /**
     * Prévient les bonnes personnes : DCAP → Admin de ses écoles ; DAE, fondamental →
     * les DCAP de l'académie ; DAE, secondaire → les Admin de l'académie.
     */
    private function annoncer($user, string $titre, Carbon $debut, array $capsVises): void
    {
        if ($user->droit === 'DCAP') {
            DB::table('annonces_cap')->insert($this->annonce($user, $titre, $debut) + ['id_cap' => $user->id_cap]);

            return;
        }

        if ($capsVises) {
            DB::table('annonces_academie')->insert([
                'id_academie' => $user->id_academie,
                'titre' => 'Évaluation commune à publier : '.$titre,
                'contenu' => "Épreuve le {$debut->format('d/m/Y à H:i')}. Retrouvez-la dans « Évaluations communes » et publiez-la à vos écoles.",
                'public_cible' => 'dcap',
                'id_utilisateur' => $user->idUtilisateur,
                'date_publication' => now(),
                'statut_annonce' => 'active',
                'type_annonce' => 'pedagogique',
            ]);

            return;
        }

        DB::table('annonces_academie')->insert($this->annonce($user, $titre, $debut) + ['id_academie' => $user->id_academie]);
    }

    private function annonce($user, string $titre, Carbon $debut): array
    {
        return [
            'titre' => 'Évaluation commune : '.$titre,
            'contenu' => "Épreuve le {$debut->format('d/m/Y à H:i')}. Le sujet sera téléchargeable dans « Évaluations communes » à l'heure de libération, par l'Admin ou le Gestionnaire de l'école.",
            'public_cible' => 'admin',
            'id_utilisateur' => $user->idUtilisateur,
            'date_publication' => now(),
            'statut_annonce' => 'active',
            'type_annonce' => 'pedagogique',
        ];
    }
}
