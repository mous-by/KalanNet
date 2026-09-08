<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ParentController as WebParentController;
use App\Models\Classe;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Rules\MaliPhone;

class ParentController extends WebParentController
{
    public function index(Request $request)
    {
        $this->authorizePermission('parents_apercu');
        $idEcole = session('idEcole');
        $search = $request->get('search');
        $classeId = $request->get('id_classe');

        $parents = ParentModel::with('eleves.classe')
            ->where('idEcole', $idEcole)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nom_prenom_parent', 'like', "%{$search}%")
                        ->orWhere('telephone_parent', 'like', "%{$search}%")
                        ->orWhere('email_parent', 'like', "%{$search}%")
                        ->orWhereHas('eleves', function ($eleveQuery) use ($search) {
                            $eleveQuery->where('nom_eleve', 'like', "%{$search}%")
                                ->orWhere('prenom_eleve', 'like', "%{$search}%")
                                ->orWhere('matricule', 'like', "%{$search}%");
                        });
                });
            })
            ->when($classeId, function ($query) use ($classeId) {
                $query->whereHas('eleves', fn ($eleveQuery) => $eleveQuery->where('eleve.id_classe', $classeId));
            })
            ->withCount('eleves')
            ->orderBy('nom_prenom_parent')
            ->paginate(20)
            ->withQueryString();

        return response()->json(['parents' => $parents]);
    }

    public function formOptions(Request $request)
    {
        $this->authorizePermission('parents_apercu');
        $ownParentId = $request->integer('id') ?: null;

        return response()->json([
            'classes' => Classe::where('idEcole', session('idEcole'))->orderBy('nom_classe')->get(),
            'eleves' => $this->elevesDisponibles($ownParentId)->map(fn ($eleve) => [
                'id_eleve' => $eleve->id_eleve,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'matricule' => $eleve->matricule,
                'classe' => $eleve->classe?->nom_classe,
            ]),
        ]);
    }

    public function show($id)
    {
        $this->authorizePermission('parents_apercu');
        $parent = ParentModel::with('eleves.classe')->where('idEcole', session('idEcole'))->findOrFail($id);

        return response()->json([
            'parent' => $parent,
            'eleves' => $parent->eleves->map(fn ($eleve) => [
                'id_eleve' => $eleve->id_eleve,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'matricule' => $eleve->matricule,
                'classe' => $eleve->classe?->nom_classe,
                'lien_parent' => $eleve->pivot->lien_parent,
                'informer' => $eleve->pivot->informer,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('parents_creation');
        $data = $this->validateParent($request);
        $this->assertElevesLibres($data['id_eleve']);

        $parent = DB::transaction(function () use ($data) {
            $parent = ParentModel::create([
                'nom_prenom_parent' => $data['nom_prenom_parent'],
                'telephone_parent' => MaliPhone::normalize($data['telephone_parent']),
                'email_parent' => $data['email_parent'] ?? null,
                'genre' => $data['genre'] ?? null,
                'idEcole' => session('idEcole'),
                'pwd' => Hash::make('123456'),
            ]);

            $this->syncEleves($parent, $data);

            return $parent;
        });

        return response()->json($parent->fresh('eleves.classe'), 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('parents_modification');
        $parent = ParentModel::where('idEcole', session('idEcole'))->findOrFail($id);
        $data = $this->validateParent($request);
        $this->assertElevesLibres($data['id_eleve'], $parent->id_parent);

        DB::transaction(function () use ($parent, $data) {
            $parent->update([
                'nom_prenom_parent' => $data['nom_prenom_parent'],
                'telephone_parent' => MaliPhone::normalize($data['telephone_parent']),
                'email_parent' => $data['email_parent'] ?? null,
                'genre' => $data['genre'] ?? null,
            ]);

            $this->syncEleves($parent, $data);
        });

        return response()->json($parent->fresh('eleves.classe'));
    }

    public function destroy($id)
    {
        $this->authorizePermission('parents_supprimer');
        $parent = ParentModel::where('idEcole', session('idEcole'))->findOrFail($id);

        DB::transaction(function () use ($parent) {
            $parent->eleves()->detach();
            $parent->delete();
        });

        return response()->json(['success' => true]);
    }
}
