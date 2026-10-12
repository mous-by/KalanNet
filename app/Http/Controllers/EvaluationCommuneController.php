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
 * Évaluations communes (étape 1) : le DAE ou le DCAP crée l'évaluation et
 * dépose le sujet ; les écoles visées le téléchargent à partir de l'heure de
 * libération ; le corrigé leur est ouvert après l'épreuve.
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
            'niveaux' => DB::table('classes_officielles')->orderBy('ordre_enseignement')->orderBy('id_classe_officielle')->get(['id_classe_officielle', 'nom_classe_officielle', 'ordre_enseignement']),
            'matieres' => DB::table('matiere')->whereNull('id_ecole')->orderBy('nom_matiere')->get(['id_matiere', 'nom_matiere']),
            'caps' => $user->droit === 'DAE' ? DB::table('cap')->where('id_academie', $user->id_academie)->orderBy('nom_cap')->get(['id_cap', 'nom_cap']) : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->superviseur();
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'id_classe_officielle' => 'nullable|integer|exists:classes_officielles,id_classe_officielle',
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
        ]);

        $debut = Carbon::parse($data['debut_at']);
        $sujet = $this->ranger($request->file('sujet'));
        $corrige = $request->hasFile('corrige') ? $this->ranger($request->file('corrige')) : null;

        $id = DB::transaction(function () use ($user, $data, $debut, $sujet, $corrige) {
            $id = DB::table('evaluations_communes')->insertGetId([
                'origine' => $user->droit === 'DAE' ? 'academie' : 'cap',
                'id_academie' => $user->droit === 'DAE' ? $user->id_academie : DB::table('cap')->where('id_cap', $user->id_cap)->value('id_academie'),
                'id_cap' => $user->droit === 'DCAP' ? $user->id_cap : null,
                'id_utilisateur' => $user->idUtilisateur,
                'titre' => $data['titre'],
                'id_classe_officielle' => $data['id_classe_officielle'] ?? null,
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

            if ($user->droit === 'DAE') {
                foreach (array_unique($data['caps'] ?? []) as $idCap) {
                    DB::table('evaluation_commune_caps')->insert(['id_evaluation' => $id, 'id_cap' => $idCap]);
                }
            }

            $this->annoncer($user, $id, $data['titre'], $debut, $data['caps'] ?? []);

            return $id;
        });

        return redirect()->route('territoire.evaluations.show', $id)->with('success', 'Évaluation commune publiée. Les écoles visées ont été prévenues.');
    }

    public function show(int $id)
    {
        $user = $this->superviseur();
        $evaluation = EvaluationsCommunes::pourTerritoire($user)->firstWhere('id', $id);
        abort_unless($evaluation, 404);

        return view('evaluations-communes.territoire.show', [
            'evaluation' => $evaluation,
            'gerable' => EvaluationsCommunes::peutGerer($user, $evaluation),
            // Un DCAP qui suit une évaluation de l'académie ne voit que ses écoles.
            'ecoles' => EvaluationsCommunes::suivi($evaluation, $user->droit === 'DCAP' && $evaluation->origine === 'academie' ? (int) $user->id_cap : null),
        ]);
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

        EvaluationsCommunes::accuser($evaluation->id, $idEcole, (int) Auth::id(), true);

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

    /** Prévient les Admin des écoles visées par une annonce (académie, CAP choisis, ou CAP). */
    private function annoncer($user, int $id, string $titre, Carbon $debut, array $caps): void
    {
        $annonce = [
            'titre' => 'Évaluation commune : '.$titre,
            'contenu' => "Épreuve le {$debut->format('d/m/Y à H:i')}. Le sujet sera téléchargeable dans « Évaluations communes » à l'heure de libération, par l'Admin ou le Gestionnaire de l'école.",
            'public_cible' => 'admin',
            'id_utilisateur' => $user->idUtilisateur,
            'date_publication' => now(),
            'statut_annonce' => 'active',
            'type_annonce' => 'pedagogique',
        ];

        if ($user->droit === 'DCAP') {
            DB::table('annonces_cap')->insert($annonce + ['id_cap' => $user->id_cap]);
        } elseif ($caps) {
            foreach (array_unique($caps) as $idCap) {
                DB::table('annonces_cap')->insert($annonce + ['id_cap' => $idCap]);
            }
        } else {
            DB::table('annonces_academie')->insert($annonce + ['id_academie' => $user->id_academie]);
        }
    }
}
