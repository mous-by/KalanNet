<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AppelEpreuveController as WebAppelEpreuveController;
use App\Models\AppelEpreuve;
use App\Models\Eleve;
use App\Services\ConductNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppelEpreuveController extends WebAppelEpreuveController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess(['controle_apercu', 'controle_creation', 'controle_création']);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $filters = $request->only(['id_classe', 'id_matiere', 'id_annee_scolaire', 'id_trimestre', 'nom_eleve', 'date_debut', 'date_fin']);
        $formData = $this->formData($user, $schoolId);
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();

        if ($hasFilters) {
            $appels = AppelEpreuve::withoutGlobalScope('school')
                ->with(['eleve', 'classe', 'matiere', 'annee', 'trimestre', 'statutControle'])
                ->join('eleve', 'eleve.id_eleve', '=', 'controle_eleve.id_eleve')
                ->when($user->droit !== 'SupAdmin', function ($query) use ($user, $schoolId) {
                    // Replicates BelongsToSchool's own DAE/DCAP branching,
                    // since this query opts out of the global scope above —
                    // without it, a DAE/DCAP user with no school selected
                    // (the normal "overview" mode) saw every school's data.
                    if ($schoolId) {
                        $query->where('controle_eleve.id_ecole', $schoolId);
                        return;
                    }
                    if ($user->droit === 'DAE' && $user->id_academie) {
                        $query->whereIn('controle_eleve.id_ecole', \App\Models\Ecole::withoutGlobalScopes()->where('id_academie', $user->id_academie)->pluck('idEcole'));
                        return;
                    }
                    if ($user->droit === 'DCAP' && $user->id_cap) {
                        $query->whereIn('controle_eleve.id_ecole', \App\Models\Ecole::withoutGlobalScopes()->where('id_cap', $user->id_cap)->pluck('idEcole'));
                        return;
                    }
                    $query->whereRaw('1 = 0');
                })
                ->when($filters['id_classe'] ?? null, fn ($query, $value) => $query->where('controle_eleve.id_classe', $value))
                ->when($filters['id_matiere'] ?? null, fn ($query, $value) => $query->where('controle_eleve.id_matiere', $value))
                ->when($filters['id_annee_scolaire'] ?? null, fn ($query, $value) => $query->where('controle_eleve.id_annee_scolaire', $value))
                ->when($filters['id_trimestre'] ?? null, fn ($query, $value) => $query->where('controle_eleve.id_trimestre', $value))
                ->when(filled($filters['date_debut'] ?? null), fn ($query) => $query->whereDate('controle_eleve.date', '>=', $filters['date_debut']))
                ->when(filled($filters['date_fin'] ?? null), fn ($query) => $query->whereDate('controle_eleve.date', '<=', $filters['date_fin']))
                ->when(filled($filters['nom_eleve'] ?? null), function ($query) use ($filters) {
                    $term = '%' . $filters['nom_eleve'] . '%';
                    $query->where(fn ($q) => $q->where('eleve.nom_eleve', 'like', $term)->orWhere('eleve.prenom_eleve', 'like', $term));
                })
                ->orderBy('eleve.nom_eleve')
                ->orderBy('eleve.prenom_eleve')
                ->orderByDesc('controle_eleve.date')
                ->select('controle_eleve.*')
                ->paginate(30)
                ->withQueryString();
            $this->attachConductProgress($appels, $schoolId);
        } else {
            $appels = AppelEpreuve::query()->whereRaw('1 = 0')->paginate(30);
        }

        return response()->json(array_merge($formData, ['appels' => $appels]));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess(['controle_creation', 'controle_création']);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $formData = $this->formData($user, $schoolId);
        $selectedClasse = $request->integer('id_classe') ?: null;
        $selectedAnnee = $request->integer('id_annee_scolaire') ?: optional($formData['annees']->first())->id_anneeScolaire;
        $eleves = collect();

        if ($selectedClasse && $selectedAnnee) {
            $eleves = Eleve::where('id_classe', $selectedClasse)
                ->where('id_annee', $selectedAnnee)
                ->where('etat_dossier', 0)
                ->when($schoolId && $user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $schoolId))
                ->orderBy('nom_eleve')->orderBy('prenom_eleve')
                ->get();
        }

        return response()->json(array_merge($formData, ['eleves' => $eleves]));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorizeAccess(['controle_creation', 'controle_création']);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $data = $request->validate([
            'id_classe' => 'required|integer|exists:classe,id_classe',
            'id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'id_annee_scolaire' => 'required|integer|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'required|integer|exists:trimestre,id_trimestre',
            'date' => 'required|date',
            'libelle' => 'required|string|max:255',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'notifier_parent' => 'nullable|boolean',
            'statuts' => 'required|array|min:1',
            'statuts.*' => 'required|integer|exists:controle,id_controle',
        ]);

        $this->authorizeClass((int) $data['id_classe'], $schoolId, $user);
        $notifier = $request->boolean('notifier_parent');

        DB::transaction(function () use ($data, $schoolId, $notifier) {
            foreach ($data['statuts'] as $studentId => $controlStatusId) {
                AppelEpreuve::create([
                    'id_eleve' => (int) $studentId,
                    'id_classe' => $data['id_classe'],
                    'id_matiere' => $data['id_matiere'],
                    'id_annee_scolaire' => $data['id_annee_scolaire'],
                    'id_trimestre' => $data['id_trimestre'],
                    'id_ecole' => $schoolId,
                    'date' => $data['date'],
                    'libelle' => $data['libelle'],
                    'heure_debut' => $data['heure_debut'],
                    'heure_fin' => $data['heure_fin'],
                    'notifier_parent' => $notifier,
                    'id_controle' => $controlStatusId,
                ]);
            }

            app(ConductNoteService::class)->syncClass($data['id_classe'], $data['id_annee_scolaire'], $data['id_trimestre'], $schoolId);
        });

        if ($notifier) {
            foreach ($data['statuts'] as $studentId => $controlStatusId) {
                $this->notifyParents((int) $studentId, (int) $controlStatusId, $data, $schoolId);
            }
        }

        return response()->json(['success' => true]);
    }
}
