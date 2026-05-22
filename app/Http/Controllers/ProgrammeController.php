<?php

namespace App\Http\Controllers;

use App\Models\ClasseOfficielle;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\Ecole;
use App\Models\ProgrammeClasse;
use App\Models\ProgrammeLecon;
use App\Models\ProgrammeOfficiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeProgrammesView();
        $idClasseOfficielle = $request->integer('id_classe_officielle');
        $user = Auth::user();
        $data = $this->programmesData($idClasseOfficielle);
        $programmes = $data['programmes'];
        $classesOfficielles = $data['classesOfficielles'];
        $canDownloadProgrammePdf = $this->canDownloadProgrammePdf($user);
        $canCreateProgramme = $this->canCreateProgramme($user);
        $canUpdateProgramme = $this->canUpdateProgramme($user);
        $canDeleteProgramme = $this->canDeleteProgramme($user);

        return view('programmes.index', compact('classesOfficielles', 'programmes', 'idClasseOfficielle', 'canDownloadProgrammePdf', 'canCreateProgramme', 'canUpdateProgramme', 'canDeleteProgramme'));
    }

    public function create()
    {
        $this->authorizeProgrammesCreation();
        return view('programmes.form', [
            'programme' => new ProgrammeOfficiel(),
            'programmeClasses' => collect(),
            'classesOfficielles' => ClasseOfficielle::orderBy('ordre_enseignement')->orderBy('nom_classe_officielle')->get(),
            'matieres' => Matiere::with('ordres')->orderBy('nom_matiere')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeProgrammesCreation();
        $data = $this->validateProgramme($request);

        DB::transaction(function () use ($data) {
            $programme = ProgrammeOfficiel::create([
                'date_creation' => now(),
                'id_utilisateur' => Auth::id(),
                'officiel' => 1,
            ]);

            $this->syncProgramme($programme, $data);
        });

        return redirect()->route('programmes.index')->with('success', 'Programme officiel enregistré avec succès.');
    }

    public function edit(int $id)
    {
        $this->authorizeProgrammesUpdate();
        $programme = ProgrammeOfficiel::with(['classes.matiere', 'classes.lecons', 'classes.classeOfficielle'])->findOrFail($id);

        return view('programmes.form', [
            'programme' => $programme,
            'programmeClasses' => $programme->classes,
            'classesOfficielles' => ClasseOfficielle::orderBy('ordre_enseignement')->orderBy('nom_classe_officielle')->get(),
            'matieres' => Matiere::with('ordres')->orderBy('nom_matiere')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeProgrammesUpdate();
        $programme = ProgrammeOfficiel::findOrFail($id);
        $data = $this->validateProgramme($request);

        DB::transaction(function () use ($programme, $data) {
            $programme->classes()->each(function ($programmeClasse) {
                $programmeClasse->lecons()->delete();
                $programmeClasse->delete();
            });
            $this->syncProgramme($programme, $data);
        });

        return redirect()->route('programmes.edit', $programme->id_programme)->with('success', 'Programme officiel modifié avec succès.');
    }

    public function destroy(int $id)
    {
        $this->authorizeProgrammesDelete();
        $programme = ProgrammeOfficiel::findOrFail($id);

        DB::transaction(function () use ($programme) {
            $programme->classes()->each(function ($programmeClasse) {
                $programmeClasse->lecons()->delete();
                $programmeClasse->delete();
            });
            $programme->delete();
        });

        return redirect()->route('programmes.index')->with('success', 'Programme officiel supprimé avec succès.');
    }

    public function downloadPDF(Request $request)
    {
        $this->authorizeProgrammesPdf();

        $idClasseOfficielle = $request->integer('id_classe_officielle');
        $data = $this->programmesData($idClasseOfficielle);
        $programmes = $data['programmes'];

        if ($programmes->isEmpty()) {
            return back()->with('error', 'Aucun programme officiel disponible pour le PDF.');
        }

        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;
        $ecole = $idEcole ? Ecole::withoutGlobalScopes()->find($idEcole) : null;
        $enseignant = $user->enseignant;
        $specialite = $enseignant?->specialite;
        $isTeacherPdf = $this->isTeacher($user);

        $pdf = Pdf::loadView('pdf.programme_officiel', compact('programmes', 'ecole', 'specialite', 'isTeacherPdf'));
        $pdf->setPaper('a4', 'portrait');

        $suffix = $idClasseOfficielle ? '_classe_' . $idClasseOfficielle : '';

        return $pdf->download('Programme_officiel' . $suffix . '.pdf');
    }

    private function validateProgramme(Request $request): array
    {
        return $request->validate([
            'id_classe_officielle' => 'required|integer|exists:classes_officielles,id_classe_officielle',
            'matieres' => 'required|array|min:1',
            'matieres.*.id_matiere' => 'required|integer|exists:matiere,id_matiere',
            'matieres.*.lecons' => 'required|array|min:1',
            'matieres.*.lecons.*.titre' => 'required|string|max:255',
        ]);
    }

    private function syncProgramme(ProgrammeOfficiel $programme, array $data): void
    {
        foreach ($data['matieres'] as $matiereData) {
            $programmeClasse = ProgrammeClasse::create([
                'id_programme' => $programme->id_programme,
                'id_classe' => $data['id_classe_officielle'],
                'id_matiere' => $matiereData['id_matiere'],
                'pour_toutes_ecoles' => 1,
            ]);

            foreach (array_values($matiereData['lecons']) as $index => $leconData) {
                ProgrammeLecon::create([
                    'id_programme_classe' => $programmeClasse->id_programme_classe,
                    'numero' => $index + 1,
                    'titre' => $leconData['titre'],
                ]);
            }
        }
    }

    private function authorizeProgrammesView(): void
    {
        $user = Auth::user();
        if ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission($this->programmesViewPermissions())) {
            abort(403);
        }
    }

    private function authorizeProgrammesPdf(): void
    {
        if (!$this->canDownloadProgrammePdf(Auth::user())) {
            abort(403, 'Vous n’avez pas la permission de télécharger le programme officiel en PDF.');
        }
    }

    private function canDownloadProgrammePdf($user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_pdf', 'programme_pdf', 'voir_pdf_programme']);
    }

    private function authorizeProgrammesCreation(): void
    {
        if (!$this->canCreateProgramme(Auth::user())) {
            abort(403, 'Vous n’avez pas la permission de créer un programme officiel.');
        }
    }

    private function authorizeProgrammesUpdate(): void
    {
        if (!$this->canUpdateProgramme(Auth::user())) {
            abort(403, 'Vous n’avez pas la permission de modifier un programme officiel.');
        }
    }

    private function authorizeProgrammesDelete(): void
    {
        if (!$this->canDeleteProgramme(Auth::user())) {
            abort(403, 'Vous n’avez pas la permission de supprimer un programme officiel.');
        }
    }

    private function canCreateProgramme($user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_creation', 'programme_creation', 'programme_création']);
    }

    private function canUpdateProgramme($user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_modification', 'programme_modification', 'programme_modifier']);
    }

    private function canDeleteProgramme($user): bool
    {
        return $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['programmes_supprimer', 'programme_supprimer', 'programmes_suppression', 'programme_suppression']);
    }

    private function programmesViewPermissions(): array
    {
        return [
            'programmes_apercu',
            'programme_apercu',
            'appercu_programm',
            'programmes_pdf',
            'voir_pdf_programme',
            'programmes_creation',
            'programme_creation',
            'programme_création',
            'programmes_modification',
            'programme_modification',
            'programmes_supprimer',
            'programme_supprimer',
        ];
    }

    private function allowedClasses()
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        return Classe::query()
            ->with(['classeOfficielle', 'ligneClasses:id_ligneclasse,id_classe,id_matiere'])
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->where('idEcole', $idEcole))
            ->whereNotNull('id_classe_officielle')
            ->get(['id_classe', 'nom_classe', 'id_classe_officielle']);
    }

    private function programmesData(?int $idClasseOfficielle = null): array
    {
        $user = Auth::user();
        $allowedClasses = $this->allowedClasses();
        $allowedClasseIds = $allowedClasses->pluck('id_classe')->all();
        $allowedClasseOfficielleIds = $allowedClasses->pluck('id_classe_officielle')->filter()->unique()->values()->all();

        $visibleClasseOfficielleIds = $user->droit === 'SupAdmin'
            ? ClasseOfficielle::query()->pluck('id_classe_officielle')->all()
            : $allowedClasseOfficielleIds;

        if ($idClasseOfficielle && $user->droit !== 'SupAdmin' && !in_array($idClasseOfficielle, $allowedClasseOfficielleIds, true)) {
            abort(403);
        }

        $programmeQuery = ProgrammeClasse::with(['programme', 'classeOfficielle', 'matiere', 'lecons']);

        if ($user->droit !== 'SupAdmin') {
            $programmeQuery->where(function ($query) use ($allowedClasseIds, $allowedClasseOfficielleIds) {
                $query->whereIn('id_classe', $allowedClasseIds);

                if (!empty($allowedClasseOfficielleIds)) {
                    $query->orWhereIn('id_classe', $allowedClasseOfficielleIds);
                }

                $query->orWhereNull('id_classe');
            });
        }

        $programmeRows = $programmeQuery
            ->orderBy('id_classe')
            ->orderBy('id_matiere')
            ->get();

        $inferredOfficialIds = $this->inferUnassignedProgrammeClasses($programmeRows, $allowedClasses);
        $officialClassesById = $allowedClasses
            ->pluck('classeOfficielle')
            ->filter()
            ->keyBy('id_classe_officielle');

        $resolvedProgrammes = $programmeRows
            ->map(function ($programmeClasse) use ($allowedClasses) {
                $localClasse = $allowedClasses->firstWhere('id_classe', (int) $programmeClasse->id_classe);

                if ($localClasse && $localClasse->classeOfficielle) {
                    $programmeClasse->setRelation('classeOfficielle', $localClasse->classeOfficielle);
                    $programmeClasse->id_classe_officielle_resolved = $localClasse->id_classe_officielle;
                } else {
                    $programmeClasse->id_classe_officielle_resolved = $programmeClasse->id_classe;
                }

                return $programmeClasse;
            })
            ->map(function ($programmeClasse) use ($inferredOfficialIds, $officialClassesById) {
                if ($programmeClasse->id_classe !== null) {
                    return $programmeClasse;
                }

                $officialId = $inferredOfficialIds[(int) $programmeClasse->id_programme] ?? null;
                if ($officialId && $officialClassesById->has($officialId)) {
                    $programmeClasse->setRelation('classeOfficielle', $officialClassesById->get($officialId));
                    $programmeClasse->id_classe_officielle_resolved = $officialId;
                }

                return $programmeClasse;
            })
            ->filter(fn ($programmeClasse) => !empty($programmeClasse->id_classe_officielle_resolved))
            ->when($this->isTeacher($user), fn ($items) => $this->filterProgrammesForTeacherSpecialite($items, $user->enseignant?->specialite));

        $programmesClasseOfficielleIds = $resolvedProgrammes
            ->pluck('id_classe_officielle_resolved')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $classesOfficielles = ClasseOfficielle::query()
            ->whereIn('id_classe_officielle', array_values(array_intersect($visibleClasseOfficielleIds, $programmesClasseOfficielleIds)))
            ->orderBy('ordre_enseignement')
            ->orderBy('nom_classe_officielle')
            ->get();

        $programmes = $resolvedProgrammes
            ->when($idClasseOfficielle, fn ($items) => $items->filter(fn ($item) => (int) ($item->id_classe_officielle_resolved ?? 0) === $idClasseOfficielle))
            ->groupBy('id_classe_officielle_resolved');

        return compact('programmes', 'classesOfficielles');
    }

    private function isTeacher($user): bool
    {
        return !empty($user->id_enseignant);
    }

    private function filterProgrammesForTeacherSpecialite($programmeRows, ?string $specialite)
    {
        $normalizedSpecialite = $this->normalizeSpecialite($specialite);

        if ($normalizedSpecialite === '') {
            return collect();
        }

        return $programmeRows->filter(function ($programmeClasse) use ($normalizedSpecialite) {
            $matiere = $this->normalizeSpecialite($programmeClasse->matiere->nom_matiere ?? '');

            return $matiere !== '' && (
                $matiere === $normalizedSpecialite ||
                Str::contains($matiere, $normalizedSpecialite) ||
                Str::contains($normalizedSpecialite, $matiere)
            );
        });
    }

    private function normalizeSpecialite(?string $value): string
    {
        return Str::of($value ?? '')
            ->ascii()
            ->lower()
            ->replace(['-', '_', '/', ','], ' ')
            ->squish()
            ->toString();
    }

    private function inferUnassignedProgrammeClasses($programmeRows, $allowedClasses): array
    {
        $resolvedOfficialIds = $programmeRows
            ->filter(fn ($row) => $row->id_classe !== null)
            ->map(function ($row) use ($allowedClasses) {
                $localClasse = $allowedClasses->firstWhere('id_classe', (int) $row->id_classe);

                return $localClasse?->id_classe_officielle ?: (int) $row->id_classe;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableClasses = $allowedClasses
            ->filter(fn ($classe) => !in_array((int) $classe->id_classe_officielle, $resolvedOfficialIds, true))
            ->values();

        $inferred = [];

        foreach ($programmeRows->whereNull('id_classe')->groupBy('id_programme') as $programmeId => $rows) {
            if ($availableClasses->isEmpty()) {
                break;
            }

            $programmeMatiereIds = $rows->pluck('id_matiere')->map(fn ($id) => (int) $id)->unique();
            $bestClasse = $availableClasses
                ->sortByDesc(function ($classe) use ($programmeMatiereIds) {
                    $classeMatiereIds = $classe->ligneClasses->pluck('id_matiere')->map(fn ($id) => (int) $id)->unique();

                    return $programmeMatiereIds->intersect($classeMatiereIds)->count();
                })
                ->first();

            if (!$bestClasse) {
                continue;
            }

            $inferred[(int) $programmeId] = (int) $bestClasse->id_classe_officielle;
            $availableClasses = $availableClasses
                ->reject(fn ($classe) => (int) $classe->id_classe_officielle === (int) $bestClasse->id_classe_officielle)
                ->values();
        }

        return $inferred;
    }
}
