<?php

namespace App\Http\Controllers;

use App\Models\ClasseOfficielle;
use App\Models\Classe;
use App\Models\Matiere;
use App\Models\ProgrammeClasse;
use App\Models\ProgrammeLecon;
use App\Models\ProgrammeOfficiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeProgrammesView();
        $idClasseOfficielle = $request->integer('id_classe_officielle');
        $user = Auth::user();
        $allowedClasseOfficielleIds = $this->allowedClasseOfficielleIds();

        $classesOfficielles = ClasseOfficielle::orderBy('ordre_enseignement')->orderBy('nom_classe_officielle')->get();
        if ($user->droit !== 'SupAdmin') {
            $classesOfficielles = $classesOfficielles->whereIn('id_classe_officielle', $allowedClasseOfficielleIds)->values();
            if ($idClasseOfficielle && !in_array($idClasseOfficielle, $allowedClasseOfficielleIds, true)) {
                abort(403);
            }
        }

        $programmes = ProgrammeClasse::with(['programme', 'classeOfficielle', 'matiere', 'lecons'])
            ->when($user->droit !== 'SupAdmin', fn ($query) => $query->whereIn('id_classe', $allowedClasseOfficielleIds))
            ->when($idClasseOfficielle, fn ($query) => $query->where('id_classe', $idClasseOfficielle))
            ->orderBy('id_classe')
            ->orderBy('id_matiere')
            ->get()
            ->groupBy('id_classe');

        return view('programmes.index', compact('classesOfficielles', 'programmes', 'idClasseOfficielle'));
    }

    public function create()
    {
        $this->authorizeProgrammesMutation();
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
        $this->authorizeProgrammesMutation();
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
        $this->authorizeProgrammesMutation();
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
        $this->authorizeProgrammesMutation();
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
        $this->authorizeProgrammesMutation();
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
        if ($user->droit !== 'SupAdmin' && !$user->userHasPermission('programmes_apercu') && !$user->userHasPermission('programme_apercu')) {
            abort(403);
        }
    }

    private function authorizeProgrammesMutation(): void
    {
        if (Auth::user()->droit !== 'SupAdmin') {
            abort(403, 'Seul le SuperAdmin peut modifier les programmes officiels.');
        }
    }

    private function allowedClasseOfficielleIds(): array
    {
        $user = Auth::user();
        $idEcole = session('idEcole') ?: $user->idEcole;

        return Classe::query()
            ->where('idEcole', $idEcole)
            ->whereNotNull('id_classe_officielle')
            ->pluck('id_classe_officielle')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
