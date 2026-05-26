<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use App\Models\Eleve;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('parents_apercu');

        $idEcole = session('idEcole');
        $search = $request->get('search');
        $classeId = $request->get('id_classe');

        $classes = Classe::where('idEcole', $idEcole)
            ->orderBy('nom_classe')
            ->get();

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
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'parents' => ParentModel::where('idEcole', $idEcole)->count(),
            'contacts_informables' => ParentModel::where('idEcole', $idEcole)
                ->whereHas('eleves', fn ($query) => $query->where('ligneparents_eleves.informer', 'Oui'))
                ->count(),
            'eleves_rattaches' => Eleve::where('id_ecole', $idEcole)
                ->where('etat_dossier', 0)
                ->whereHas('parents')
                ->count(),
        ];

        return view('pedagogie.parents', compact('parents', 'classes', 'stats'));
    }

    public function create()
    {
        $this->authorizePermission('parents_creation');

        return view('pedagogie.parents_form', [
            'parent' => new ParentModel(),
            'eleves' => $this->elevesDisponibles(),
            'selectedRows' => $this->selectedRowsFromOldInput(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('parents_creation');

        $data = $this->validateParent($request);

        DB::transaction(function () use ($data) {
            $parent = ParentModel::create([
                'nom_prenom_parent' => $data['nom_prenom_parent'],
                'telephone_parent' => $data['telephone_parent'],
                'email_parent' => $data['email_parent'] ?? null,
                'genre' => $data['genre'] ?? null,
                'idEcole' => session('idEcole'),
                'pwd' => Hash::make('123456'),
            ]);

            $this->syncEleves($parent, $data);
        });

        return redirect()->route('pedagogie.parents')->with('success', 'Parent et élèves enregistrés avec succès.');
    }

    public function edit($id)
    {
        $this->authorizePermission('parents_modification');

        $parent = ParentModel::with('eleves.classe')->where('idEcole', session('idEcole'))->findOrFail($id);
        $selectedRows = old('id_eleve')
            ? $this->selectedRowsFromOldInput()
            : $parent->eleves->map(fn ($eleve) => [
                'id_eleve' => $eleve->id_eleve,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'matricule' => $eleve->matricule,
                'classe' => $eleve->classe->nom_classe ?? 'N/A',
                'informer' => $eleve->pivot->informer,
                'lien_parent' => $eleve->pivot->lien_parent,
            ]);

        return view('pedagogie.parents_form', [
            'parent' => $parent,
            'eleves' => $this->elevesDisponibles(),
            'selectedRows' => $selectedRows,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('parents_modification');

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
        $this->authorizePermission('parents_supprimer');

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
            'id_eleve.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('eleve', 'id_eleve')->where('id_ecole', session('idEcole')),
            ],
            'lien_parent' => 'required|array|min:1',
            'lien_parent.*' => 'required|string|max:100',
            'informer' => 'required|array|min:1',
            'informer.*' => 'required|string|in:Oui,Non',
        ], [
            'id_eleve.required' => 'Veuillez sélectionner au moins un élève.',
            'id_eleve.*.distinct' => 'Un élève ne doit être ajouté qu’une seule fois pour le même parent.',
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

    private function elevesDisponibles()
    {
        return Eleve::with('classe')
            ->where('id_ecole', session('idEcole'))
            ->where('etat_dossier', 0)
            ->orderBy('prenom_eleve')->orderBy('nom_eleve')
            ->get();
    }

    private function selectedRowsFromOldInput()
    {
        $ids = collect(old('id_eleve', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $eleves = Eleve::with('classe')
            ->where('id_ecole', session('idEcole'))
            ->whereIn('id_eleve', $ids)
            ->get()
            ->keyBy('id_eleve');

        $liens = old('lien_parent', []);
        $informers = old('informer', []);

        return $ids->map(function ($id, $index) use ($eleves, $liens, $informers) {
            $eleve = $eleves->get($id);

            if (!$eleve) {
                return null;
            }

            return [
                'id_eleve' => $eleve->id_eleve,
                'nom' => trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve),
                'matricule' => $eleve->matricule,
                'classe' => $eleve->classe->nom_classe ?? 'N/A',
                'informer' => $informers[$index] ?? 'Oui',
                'lien_parent' => $liens[$index] ?? 'Parent',
            ];
        })->filter()->values();
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::user();

        if (!$user || $user->droit === 'parent' || ($user->droit !== 'SupAdmin' && !$user->userHasPermission($permission))) {
            abort(403, 'Permission insuffisante.');
        }
    }

    private function authorizeAnyPermission(array $permissions): void
    {
        $user = Auth::user();

        if (!$user || $user->droit === 'parent' || ($user->droit !== 'SupAdmin' && !$user->userHasAnyPermission($permissions))) {
            abort(403, 'Permission insuffisante.');
        }
    }
}
