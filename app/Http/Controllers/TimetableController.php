<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\Classe;
use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\AnneeScolaire;
use App\Models\Ecole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        // Reset filter if requested
        if ($request->has('reset')) {
            session()->forget(['timetable_id_classe', 'timetable_id_annee']);
            return redirect()->route('pedagogie.timetable');
        }

        // Store selected options in session when coming from filter POST form
        if ($request->isMethod('post')) {
            if ($request->has('id_classe')) {
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
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->orderBy('nom_classe')
            ->get();
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();
        $matieres = Matiere::orderBy('nom_matiere')->get();
        $enseignants = Enseignant::query()
            ->where('is_deleted', 0)
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $idEcole))
            ->orderBy('nom_prenom_enseignant')
            ->get();
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;

        $timetable = [];
        $selectedClasse = $id_classe ? $classes->firstWhere('id_classe', (int) $id_classe) : null;
        $selectedAnnee = $id_annee ? $annees->firstWhere('id_anneeScolaire', (int) $id_annee) : null;

        if ($id_classe && $id_annee) {
            $timetable = EmploiDuTemps::with(['matiere', 'enseignant'])
                ->where('id_classe', $id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->get()
                ->groupBy('jour');
        }

        // Also fetch all courses for inline list edit
        $coursesList = collect();
        if ($id_classe && $id_annee) {
            $coursesList = EmploiDuTemps::with(['matiere', 'enseignant'])
                ->where('id_classe', $id_classe)
                ->where('id_annee_scolaire', $id_annee)
                ->get();
        }

        $recesses = [];
        if ($id_classe) {
            $recesses = session('timetable_recesses_' . $id_classe, []);
        }

        return view('pedagogie.timetable', compact('classes', 'annees', 'timetable', 'coursesList', 'matieres', 'enseignants', 'selectedClasse', 'selectedAnnee', 'ecole', 'recesses'));
    }

    public function store(Request $request)
    {
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
        $id_classe = session('timetable_id_classe');
        $id_annee = session('timetable_id_annee');

        if (!$id_classe || !$id_annee) {
            return back()->with('error', 'Sélectionnez d\'abord une classe et une année scolaire.');
        }

        $slots = $request->input('slots', []);

        foreach ($slots as $jour => $heuresData) {
            foreach ($heuresData as $heureKey => $data) {
                $id = $data['id'] ?? null;
                $id_matiere = $data['id_matiere'] ?? null;
                $id_enseignant = $data['id_enseignant'] ?? null;
                $heure_debut = $data['heure_debut'] ?? null;
                $heure_fin = $data['heure_fin'] ?? null;

                if ($id) {
                    if (empty($id_matiere)) {
                        // User cleared the slot
                        EmploiDuTemps::destroy($id);
                    } else {
                        // Update existing slot
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

        $recesses = $request->input('recesses', []);
        session(['timetable_recesses_' . $id_classe => $recesses]);

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

        $timetable = EmploiDuTemps::with(['matiere', 'enseignant'])
            ->where('id_classe', $id_classe)
            ->where('id_annee_scolaire', $id_annee)
            ->get()
            ->groupBy('jour');

        $recesses = session('timetable_recesses_' . $id_classe, []);

        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $heures = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

        $pdf = Pdf::loadView('pdf.timetable', compact('selectedClasse', 'selectedAnnee', 'ecole', 'timetable', 'recesses', 'jours', 'heures'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('Emploi_du_temps_' . str_replace(' ', '_', $selectedClasse->nom_classe) . '.pdf');
    }
}
