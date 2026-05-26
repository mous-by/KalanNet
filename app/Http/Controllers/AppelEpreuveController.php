<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\AppelEpreuve;
use App\Models\AppNotification;
use App\Models\Classe;
use App\Models\Controle;
use App\Models\Ecole;
use App\Models\Eleve;
use App\Models\LigneClasse;
use App\Models\Matiere;
use App\Models\Trimestre;
use App\Models\User;
use App\Services\ConductNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AppelEpreuveController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $this->authorizeAccess(['controle_apercu', 'controle_creation', 'controle_création']);

        $schoolId = session('idEcole') ?: $user->idEcole;
        $filters = $request->only(['id_classe', 'id_matiere', 'id_annee_scolaire', 'id_trimestre']);
        $formData = $this->formData($user, $schoolId);
        $hasFilters = collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty();

        if ($hasFilters) {
            $appels = AppelEpreuve::with(['eleve', 'classe', 'matiere', 'annee', 'trimestre', 'statutControle'])
                ->when($schoolId && $user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $schoolId))
                ->when($filters['id_classe'] ?? null, fn ($query, $value) => $query->where('id_classe', $value))
                ->when($filters['id_matiere'] ?? null, fn ($query, $value) => $query->where('id_matiere', $value))
                ->when($filters['id_annee_scolaire'] ?? null, fn ($query, $value) => $query->where('id_annee_scolaire', $value))
                ->when($filters['id_trimestre'] ?? null, fn ($query, $value) => $query->where('id_trimestre', $value))
                ->orderByDesc('date')
                ->orderByDesc('id_controle_eleve')
                ->paginate(30)
                ->withQueryString();
            $this->attachConductProgress($appels, $schoolId);
        } else {
            $appels = AppelEpreuve::query()->whereRaw('1 = 0')->paginate(30);
        }

        return view('appels-epreuves.index', array_merge($formData, compact('appels', 'filters', 'hasFilters')));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
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
                ->orderBy('prenom_eleve')->orderBy('nom_eleve')
                ->get();
        }

        return view('appels-epreuves.create', array_merge($formData, compact('selectedClasse', 'selectedAnnee', 'eleves')));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
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

        return redirect()->route('appels-epreuves.index')
            ->with('success', 'Appel d’épreuve enregistré. Les notes de conduite ont été recalculées.');
    }

    private function formData($user, ?int $schoolId): array
    {
        $classes = Classe::query()
            ->when($schoolId && $user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $schoolId))
            ->when($user->droit === 'enseignant', function ($query) use ($user) {
                $query->whereIn('id_classe', LigneClasse::where('id_enseignants', $user->id_enseignant)->pluck('id_classe'));
            })
            ->orderBy('nom_classe')
            ->get();

        $matieres = Matiere::query()
            ->when($user->droit === 'enseignant', function ($query) use ($user) {
                $query->whereIn('id_matiere', LigneClasse::where('id_enseignants', $user->id_enseignant)->pluck('id_matiere'));
            })
            ->orderBy('nom_matiere')
            ->get();

        return [
            'classes' => $classes,
            'matieres' => $matieres,
            'annees' => AnneeScolaire::orderByDesc('id_anneeScolaire')->get(),
            'trimestres' => Trimestre::orderBy('id_trimestre')->get(),
            'statuts' => Controle::when($schoolId && $user->droit !== 'SupAdmin', fn ($query) => $query->where('id_ecole', $schoolId))->orderBy('type_controle')->get(),
        ];
    }

    private function attachConductProgress($appels, ?int $schoolId): void
    {
        $service = app(ConductNoteService::class);
        foreach ($appels->getCollection() as $appel) {
            $appelSchoolId = (int) ($appel->id_ecole ?: $schoolId);
            if (!$appelSchoolId) {
                continue;
            }

            $penaltyAfter = $service->penaltyForStudent(
                (int) $appel->id_eleve,
                (int) $appel->id_classe,
                (int) $appel->id_annee_scolaire,
                (int) $appel->id_trimestre,
                $appelSchoolId,
                $appel->date?->format('Y-m-d') ?? (string) $appel->date,
                (int) $appel->id_controle_eleve
            );
            $currentPenalty = abs((float) ($appel->statutControle?->penalite_conduite ?? 0));

            $appel->note_conduite_avant = $service->noteFromPenalty(max(0, $penaltyAfter - $currentPenalty));
            $appel->note_conduite_apres = $service->noteFromPenalty($penaltyAfter);
        }
    }

    private function notifyParents(int $studentId, int $controlStatusId, array $data, ?int $schoolId): void
    {
        $eleve = Eleve::with('parents')->find($studentId);
        $status = Controle::find($controlStatusId);
        $emailEnabled = $this->schoolAllowsParentEmails($schoolId);

        if (!$eleve || !$status || $status->alertControle !== 'oui') {
            return;
        }

        $message = "Votre enfant {$eleve->prenom_eleve} {$eleve->nom_eleve} a été marqué \"{$status->type_controle}\" pendant l’appel {$data['libelle']} du " . date('d/m/Y', strtotime($data['date'])) . '.';

        foreach ($eleve->parents as $parent) {
            if (($parent->pivot->informer ?? 'Non') !== 'Oui') {
                continue;
            }

            if (Schema::hasTable('app_notifications')) {
                User::where('id_parent', $parent->id_parent)->get()->each(function (User $user) use ($message, $eleve) {
                    AppNotification::create([
                        'user_id' => $user->idUtilisateur,
                        'type' => 'controle',
                        'title' => 'Appel de présence',
                        'message' => $message,
                        'link' => route('dashboard') . '#appel-presence-parent',
                        'data' => ['event' => 'EXAM_CALL_STATUS', 'id_eleve' => $eleve->id_eleve],
                    ]);
                });
            }

            if ($emailEnabled && $parent->email_parent) {
                try {
                    Mail::raw($message, function ($mail) use ($parent) {
                        $mail->to($parent->email_parent)->subject('Notification appel de présence');
                    });
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        }
    }

    private function schoolAllowsParentEmails(?int $schoolId): bool
    {
        if (!$schoolId || !Schema::hasColumn('ecole', 'notification_email')) {
            return true;
        }

        return (bool) Ecole::withoutGlobalScopes()
            ->where('idEcole', $schoolId)
            ->value('notification_email');
    }

    private function authorizeAccess(array $permissions): void
    {
        $user = Auth::user();
        if (!$user || ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission($permissions))) {
            abort(403);
        }
    }

    private function authorizeClass(int $classId, ?int $schoolId, $user): void
    {
        $classe = Classe::findOrFail($classId);

        if ($user->droit !== 'SupAdmin' && (int) $classe->idEcole !== (int) $schoolId) {
            abort(403);
        }

        if ($user->droit === 'enseignant') {
            $allowed = LigneClasse::where('id_enseignants', $user->id_enseignant)
                ->where('id_classe', $classId)
                ->exists();

            if (!$allowed) {
                abort(403);
            }
        }
    }
}
