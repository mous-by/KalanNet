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
        $this->authorizePermission('classes_apercu');
        $user = request()->user();
        $idEcole = session('idEcole');

        $classes = $user->droit === 'SupAdmin'
            ? Classe::with(['ecole', 'classeOfficielle', 'filiere'])->withCount('eleves')->get()
            : Classe::with(['classeOfficielle', 'filiere'])->where('idEcole', $idEcole)->withCount('eleves')->get();

        return response()->json(['data' => $classes]);
    }

    public function show($id)
    {
        $classe = Classe::with(['ligneClasses.matiere', 'ligneClasses.enseignant', 'ecole', 'filiere', 'maitres:id_enseignant,nom_prenom_enseignant'])->findOrFail($id);

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
        $ecole = $this->resolveEcole($user, $idEcole);
        $estSante = $this->estSante($ecole);

        return response()->json([
            'matieres' => $this->matieresDisponibles($user, $idEcole, $estSante),
            'enseignants' => $this->enseignantsDisponibles($user, $idEcole),
            'ordres' => $this->ordresDisponibles($user, $idEcole),
            'filieres' => $this->filieresDisponibles($idEcole),
            'est_sante' => $estSante,
            'nom_jardin_propose' => $this->nomJardinPropose($idEcole),
            'series' => collect(\App\Support\Series::pourNiveau(11))->map(fn ($s, $code) => ['code' => $code, 'libelle' => $s[0], 'niveau' => $s[1]])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('classes_creation');
        $idEcole = session('idEcole');
        $ecole = $this->resolveEcole(request()->user(), $idEcole);
        $estSante = $this->estSante($ecole);
        $data = $this->validateClasse($request, $ecole);

        if (!$estSante) {
            $this->ensureOrdreAllowed($data['ordre_enseignement'], request()->user(), $idEcole);
        }

        $classe = DB::transaction(function () use ($data, $idEcole, $estSante) {
            $classe = Classe::create([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $estSante ? null : $data['ordre_enseignement'],
                'idEcole' => $idEcole,
                'id_filiere' => $estSante ? $data['id_filiere'] : null,
                'serie' => $this->serieValide($data),
            ]);

            $this->syncLignesClasse($classe, $data);

            return $classe;
        });

        return response()->json($classe->load(['ligneClasses.matiere', 'ligneClasses.enseignant', 'filiere', 'maitres:id_enseignant,nom_prenom_enseignant']), 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('classes_modification');
        $classe = Classe::findOrFail($id);

        $user = request()->user();
        if ($user->droit !== 'SupAdmin' && $user->idEcole !== $classe->idEcole) {
            abort(403);
        }

        $estSante = $this->estSante($classe->ecole);
        $data = $this->validateClasse($request, $classe->ecole);

        if (!$estSante) {
            $this->ensureOrdreAllowed($data['ordre_enseignement'], $user, $classe->idEcole);
        }

        DB::transaction(function () use ($classe, $data, $estSante) {
            $classe->update([
                'nom_classe' => $data['nom_classe'],
                'ordreEnseignement' => $estSante ? null : $data['ordre_enseignement'],
                'id_filiere' => $estSante ? $data['id_filiere'] : null,
            ] + (array_key_exists('serie', $data) ? ['serie' => $this->serieValide($data)] : []));

            // Domaine et groupe des matières : gardés si l'application ne les envoie pas.
            $dejaPresentes = $classe->ligneClasses()->pluck('id_matiere')->all();
            $domainesPrecedents = $classe->ligneClasses()->pluck('id_domaine', 'id_matiere')->all();
            $groupesPrecedents = $classe->ligneClasses()->pluck('groupe', 'id_matiere')->all();
            $classe->ligneClasses()->delete();
            $this->syncLignesClasse($classe, $data, $dejaPresentes, $domainesPrecedents, $groupesPrecedents);
        });

        return response()->json($classe->fresh(['ligneClasses.matiere', 'ligneClasses.enseignant', 'filiere', 'maitres:id_enseignant,nom_prenom_enseignant']));
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
