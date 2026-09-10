<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\EvaluationController as WebEvaluationController;
use App\Models\Classe;
use App\Models\Evaluation;
use App\Models\LigneEvaluation;
use App\Models\Matiere;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class EvaluationController extends WebEvaluationController
{
    public function index(Request $request)
    {
        $context = $this->evaluationContext();
        $filters = $request->only(['id_classe', 'id_matiere', 'id_annee_scolaire', 'id_trimestre', 'mois']);

        $validationSelect = Schema::hasColumn('ligne_evaluation', 'validation_status')
            ? "MIN(COALESCE(ligne_evaluation.validation_status, 'valide')) as validation_status"
            : "'valide' as validation_status";

        $user = $request->user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        $evaluations = LigneEvaluation::query()
            ->with(['evaluation', 'classe', 'matiere', 'trimestre'])
            ->selectRaw("MIN(ligne_evaluation.id_ligneEvaluation) as id_ligneEvaluation, ligne_evaluation.id_evaluation, ligne_evaluation.id_classe, ligne_evaluation.id_matiere, ligne_evaluation.id_annee_scolaire, ligne_evaluation.id_trimestre, ligne_evaluation.mois, {$validationSelect}")
            ->join('evaluation as e', 'e.id_evaluation', '=', 'ligne_evaluation.id_evaluation')
            ->when($user->droit !== 'SupAdmin', fn ($q) => $q->whereIn(
                'ligne_evaluation.id_classe',
                Classe::withoutGlobalScopes()->where('idEcole', $idEcole)->select('id_classe')
            ))
            ->when($user->id_enseignant, fn ($q, $teacherId) => $q->where('ligne_evaluation.id_enseignant', $teacherId))
            ->when($filters['id_classe'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_classe', $value))
            ->when($filters['id_matiere'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_matiere', $value))
            ->when($filters['id_annee_scolaire'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.id_annee_scolaire', $value))
            ->when($filters['mois'] ?? null, fn ($q, $value) => $q->where('ligne_evaluation.mois', $value))
            ->when(empty($filters['mois']) && !empty($filters['id_trimestre']), fn ($q) => $q->where('ligne_evaluation.id_trimestre', $filters['id_trimestre']))
            ->groupBy('ligne_evaluation.id_evaluation', 'ligne_evaluation.id_classe', 'ligne_evaluation.id_matiere', 'ligne_evaluation.id_annee_scolaire', 'ligne_evaluation.id_trimestre', 'ligne_evaluation.mois')
            ->orderByDesc('e.date_evaluation')
            ->paginate(20)
            ->withQueryString();

        return response()->json($context + ['evaluations' => $evaluations]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('evaluation_creation');
        $data = $this->validateProgramme($request);
        $user = $request->user();
        $idEnseignant = $user->id_enseignant;

        if (!$idEnseignant) {
            abort(403, 'Seuls les enseignants peuvent programmer une évaluation.');
        }

        $students = $this->studentsForEvaluation($data['id_classe'], $data['id_annee_scolaire'])->pluck('id_eleve')->all();
        if (empty($students)) {
            throw ValidationException::withMessages(['id_classe' => 'Aucun élève trouvé pour cette classe et cette année scolaire.']);
        }

        $evaluation = DB::transaction(function () use ($data, $idEnseignant, $students) {
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

            return $evaluation;
        });

        return response()->json($evaluation, 201);
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
        $classe = $firstLine?->classe ?? new Classe(['nom_classe' => 'Non renseignée']);
        $this->authorizeClasse($classe);

        return response()->json([
            'evaluation' => $evaluation,
            'details' => $details,
            'matiere' => $firstLine?->matiere ?? new Matiere(['nom_matiere' => 'Non renseignée']),
            'classe' => $classe,
        ]);
    }

    public function updateProgramme(Request $request, int $id)
    {
        $this->authorizePermission('evaluation_modification');
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
        $idEnseignant = $request->user()->id_enseignant ?: $details->first()->id_enseignant;

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

        return response()->json(['success' => true]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('evaluation_modification');
        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with(['classe.ecole', 'noteType'])->where('id_evaluation', $evaluation->id_evaluation)->get();
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

        DB::transaction(function () use ($evaluation, $data, $details) {
            $validationStatus = $this->requiresPrivateNoteValidation($details->first()->classe) ? 'en_attente' : 'valide';
            $validationColumns = $this->validationColumns($validationStatus);

            foreach ($data['id_ligneEvaluation'] as $index => $lineId) {
                LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)
                    ->where('id_ligneEvaluation', $lineId)
                    ->update(array_merge([
                        'note' => $this->normalizeNote($data['note'][$index] ?? null),
                    ], $validationColumns));
            }
        });

        $this->notifyNoteValidators($evaluation);

        return response()->json(['success' => true]);
    }

    public function validateNotes(int $id)
    {
        $this->authorizeNoteValidation();

        $evaluation = Evaluation::findOrFail($id);
        $details = LigneEvaluation::with(['classe'])->where('id_evaluation', $evaluation->id_evaluation)->get();

        abort_if($details->isEmpty(), 404);
        $this->authorizeClasse($details->first()->classe);

        LigneEvaluation::where('id_evaluation', $evaluation->id_evaluation)
            ->update($this->validationColumns('valide'));

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('evaluation_supprimer');
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

        return response()->json(['success' => true]);
    }
}
