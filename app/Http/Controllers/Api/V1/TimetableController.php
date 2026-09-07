<?php

namespace App\Http\Controllers\Api\V1;

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
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManageTimetable();

        $request->validate([
            'id_classe' => 'required',
            'id_matiere' => 'required',
            'id_enseignant' => 'nullable',
            'id_annee_scolaire' => 'required',
            'jour' => 'required',
            'heure_debut' => 'required',
            'heure_fin' => 'required|after:heure_debut',
        ]);

        $course = EmploiDuTemps::create($request->all());

        return response()->json($course->load(['matiere', 'enseignant']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->ensureCanManageTimetable();

        $request->validate([
            'id_classe' => 'required',
            'id_matiere' => 'required',
            'id_enseignant' => 'nullable',
            'id_annee_scolaire' => 'required',
            'jour' => 'required',
            'heure_debut' => 'required',
            'heure_fin' => 'required|after:heure_debut',
        ]);

        $course = EmploiDuTemps::findOrFail($id);
        $course->update($request->all());

        return response()->json($course->fresh(['matiere', 'enseignant']));
    }

    public function destroy(Request $request, $id)
    {
        $this->ensureCanManageTimetable();

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

        $slots = $request->input('slots', []);
        $hourRows = [];

        foreach ($slots as $jour => $heuresData) {
            foreach ($heuresData as $heureKey => $data) {
                $id = $data['id'] ?? null;
                $id_matiere = $data['id_matiere'] ?? null;
                $id_enseignant = $data['id_enseignant'] ?? null;
                $heure_debut = $data['heure_debut'] ?? null;
                $heure_fin = $data['heure_fin'] ?? null;

                if (!empty($id_matiere) && $heure_debut && $heure_fin && !isset($hourRows[$heureKey])) {
                    $hourRows[$heureKey] = ['debut' => $heure_debut, 'fin' => $heure_fin];
                }

                if ($id) {
                    if (empty($id_matiere)) {
                        EmploiDuTemps::destroy($id);
                    } else {
                        $course = EmploiDuTemps::find($id);
                        if ($course) {
                            $course->update([
                                'id_matiere' => $id_matiere,
                                'id_enseignant' => $id_enseignant ?: null,
                                'heure_debut' => $heure_debut,
                                'heure_fin' => $heure_fin,
                            ]);
                        }
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

        $recesses = $request->input('recesses', []);
        session(['timetable_recesses_' . $id_classe => $recesses]);
        session([$this->hoursSessionKey($id_classe, $id_annee) => $hourRows]);

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
        $recesses = session('timetable_recesses_' . $id_classe, []);
        $storedHours = session($this->hoursSessionKey($id_classe, $id_annee), []);
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $heures = $this->pdfHours($storedHours, $courses);

        $pdf = Pdf::loadView('pdf.timetable', compact('selectedClasse', 'selectedAnnee', 'ecole', 'timetable', 'recesses', 'jours', 'heures'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream('Emploi_du_temps_' . str_replace(' ', '_', $selectedClasse->nom_classe) . '.pdf');
    }
}
