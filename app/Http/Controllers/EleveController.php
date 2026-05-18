<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EleveController extends Controller
{
    public function index(Request $request)
    {
        $idEcole = session('idEcole');
        
        $classes = Classe::where('idEcole', $idEcole)->get();
        $annees = AnneeScolaire::all();

        $showList = $request->filled('id_classe') && $request->filled('id_annee');
        $eleves = collect([]);

        if ($showList) {
            $query = Eleve::where('id_ecole', $idEcole)->where('etat_dossier', 0)
                ->where('id_classe', $request->id_classe)
                ->where('id_annee', $request->id_annee);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nom_eleve', 'LIKE', "%{$search}%")
                      ->orWhere('prenom_eleve', 'LIKE', "%{$search}%")
                      ->orWhere('matricule', 'LIKE', "%{$search}%");
                });
            }

            $eleves = $query->with('classe')
                ->orderBy('nom_eleve')
                ->orderBy('prenom_eleve')
                ->paginate(20);
        } else {
            $eleves = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20, $request->page ?: 1, [
                'path' => url()->current(),
                'query' => $request->query(),
            ]);
        }

        return view('eleves.index', compact('eleves', 'classes', 'annees', 'showList'));
    }

    public function show($id)
    {
        $eleve = Eleve::with(['classe', 'ecole'])->findOrFail($id);
        return view('eleves.show', compact('eleve'));
    }

    // Add other methods (create, store, edit, update, destroy) as needed
}
