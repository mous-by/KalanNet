<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\LigneEvaluation;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Matiere;
use App\Models\AnneeScolaire;
use App\Models\Note;
use App\Models\Trimestre;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->evaluationContext();
        $filters = $request->only(['id_classe', 'id_matiere', 'id_annee_scolaire', 'id_trimestre', 'mois']);

        $evaluations = LigneEvaluation::query()
            ->with(['evaluation', 'classe', 'matiere', 'trimestre'])
            ->selectRaw('MIN(ligne_evaluation.id_ligneEvaluation) as id_ligneEvaluation, ligne_evaluation.id_evaluation, ligne_evaluation.id_classe, ligne_evaluation.id_matiere, ligne_evaluation.id_annee_scolaire, ligne_evaluation.id_trimestre, ligne_evaluation.mois')
            ->join('evaluation as e', 'e.id_evaluation', '=', 'ligne_evaluation.id_evaluation')
            ->when(Auth::user()->id_enseignant, fn ($q, $teacherId) => $q->where('ligne_evaluation.id_enseignant', $teacherId))
            ->when($filters['id_classe'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_classe', $value))
            ->when($filters['id_matiere'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_matiere', $value))
            ->when($filters['id_annee_scolaire'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_annee_scolaire', $value))
            ->when($filters['mois'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.mois', $value))
            ->when(empty($filters['mois']) && !empty($filters['id_trimestre']), fn ($q) => $q->where('ligne_evaluation.id_trimestre', $filters['id_trimestre']))
            ->groupBy('ligne_evaluation.id_evaluation', 'ligne_evaluation.id_classe', 'ligne_evaluation.id_matiere', 'ligne_evaluation.id_annee_scolaire', 'ligne_evaluation.id_trimestre', 'ligne_evaluation.mois')
            ->orderByDesc('e.date_evaluation')
            ->paginate(20)
            ->withQueryString();

        return view('evaluations.index', $context + compact('evaluations', 'filters'));
    }

    public function create()
    {
        return view('evaluations.form', $this->evaluationContext() + [
            'evaluation' => new Evaluation(),
            'details' => collect(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProgramme($request);
        $user = Auth::user();
        $idEnseignant = $user->id_enseignant;

        if (!$idEnseignant) {
            abort(403, 'Seuls les enseignants peuvent programmer une évaluation.');
        }

        $students = $this->studentsForEvaluation($data['id_classe'], $data['id_annee_scolaire'])->pluck('id_eleve')->all();
        if (empty($students)) {
            throw ValidationException::withMessages(['id_classe' => 'Aucun élève trouvé pour cette classe et cette année scolaire.']);
        }

        DB::transaction(function () use ($data, $idEnseignant, $students) {
            $evaluation = Evaluation::create([
                'libeller' => $data['libeller'],
                'date_evaluation' => $data['date_evaluation'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
            ]);

            foreach ($students as $idEleve) {
                LigneEvaluation::create([
                    'id_evaluation' => $evaluation->id_evaluation,
                    'id_classe' => $data['id_classe'],
                    'id_matiere' => $data['id_matiere'],
                    'id_annee_scolaire' => $data['id_annee_scolaire'],
                    'id_trimestre' => $data['id_trimestre'] ?? null,
                    'id_note' => $data['id_note'],
                    'id_eleve' => $idEleve,
                    'note' => null,
                    'id_enseignant' => $idEnseignant,
                    'mois' => $data['mois'] ?? null,
                ]);
            }
        });

        return redirect()->route('evaluations.index')->with('success', 'Évaluation programmée avec succès. Vous pouvez maintenant saisir les notes.');
    }

    public function show($id)
    {
        $evaluation = Evaluation::findOrFail($id);

        $details = LigneEvaluation::with(['eleve', 'matiere', 'classe', 'noteType', 'trimestre'])
            ->where('id_evaluation', $evaluation->id_evaluation)
            ->orderBy('id_classe')
            ->orderBy('id_matiere')
            ->orderBy('id_eleve')
            ->get();
        $this->authorizeEvaluationLines($details);

        $firstLine = $details->first();
        $matiere = $firstLine?->matiere ?? new Matiere(['nom_matiere' => 'Non renseignée']);
        $classe = $firstLine?->classe ?? new Classe(['nom_classe' => 'Non renseignée']);

        return view('evaluations.show', compact('evaluation', 'details', 'matiere', 'classe'));
    }

    public function edit(int $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with(['eleve', 'matiere', 'classe', 'noteType', 'trimestre'])
            ->where('id_evaluation', $evaluation->id_evaluation)
            ->orderBy('id_ligneEvaluation')
            ->get();

        abort_if($details->isEmpty(), 404);
        $this->authorizeEvaluationLines($details);
        $this->authorizeClasse($details->first()->classe);

        return view('evaluations.edit', compact('evaluation', 'details'));
    }

    public function editProgramme(int $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with(['classe', 'matiere', 'noteType', 'trimestre'])
            ->where('id_evaluation', $evaluation->id_evaluation)
            ->orderBy('id_ligneEvaluation')
            ->get();

        abort_if($details->isEmpty(), 404);
        $this->authorizeEvaluationLines($details);
        $this->authorizeClasse($details->first()->classe);

        return view('evaluations.programme', $this->evaluationContext() + compact('evaluation', 'details'));
    }

    public function updateProgramme(Request $request, int $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with('classe')->where('id_evaluation', $evaluation->id_evaluation)->get();

        abort_if($details->isEmpty(), 404);
        $this->authorizeEvaluationLines($details);
        $this->authorizeClasse($details->first()->classe);

        $data = $this->validateProgramme($request);
        $students = $this->studentsForEvaluation($data['id_classe'], $data['id_annee_scolaire'])->pluck('id_eleve')->all();
        if (empty($students)) {
            throw ValidationException::withMessages(['id_classe' => 'Aucun élève trouvé pour cette classe et cette année scolaire.']);
        }
        $idEnseignant = Auth::user()->id_enseignant ?: $details->first()->id_enseignant;

        DB::transaction(function () use ($evaluation, $data, $students, $idEnseignant) {
            $evaluation->update([
                'libeller' => $data['libeller'],
                'date_evaluation' => $data['date_evaluation'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'updated_at' => now(),
            ]);

            $existingNotes = LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)
                ->pluck('note', 'id_eleve');

            LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)->delete();

            foreach ($students as $idEleve) {
                LigneEvaluation::create([
                    'id_evaluation' => $evaluation->id_evaluation,
                    'id_classe' => $data['id_classe'],
                    'id_matiere' => $data['id_matiere'],
                    'id_annee_scolaire' => $data['id_annee_scolaire'],
                    'id_trimestre' => $data['id_trimestre'] ?? null,
                    'id_note' => $data['id_note'],
                    'id_eleve' => $idEleve,
                    'note' => $existingNotes[$idEleve] ?? null,
                    'id_enseignant' => $idEnseignant,
                    'mois' => $data['mois'] ?? null,
                ]);
            }
        });

        return redirect()->route('evaluations.index')->with('success', 'Programmation de l’évaluation modifiée avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with(['classe', 'noteType'])->where('id_evaluation', $evaluation->id_evaluation)->get();
        abort_if($details->isEmpty(), 404);
        $this->authorizeEvaluationLines($details);
        $this->authorizeClasse($details->first()->classe);

        $maxNote = $this->maxNoteFor($details->first()->noteType);
        $data = $request->validate([
            'id_ligneEvaluation' => 'required|array|min:1',
            'id_ligneEvaluation.*' => 'required|integer|exists:ligne_evaluation,id_ligneEvaluation',
            'note' => 'required|array|min:1',
            'note.*' => 'nullable|numeric|min:0|max:' . $maxNote,
        ]);

        DB::transaction(function () use ($evaluation, $data) {
            foreach ($data['id_ligneEvaluation'] as $index => $lineId) {
                LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)
                    ->where('id_ligneEvaluation', $lineId)
                    ->update(['note' => $this->normalizeNote($data['note'][$index] ?? null)]);
            }
        });

        return redirect()->route('evaluations.edit', $evaluation->id_evaluation)->with('success', 'Notes enregistrées avec succès.');
    }

    public function destroy(int $id)
    {
        $evaluation = Evaluation::findOrFail($id);
        $firstLine = LigneEvaluation::with('classe')->where('id_evaluation', $evaluation->id_evaluation)->first();
        if ($firstLine) {
            $this->authorizeEvaluationLines(collect([$firstLine]));
        }
        if ($firstLine?->classe) {
            $this->authorizeClasse($firstLine->classe);
        }

        DB::transaction(function () use ($evaluation) {
            LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)->delete();
            $evaluation->delete();
        });

        return redirect()->route('evaluations.index')->with('success', 'Évaluation supprimée avec succès.');
    }

    public function matieresByClasse(int $idClasse)
    {
        $classe = Classe::with('ligneClasses.matiere')->findOrFail($idClasse);
        $this->authorizeClasse($classe);

        $user = Auth::user();
        $matieres = $classe->ligneClasses
            ->when($user->id_enseignant, fn ($items) => $items->where('id_enseignants', $user->id_enseignant))
            ->pluck('matiere')
            ->filter()
            ->unique('id_matiere')
            ->values()
            ->map(fn ($matiere) => ['id_matiere' => $matiere->id_matiere, 'nom_matiere' => $matiere->nom_matiere]);

        return response()->json(['matiere' => $matieres]);
    }

    public function students(Request $request)
    {
        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
        ]);

        $students = $this->studentsForEvaluation((int) $data['id_classe'], (int) $data['id_annee_scolaire'])
            ->map(fn ($eleve) => [
                'id_eleve' => $eleve->id_eleve,
                'matricule' => $eleve->matricule,
                'nom' => trim($eleve->nom_eleve . ' ' . $eleve->prenom_eleve),
            ]);

        return response()->json(['eleves' => $students]);
    }

    private function evaluationContext(): array
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $classes = Classe::query()
            ->with('ligneClasses.matiere')
            ->when($user->droit !== 'SupAdmin', fn ($q) => $q->where('idEcole', $idEcole))
            ->when($user->id_enseignant, fn ($q) => $q->whereHas('ligneClasses', fn ($l) => $l->where('id_enseignants', $user->id_enseignant)))
            ->orderBy('nom_classe')
            ->get();

        return [
            'classes' => $classes,
            'annees' => AnneeScolaire::orderByDesc('date_debut')->get(),
            'notes' => Note::orderBy('typeNote')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'moisOptions' => [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'],
        ];
    }

    private function validateProgramme(Request $request): array
    {
        $data = $request->validate([
            'libeller' => 'required|string|max:150',
            'date_evaluation' => 'required|date',
            'heure_debut' => 'required',
            'heure_fin' => 'required|after:heure_debut',
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'nullable|integer|exists:trimestre,id_trimestre',
            'mois' => 'nullable|integer|between:1,12',
            'id_note' => 'required|integer|exists:note,id_note',
        ]);

        $classe = Classe::find((int) $data['id_classe']);
        $this->ensureTeacherCanEvaluate((int) $data['id_classe'], (int) $data['id_matiere']);
        if ($classe?->ordreEnseignement === 'fondamentale1') {
            if (empty($data['mois'])) {
                throw ValidationException::withMessages(['mois' => 'Le mois est obligatoire pour cette classe.']);
            }
            $data['id_trimestre'] = null;
            return $data;
        }

        if (empty($data['id_trimestre'])) {
            throw ValidationException::withMessages(['id_trimestre' => 'La période est obligatoire pour cette classe.']);
        }

        $data['mois'] = null;
        return $data;
    }

    private function studentsForEvaluation(int $idClasse, int $idAnnee)
    {
        $classe = Classe::findOrFail($idClasse);
        $this->authorizeClasse($classe);

        return Eleve::query()
            ->where('id_classe', $idClasse)
            ->where('id_annee', $idAnnee)
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
            ->get();
    }

    private function authorizeClasse(Classe $classe): void
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && (int) $classe->idEcole !== (int) (session('idEcole') ?: $user->idEcole)) {
            abort(403);
        }
    }

    private function authorizeEvaluationLines($details): void
    {
        abort_if($details->isEmpty(), 404);

        $user = Auth::user();
        if (!$user->id_enseignant) {
            return;
        }

        $belongsToTeacher = $details->every(fn (LigneEvaluation $line) => (int) $line->id_enseignant === (int) $user->id_enseignant);
        if (!$belongsToTeacher) {
            abort(403);
        }
    }

    private function ensureTeacherCanEvaluate(int $classId, int $subjectId): void
    {
        $teacherId = Auth::user()->id_enseignant;
        if (!$teacherId) {
            return;
        }

        $isAssigned = DB::table('ligneclasse')
            ->where('id_classe', $classId)
            ->where('id_matiere', $subjectId)
            ->where('id_enseignants', $teacherId)
            ->exists();

        if (!$isAssigned) {
            throw ValidationException::withMessages([
                'id_matiere' => 'Vous ne pouvez préparer ou modifier une évaluation que pour vos propres matières.',
            ]);
        }
    }

    private function normalizeNote($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    private function maxNoteFor(?Note $note): float
    {
        $value = (float) ($note?->valeur ?? 20);

        return $value > 0 ? $value : 20;
    }
}
