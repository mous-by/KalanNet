<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $idEcole = session('idEcole');
        $search = $request->get('search');

        $parents = ParentModel::with('eleves.classe')
            ->where('idEcole', $idEcole)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nom_prenom_parent', 'like', "%{$search}%")
                        ->orWhere('telephone_parent', 'like', "%{$search}%");
                });
            })
            ->paginate(15);

        return view('pedagogie.parents', compact('parents'));
    }

    public function create()
    {
        return view('pedagogie.parents_form', [
            'parent' => new ParentModel(),
            'eleves' => $this->elevesDisponibles(),
            'selectedRows' => collect(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateParent($request);

        DB::transaction(function () use ($data) {
            $parent = ParentModel::create([
                'nom_prenom_parent' => $data['nom_prenom_parent'],
                'telephone_parent' => $data['telephone_parent'],
                'email_parent' => $data['email_parent'] ?? null,
                'genre' => $data['genre'] ?? null,
                'idEcole' => session('idEcole'),
            ]);

            $this->syncEleves($parent, $data);
        });

        return redirect()->route('pedagogie.parents')->with('success', 'Parent et élèves enregistrés avec succès.');
    }

    public function edit($id)
    {
        $parent = ParentModel::with('eleves.classe')->where('idEcole', session('idEcole'))->findOrFail($id);
        $selectedRows = $parent->eleves->map(fn ($eleve) => [
            'id_eleve' => $eleve->id_eleve,
            'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
            'matricule' => $eleve->matricule,
            'classe' => $eleve->classe->nom_classe ?? 'N/A',
            'informer' => $eleve->pivot->informer,
            'lien_parent' => $eleve->pivot->lien_parent,
        ]);

        return view('pedagogie.parents_form', [
            'parent' => $parent,
            'eleves' => $this->elevesDisponibles($parent),
            'selectedRows' => $selectedRows,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $parent = ParentModel::where('idEcole', session('idEcole'))->findOrFail($id);
        $data = $this->validateParent($request);

        DB::transaction(function () use ($parent, $data) {
            $parent->update([
                'nom_prenom_parent' => $data['nom_prenom_parent'],
                'telephone_parent' => $data['telephone_parent'],
                'email_parent' => $data['email_parent'] ?? null,
                'genre' => $data['genre'] ?? null,
            ]);

            $this->syncEleves($parent, $data);
        });

        return redirect()->route('pedagogie.parents.edit', $parent->id_parent)->with('success', 'Modifications enregistrées avec succès.');
    }

    public function destroy($id)
    {
        $parent = ParentModel::where('idEcole', session('idEcole'))->findOrFail($id);

        DB::transaction(function () use ($parent) {
            $parent->eleves()->detach();
            $parent->delete();
        });

        return redirect()->route('pedagogie.parents')->with('success', 'Parent supprimé avec succès.');
    }

    private function validateParent(Request $request): array
    {
        return $request->validate([
            'nom_prenom_parent' => 'required|string|max:255',
            'telephone_parent' => 'required|string|max:100',
            'email_parent' => 'nullable|email|max:255',
            'genre' => 'nullable|string|max:50',
            'id_eleve' => 'required|array|min:1',
            'id_eleve.*' => 'required|integer|exists:eleve,id_eleve',
            'lien_parent' => 'required|array|min:1',
            'lien_parent.*' => 'required|string|max:100',
            'informer' => 'required|array|min:1',
            'informer.*' => 'required|string|in:Oui,Non',
        ], [
            'id_eleve.required' => 'Veuillez sélectionner au moins un élève.',
        ]);
    }

    private function syncEleves(ParentModel $parent, array $data): void
    {
        $sync = [];
        foreach ($data['id_eleve'] as $index => $idEleve) {
            $sync[$idEleve] = [
                'lien_parent' => $data['lien_parent'][$index] ?? 'Parent',
                'informer' => $data['informer'][$index] ?? 'Non',
            ];
        }

        $parent->eleves()->sync($sync);
    }

    private function elevesDisponibles(?ParentModel $parent = null)
    {
        $parentId = $parent?->id_parent;

        return Eleve::with('classe')
            ->where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->where(function ($query) use ($parentId) {
                $query->whereDoesntHave('parents');
                if ($parentId) {
                    $query->orWhereHas('parents', fn ($inner) => $inner->where('parents.id_parent', $parentId));
                }
            })
            ->orderBy('nom_eleve')
            ->get();
    }
}
