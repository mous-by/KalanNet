<?php

namespace App\Http\Controllers;

use App\Support\GrilleEmploiDuTemps;
use App\Models\EmploiDuTemps;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use App\Models\LigneClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;
        $isTeacher = $user->droit === 'enseignant';
        $canEditTimetable = !$isTeacher && ($user->droit === 'SupAdmin' || $user->userHasPermission('planning_creation'));

        // Reset filter if requested
        if ($request->has('reset')) {
            session()->forget(['timetable_id_classe', 'timetable_id_annee']);
            return redirect()->route('pedagogie.timetable');
        }

        // Store selected options in session when coming from filter POST form
        if ($request->isMethod('post')) {
            if ($request->filled('id_classe')) {
                $this->authorizeClasseForTimetable((int) $request->input('id_classe'));
                session(['timetable_id_classe' => $request->input('id_classe')]);
            }
            if ($request->has('id_annee')) {
                session(['timetable_id_annee' => $request->input('id_annee')]);
            }
            return redirect()->route('pedagogie.timetable');
        }

        $id_classe = session('timetable_id_classe');
        $id_annee = session('timetable_id_annee');

        $classes = Classe::query()
            ->when($isTeacher, function ($query) use ($user) {
                $query->whereIn('id_classe', LigneClasse::where('id_enseignants', $user->id_enseignant)->pluck('id_classe'));
            })
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->orderBy('nom_classe')
            ->get();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        // Seulement les matières des classes de l'école (catalogue franco-arabe,
        // classique ou École de Santé selon l'école).
        $matieres = Matiere::query()
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->whereIn('id_matiere', LigneClasse::whereIn('id_classe', $classes->pluck('id_classe'))->pluck('id_matiere')))
            ->orderBy('nom_matiere')
            ->get();
        $enseignants = Enseignant::query()
            ->where('is_deleted', 0)
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $idEcole))
            ->orderBy('nom_prenom_enseignant')
            ->get();
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;

        if ($isTeacher) {
            if (!$id_classe && $classes->isNotEmpty()) {
                $id_classe = $classes->first()->id_classe;
                session(['timetable_id_classe' => $id_classe]);
            }
            if (!$id_annee && $annees->isNotEmpty()) {
                $id_annee = $annees->first()->id_anneeScolaire;
                session(['timetable_id_annee' => $id_annee]);
            }
        }

        $timetable = [];
        $selectedClasse = $id_classe ? $classes->firstWhere('id_classe', (int) $id_classe) : null;
        if ($isTeacher && !$selectedClasse && $classes->isNotEmpty()) {
            $id_classe = $classes->first()->id_classe;
            session(['timetable_id_classe' => $id_classe]);
            $selectedClasse = $classes->first();
        }
        $selectedAnnee = $id_annee ? $annees->firstWhere('id_anneeScolaire', (int) $id_annee) : null;
        $lignesClasse = collect();
        $matieresForGrid = $matieres;
        $teacherSuggestionMap = [];

        if ($selectedClasse) {
            $lignesClasse = LigneClasse::with(['matiere', 'enseignant'])
                ->where('id_classe', $selectedClasse->id_classe)
                ->when($isTeacher, fn ($query) => $query->where('id_enseignants', $user->id_enseignant))
                ->orderBy('id_ligneclasse')
                ->get();

            // Seulement les matières de la classe (rien d'autre si elle n'en a pas encore).
            $matieresForGrid = $lignesClasse
                ->pluck('matiere')
                ->filter()
                ->unique('id_matiere')
                ->values();

            if ($lignesClasse->isNotEmpty()) {

                $teacherSuggestionMap = $lignesClasse
                    ->filter(fn ($ligne) => $ligne->id_matiere && $ligne->id_enseignants)
                    ->mapWithKeys(fn ($ligne) => [(string) $ligne->id_matiere => (string) $ligne->id_enseignants])
                    ->all();
            }
        }

        if ($id_classe && $id_annee) {
            $timetable = EmploiDuTemps::with(['matiere', 'enseignant'])
                ->where('id_classe', $id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->when($isTeacher, fn ($query) => $query->where('id_enseignant', $user->id_enseignant))
                ->get()
                ->groupBy('jour');
        }

        // Also fetch all courses for inline list edit
        $coursesList = collect();
        if ($id_classe && $id_annee) {
            $coursesList = EmploiDuTemps::with(['matiere', 'enseignant'])
                ->where('id_classe', $id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->when($isTeacher, fn ($query) => $query->where('id_enseignant', $user->id_enseignant))
                ->get();
        }

        // Un cours déjà placé garde sa matière dans la liste (même retirée depuis de la classe),
        // sinon l'enregistrement de la grille l'effacerait.
        if ($selectedClasse) {
            $matieresForGrid = $matieresForGrid->merge($coursesList->pluck('matiere')->filter())->unique('id_matiere')->values();
        }

        // Grille : horaires des lignes et récréations enregistrés en base, et une case pour chaque cours
        // (y compris ceux saisis sur l'application mobile, à des heures libres).
        $disposition = $id_classe && $id_annee
            ? GrilleEmploiDuTemps::disposer((int) $id_classe, (int) $id_annee, collect($timetable)->flatten(1))
            : ['lignes' => array_fill_keys(array_slice(GrilleEmploiDuTemps::LIGNES, 0, GrilleEmploiDuTemps::LIGNES_MIN), []), 'horaires' => [], 'recreations' => [], 'placement' => []];
        $recesses = $disposition['recreations'];
        $storedHourRows = $disposition['horaires'];
        $lignesGrille = array_keys($disposition['lignes']);
        $placement = $disposition['placement'];

        return view('pedagogie.timetable', compact('classes', 'annees', 'timetable', 'coursesList', 'matieres', 'matieresForGrid', 'enseignants', 'selectedClasse', 'selectedAnnee', 'ecole', 'recesses', 'storedHourRows', 'lignesGrille', 'placement', 'lignesClasse', 'teacherSuggestionMap', 'canEditTimetable', 'isTeacher'));
    }

    public function store(Request $request)
    {
        $this->normaliserHeures($request);
        $this->ensureCanManageTimetable();

        $data = $request->validate([
            'id_classe' => 'required',
            'id_matiere' => 'required',
            'id_enseignant' => 'nullable',
            'id_annee_scolaire' => 'required',
            'jour' => 'required',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
        ]);

        $this->authorizeClasseForTimetable((int) $data['id_classe']);
        \App\Support\MatiereClasse::verifier((int) $data['id_classe'], $data['id_matiere']);

        $course = EmploiDuTemps::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cours ajouté à l\'emploi du temps.',
                'course' => $course->load(['matiere', 'enseignant'])
            ]);
        }

        return back()->with('success', 'Cours ajouté à l\'emploi du temps.');
    }

    public function update(Request $request, $id)
    {
        $this->normaliserHeures($request);
        $this->ensureCanManageTimetable();

        $data = $request->validate([
            'id_classe' => 'required',
            'id_matiere' => 'required',
            'id_enseignant' => 'nullable',
            'id_annee_scolaire' => 'required',
            'jour' => 'required',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
        ]);

        $course = $this->authorizeCourseForTimetable((int) $id);
        $this->authorizeClasseForTimetable((int) $data['id_classe']);
        \App\Support\MatiereClasse::verifier((int) $data['id_classe'], $data['id_matiere']);
        $course->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cours mis à jour.',
                'course' => $course->load(['matiere', 'enseignant'])
            ]);
        }

        return back()->with('success', 'Cours mis à jour.');
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureCanManageTimetable();

        $this->authorizeCourseForTimetable((int) $id);
        EmploiDuTemps::destroy($id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cours supprimé.'
            ]);
        }

        return back()->with('success', 'Cours supprimé.');
    }

    public function saveGrid(Request $request)
    {
        $this->ensureCanManageTimetable();

        $id_classe = session('timetable_id_classe');
        $id_annee = session('timetable_id_annee');

        if (!$id_classe || !$id_annee) {
            return back()->with('error', 'Sélectionnez d\'abord une classe et une année scolaire.');
        }

        $this->authorizeClasseForTimetable((int) $id_classe);

        $slots = $request->input('slots', []);
        \App\Support\MatiereClasse::verifierGrille((int) $id_classe, (int) $id_annee, (array) $slots);

        $hourRows = [];

        foreach ($slots as $jour => $heuresData) {
            foreach ($heuresData as $heureKey => $data) {
                $id = $data['id'] ?? null;
                $id_matiere = $data['id_matiere'] ?? null;
                $id_enseignant = $data['id_enseignant'] ?? null;
                $heure_debut = GrilleEmploiDuTemps::normaliserHeure($data['heure_debut'] ?? null);
                $heure_fin = GrilleEmploiDuTemps::normaliserHeure($data['heure_fin'] ?? null);

                if (!empty($id_matiere) && $heure_debut && $heure_fin && !isset($hourRows[$heureKey])) {
                    $hourRows[$heureKey] = [
                        'debut' => $heure_debut,
                        'fin' => $heure_fin,
                    ];
                }

                if ($id) {
                    // Ignore any slot id that doesn't actually belong to the
                    // classe this grid was verified for above — a forged
                    // payload could otherwise smuggle in a foreign course id.
                    $course = EmploiDuTemps::find($id);
                    if (!$course || (int) $course->id_classe !== (int) $id_classe) {
                        continue;
                    }

                    if (empty($id_matiere)) {
                        // User cleared the slot
                        $course->delete();
                    } else {
                        // Update existing slot
                        $course->update([
                            'id_matiere' => $id_matiere,
                            'id_enseignant' => $id_enseignant ?: null,
                            'heure_debut' => $heure_debut,
                            'heure_fin' => $heure_fin,
                        ]);
                    }
                } else {
                    if (!empty($id_matiere)) {
                        // Create new slot
                        EmploiDuTemps::create([
                            'id_classe' => $id_classe,
                            'id_annee_scolaire' => $id_annee,
                            'id_matiere' => $id_matiere,
                            'id_enseignant' => $id_enseignant ?: null,
                            'jour' => $jour,
                            'heure_debut' => $heure_debut,
                            'heure_fin' => $heure_fin,
                        ]);
                    }
                }
            }
        }

        GrilleEmploiDuTemps::enregistrer((int) $id_classe, (int) $id_annee, $hourRows, (array) $request->input('recesses', []));

        return back()->with('success', 'Emploi du temps enregistré avec succès.');
    }

    public function downloadPDF(Request $request)
    {
        $id_classe = session('timetable_id_classe');
        $id_annee = session('timetable_id_annee');
        
        if (!$id_classe || !$id_annee) {
            return back()->with('error', "Veuillez d'abord sélectionner une classe et une année scolaire.");
        }

        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $selectedClasse = Classe::findOrFail($id_classe);
        $selectedAnnee = AnneeScolaire::findOrFail($id_annee);
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;

        $courses = EmploiDuTemps::with(['matiere', 'enseignant'])
            ->where('id_classe', $id_classe)
            ->where('id_annee_scolaire', $id_annee)
            ->when($user->droit === 'enseignant', fn ($query) => $query->where('id_enseignant', $user->id_enseignant))
            ->orderBy('heure_debut')
            ->get();

        $timetable = $courses->groupBy('jour');

        $disposition = GrilleEmploiDuTemps::disposer((int) $id_classe, (int) $id_annee, $courses);
        $recesses = $disposition['recreations'];
        $placement = $disposition['placement'];

        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $heures = $disposition['horaires'];

        $pdf = Pdf::loadView('pdf.timetable', compact('selectedClasse', 'selectedAnnee', 'ecole', 'timetable', 'recesses', 'jours', 'heures', 'placement') + ['modele' => \App\Support\ModelesEdt::normaliser($request->query('modele')), 'couleur' => $request->boolean('couleur')]);
        $pdf->setPaper('a4', 'landscape');
        
        $fichier = 'Emploi_du_temps_' . str_replace(' ', '_', $selectedClasse->nom_classe) . '.pdf';

        // ?affichage=1 : affiché dans la page (aperçu d'un modèle, impression directe).
        return $request->boolean('affichage') ? $pdf->stream($fichier) : $pdf->download($fichier);
    }

    protected function ensureCanManageTimetable(): void
    {
        $user = Auth::user();
        if (!$user || $user->droit === 'enseignant' || ($user->droit !== 'SupAdmin' && !$user->userHasPermission('planning_creation'))) {
            abort(403, 'Permission insuffisante.');
        }
    }

    /**
     * Verifies a classe belongs to the current user's school before it is
     * used to create/modify an EmploiDuTemps row — EmploiDuTemps has no
     * school column/scope of its own, so without this check any account
     * with planning_creation could read or write another school's timetable
     * just by passing a foreign id_classe or a foreign course id.
     */
    protected function authorizeClasseForTimetable(int $idClasse): Classe
    {
        $user = Auth::user();
        if ($user->droit === 'SupAdmin') {
            return Classe::findOrFail($idClasse);
        }

        $idEcole = session('idEcole') ?: $user->idEcole;

        return Classe::where('idEcole', $idEcole)->findOrFail($idClasse);
    }

    protected function authorizeCourseForTimetable(int $id): EmploiDuTemps
    {
        $course = EmploiDuTemps::findOrFail($id);
        $this->authorizeClasseForTimetable($course->id_classe);

        return $course;
    }

    /** Heures saisies librement (application mobile : « 8:00 », « 8h00 ») ramenées au format 08:00. */
    protected function normaliserHeures(Request $request): void
    {
        $request->merge(collect(['heure_debut', 'heure_fin'])
            ->filter(fn ($champ) => $request->filled($champ))
            ->mapWithKeys(fn ($champ) => [$champ => GrilleEmploiDuTemps::normaliserHeure($request->input($champ)) ?? $request->input($champ)])
            ->all());
    }
}
