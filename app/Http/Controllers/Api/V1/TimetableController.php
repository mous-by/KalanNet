<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\GrilleEmploiDuTemps;
use App\Http\Controllers\TimetableController as WebTimetableController;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\EmploiDuTemps;
use App\Models\LigneClasse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TimetableController extends WebTimetableController
{
    /**
     * id_classe/id_annee are passed explicitly on every call instead of the
     * web version's session-remembered filter, since an API client manages
     * its own UI state locally (no server-side "current selection" memory).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $idEcole = session('idEcole') ?: $user->idEcole;
        $isTeacher = $user->droit === 'enseignant';

        $classes = Classe::query()
            ->when($isTeacher, fn ($query) => $query->whereIn(
                'id_classe',
                LigneClasse::where('id_enseignants', $user->id_enseignant)->pluck('id_classe')
            ))
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->orderBy('nom_classe')
            ->get();

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $idClasse = $request->integer('id_classe') ?: null;
        $idAnnee = $request->integer('id_annee') ?: null;

        $selectedClasse = $idClasse ? $classes->firstWhere('id_classe', $idClasse) : null;
        $selectedAnnee = $idAnnee ? $annees->firstWhere('id_anneeScolaire', $idAnnee) : null;

        $lignesClasse = collect();
        if ($selectedClasse) {
            $lignesClasse = LigneClasse::with(['matiere', 'enseignant'])
                ->where('id_classe', $selectedClasse->id_classe)
                ->when($isTeacher, fn ($query) => $query->where('id_enseignants', $user->id_enseignant))
                ->orderBy('id_ligneclasse')
                ->get();
        }

        $timetable = collect();
        if ($idClasse && $idAnnee) {
            $timetable = EmploiDuTemps::with(['matiere', 'enseignant'])
                ->where('id_classe', $idClasse)
                ->where('id_annee_scolaire', $idAnnee)
                ->when($isTeacher, fn ($query) => $query->where('id_enseignant', $user->id_enseignant))
                ->get()
                ->groupBy('jour');
        }

        return response()->json([
            'classes' => $classes,
            'annees' => $annees,
            'selected_classe' => $selectedClasse,
            'selected_annee' => $selectedAnnee,
            'lignes_classe' => $lignesClasse,
            'timetable' => $timetable,
            // Horaires des lignes et récréations de la classe (nouvelle clé, ignorée par les anciennes versions).
            'grille' => $idClasse && $idAnnee ? collect(GrilleEmploiDuTemps::disposer((int) $idClasse, (int) $idAnnee, collect($timetable)->flatten(1)))->only(['horaires', 'recreations'])->all() : null,
        ]);
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

        return response()->json($course->load(['matiere', 'enseignant']), 201);
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

        return response()->json($course->fresh(['matiere', 'enseignant']));
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureCanManageTimetable();

        $this->authorizeCourseForTimetable((int) $id);
        EmploiDuTemps::destroy($id);

        return response()->json(['success' => true]);
    }

    /**
     * Grid save: takes id_classe/id_annee explicitly (request body) instead
     * of the web version's session-remembered selection.
     */
    public function saveGrid(Request $request)
    {
        $this->ensureCanManageTimetable();

        $id_classe = $request->input('id_classe');
        $id_annee = $request->input('id_annee');

        if (!$id_classe || !$id_annee) {
            return response()->json(['message' => 'id_classe et id_annee sont requis.'], 422);
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
                    $hourRows[$heureKey] = ['debut' => $heure_debut, 'fin' => $heure_fin];
                }

                if ($id) {
                    // Ignore any slot id that doesn't actually belong to the
                    // classe verified above — a forged payload could
                    // otherwise smuggle in a foreign course id.
                    $course = EmploiDuTemps::find($id);
                    if (!$course || (int) $course->id_classe !== (int) $id_classe) {
                        continue;
                    }

                    if (empty($id_matiere)) {
                        $course->delete();
                    } else {
                        $course->update([
                            'id_matiere' => $id_matiere,
                            'id_enseignant' => $id_enseignant ?: null,
                            'heure_debut' => $heure_debut,
                            'heure_fin' => $heure_fin,
                        ]);
                    }
                } elseif (!empty($id_matiere)) {
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

        GrilleEmploiDuTemps::enregistrer((int) $id_classe, (int) $id_annee, $hourRows, (array) $request->input('recesses', []));

        return response()->json(['success' => true]);
    }

    /**
     * PDF export: id_classe/id_annee passed explicitly as query params
     * instead of relying on the web version's session-remembered selection.
     */
    public function downloadPDF(Request $request)
    {
        $id_classe = $request->integer('id_classe') ?: null;
        $id_annee = $request->integer('id_annee') ?: null;

        if (!$id_classe || !$id_annee) {
            return response()->json(['message' => "id_classe et id_annee sont requis."], 422);
        }

        $user = $request->user();
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

        $pdf = Pdf::loadView('pdf.timetable', compact('selectedClasse', 'selectedAnnee', 'ecole', 'timetable', 'recesses', 'jours', 'heures', 'placement') + ['modele' => \App\Support\ModelesEdt::normaliser($request->query('modele')), 'couleur' => $request->boolean('couleur'), 'signataireChoisi' => mb_substr(trim((string) $request->query('signataire', '')), 0, 80) ?: null]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Emploi_du_temps_' . str_replace(' ', '_', $selectedClasse->nom_classe) . '.pdf');
    }
}
