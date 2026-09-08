<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ClasseController as WebClasseController;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClasseController extends WebClasseController
{
    public function index()
    {
        $user = request()->user();
        $idEcole = session('idEcole');

        $classes = $user->droit === 'SupAdmin'
            ? Classe::with(['ecole', 'classeOfficielle'])->withCount('eleves')->get()
            : Classe::with('classeOfficielle')->where('idEcole', $idEcole)->withCount('eleves')->get();

        return response()->json(['data' => $classes]);
    }

    public function show($id)
    {
        $classe = Classe::with(['ligneClasses.matiere', 'ligneClasses.enseignant', 'ecole'])->findOrFail($id);

        $user = request()->user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        return response()->json($classe);
    }

    public function formOptions(Request $request)
    {
        $user = request()->user();
        $idEcole = session('idEcole');

        return response()->json([
            'matieres' => $this->matieresDisponibles($user, $idEcole),
            'enseignants' => $this->enseignantsDisponibles($user, $idEcole),
            'ordres' => $this->ordresDisponibles($user, $idEcole),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('classes_creation');
        $data = $this->validateClasse($request);
        $idEcole = session('idEcole');
        $this->ensureOrdreAllowed($data['ordre_enseignement'], request()->user(), $idEcole);

        $classe = DB::transaction(function () use ($data, $idEcole) {
            $classe = Classe::create([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $data['ordre_enseignement'],
                'idEcole' => $idEcole,
            ]);

            $this->syncLignesClasse($classe, $data);

            return $classe;
        });

        return response()->json($classe->load(['ligneClasses.matiere', 'ligneClasses.enseignant']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('classes_modification');
        $classe = Classe::findOrFail($id);

        $user = request()->user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        $data = $this->validateClasse($request);
        $this->ensureOrdreAllowed($data['ordre_enseignement'], $user, $classe->idEcole);

        DB::transaction(function () use ($classe, $data) {
            $classe->update([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $data['ordre_enseignement'],
            ]);

            $classe->ligneClasses()->delete();
            $this->syncLignesClasse($classe, $data);
        });

        return response()->json($classe->fresh(['ligneClasses.matiere', 'ligneClasses.enseignant']));
    }

    public function destroy($id)
    {
        $this->authorizePermission('classes_supprimer');
        $classe = Classe::withCount('eleves')->findOrFail($id);

        $user = request()->user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        if ($classe->eleves_count > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette classe car des élèves y sont inscrits.',
            ], 422);
        }

        DB::transaction(function () use ($classe) {
            $classe->ligneClasses()->delete();
            $classe->delete();
        });

        return response()->json(['success' => true]);
    }
}
