<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ResultatNationalController as WebResultatNationalController;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResultatNationalController extends WebResultatNationalController
{
    /**
     * Read + record results for now; file import (PDF/Excel parsing) left
     * for later — niche, seasonal workflow, lower priority for mobile.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess();

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classes = $this->examClasses($schoolId, $user);
        $filters = $request->only(['id_classe', 'id_annee', 'niveau_examen']);
        $selectedClasse = $classes->firstWhere('id_classe', (int) ($filters['id_classe'] ?? 0));
        $niveauExamen = ($filters['niveau_examen'] ?? null) ?: ($selectedClasse ? $this->examLevel($selectedClasse) : null);
        $eleves = collect();
        $resultats = collect();

        if ($selectedClasse && !empty($filters['id_annee']) && $niveauExamen) {
            $eleves = Eleve::where('id_ecole', $schoolId)
                ->where('id_classe', $selectedClasse->id_classe)
                ->where('id_annee', (int) $filters['id_annee'])
                ->where('etat_dossier', 0)
                ->orderBy('prenom_eleve')->orderBy('nom_eleve')
                ->get();

            $resultats = DB::table('resultats_def_terminal')
                ->whereIn('id_eleve', $eleves->pluck('id_eleve'))
                ->where('id_annee', (int) $filters['id_annee'])
                ->where('niveau_examen', $niveauExamen)
                ->get()
                ->keyBy('id_eleve');
        }

        return response()->json([
            'classes' => $classes,
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'examens_disponibles' => $this->allowedExamLevels($schoolId),
            'selected_classe' => $selectedClasse,
            'niveau_examen' => $niveauExamen,
            'eleves' => $eleves,
            'resultats' => $resultats,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess();

        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'niveau_examen' => 'required|string|in:DEF,BAC',
            'date_resultat' => 'nullable|date',
            'resultats' => 'required|array',
            'resultats.*.decision' => 'nullable|string|in:admis,échec,echec',
            'resultats.*.moyenne' => 'nullable|numeric|min:0|max:20',
            'resultats.*.observation' => 'nullable|string|max:255',
        ]);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classe = Classe::where('idEcole', $schoolId)->findOrFail((int) $data['id_classe']);
        $this->ensureExamAllowed($schoolId, $data['niveau_examen'], $classe);

        $students = Eleve::where('id_ecole', $schoolId)
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', (int) $data['id_annee'])
            ->pluck('id_eleve')
            ->map(fn ($id) => (int) $id)
            ->all();

        $saved = 0;
        DB::transaction(function () use ($data, $students, &$saved) {
            foreach ($data['resultats'] as $studentId => $row) {
                $studentId = (int) $studentId;
                if (!in_array($studentId, $students, true) || empty($row['decision'])) {
                    continue;
                }

                $decision = $this->normalizeDecision($row['decision']);
                if (!$decision) {
                    continue;
                }

                DB::table('resultats_def_terminal')->updateOrInsert(
                    ['id_eleve' => $studentId, 'id_annee' => (int) $data['id_annee'], 'niveau_examen' => $data['niveau_examen']],
                    [
                        'decision' => $decision,
                        'moyenne' => $row['moyenne'] ?? null,
                        'observation' => $row['observation'] ?? null,
                        'date_resultat' => $data['date_resultat'] ?? now()->toDateString(),
                        'id_classe' => (int) $data['id_classe'],
                    ]
                );
                $saved++;
            }
        });

        return response()->json(['success' => true, 'saved' => $saved]);
    }

    public function import(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess();

        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'niveau_examen' => 'required|string|in:DEF,BAC',
            'date_resultat' => 'nullable|date',
            'fichier_resultats' => 'required|file|mimes:xls,xlsx,csv,txt,pdf|max:10240',
        ]);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $classe = Classe::where('idEcole', $schoolId)->findOrFail((int) $data['id_classe']);
        $this->ensureExamAllowed($schoolId, $data['niveau_examen'], $classe);

        $students = Eleve::where('id_ecole', $schoolId)
            ->where('id_classe', $classe->id_classe)
            ->where('id_annee', (int) $data['id_annee'])
            ->get()
            ->keyBy(fn ($student) => Str::upper(trim((string) $student->matricule)));

        if ($students->isEmpty()) {
            return response()->json(['message' => 'Aucun élève actif trouvé pour cette classe et cette année.'], 422);
        }

        $extension = Str::lower($request->file('fichier_resultats')->getClientOriginalExtension());
        $rows = $extension === 'pdf'
            ? $this->rowsFromPdf($request->file('fichier_resultats')->getRealPath(), $students)
            : $this->rowsFromSpreadsheet($request->file('fichier_resultats')->getRealPath(), $extension);

        $saved = 0;
        $ignored = 0;
        DB::transaction(function () use ($rows, $students, $data, &$saved, &$ignored) {
            foreach ($rows as $row) {
                $matricule = Str::upper(trim((string) ($row['matricule'] ?? '')));
                $student = $students->get($matricule);
                $decision = $this->normalizeDecision((string) ($row['decision'] ?? ''));

                if (!$student || !$decision) {
                    $ignored++;
                    continue;
                }

                DB::table('resultats_def_terminal')->updateOrInsert(
                    ['id_eleve' => $student->id_eleve, 'id_annee' => (int) $data['id_annee'], 'niveau_examen' => $data['niveau_examen']],
                    [
                        'decision' => $decision,
                        'moyenne' => $row['moyenne'] ?? null,
                        'observation' => $row['observation'] ?? null,
                        'date_resultat' => $data['date_resultat'] ?? now()->toDateString(),
                        'id_classe' => (int) $data['id_classe'],
                    ]
                );
                $saved++;
            }
        });

        return response()->json(['success' => $saved > 0, 'saved' => $saved, 'ignored' => $ignored]);
    }
}
