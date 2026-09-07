<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ProgrammeController as WebProgrammeController;
use App\Models\ClasseOfficielle;
use App\Models\Matiere;
use App\Models\ProgrammeOfficiel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends WebProgrammeController
{
    // downloadPDF() streams a PDF directly and is routed straight to the
    // web controller (see routes/api.php) — no override needed.

    public function index(Request $request)
    {
        $this->authorizeProgrammesView();
        $idClasseOfficielle = $request->integer('id_classe_officielle') ?: null;
        $user = $request->user();
        $data = $this->programmesData($idClasseOfficielle);

        return response()->json([
            'programmes' => $data['programmes'],
            'classes_officielles' => $data['classesOfficielles'],
            'can_download_pdf' => $this->canDownloadProgrammePdf($user),
            'can_create' => $this->canCreateProgramme($user),
            'can_update' => $this->canUpdateProgramme($user),
            'can_delete' => $this->canDeleteProgramme($user),
        ]);
    }

    public function create()
    {
        $this->authorizeProgrammesCreation();

        return response()->json([
            'classes_officielles' => ClasseOfficielle::orderBy('ordre_enseignement')->orderBy('nom_classe_officielle')->get(),
            'matieres' => Matiere::with('ordres')->orderBy('nom_matiere')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeProgrammesCreation();
        $data = $this->validateProgramme($request);

        $programme = DB::transaction(function () use ($data, $request) {
            $programme = ProgrammeOfficiel::create([
                'date_creation' => now(),
                'id_utilisateur' => $request->user()->idUtilisateur,
                'officiel' => 1,
            ]);

            $this->syncProgramme($programme, $data);

            return $programme;
        });

        return response()->json($programme->load('classes'), 201);
    }

    public function edit(int $id)
    {
        $this->authorizeProgrammesUpdate();
        $programme = ProgrammeOfficiel::with(['classes.matiere', 'classes.lecons', 'classes.classeOfficielle'])->findOrFail($id);

        return response()->json([
            'programme' => $programme,
            'classes_officielles' => ClasseOfficielle::orderBy('ordre_enseignement')->orderBy('nom_classe_officielle')->get(),
            'matieres' => Matiere::with('ordres')->orderBy('nom_matiere')->get(),
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

        return response()->json($programme->fresh('classes'));
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

        return response()->json(['success' => true]);
    }
}
