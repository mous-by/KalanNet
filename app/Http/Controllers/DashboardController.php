<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: ($user->idEcole ?? null);

        $anneeEnCours = AnneeScolaire::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->orderBy('id_anneeScolaire', 'desc')->first();
        $idAnnee = $anneeEnCours ? $anneeEnCours->id_anneeScolaire : null;

        // General stats
        $totalEleves = Eleve::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->where('etat_dossier', 0)->where('id_annee', $idAnnee)->count();

        $totalGarcons = Eleve::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->where('etat_dossier', 0)->where('id_annee', $idAnnee)->where('genre_eleve', 'Masculin')->count();

        $totalFilles = Eleve::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->where('etat_dossier', 0)->where('id_annee', $idAnnee)->where('genre_eleve', 'Féminin')->count();

        $totalEnseignants = Enseignant::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->count();

        $totalClasses = Classe::when($schoolId, function ($query, $schoolId) {
            $query->where('idEcole', $schoolId);
        })->count();
        
        $totalRecettes = \App\Models\Paiement::when($schoolId, function ($query, $schoolId) {
            $query->where('idEcole', $schoolId);
        })->sum('montant');

        $soldeCaisse = \App\Models\Caisse::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->where('status', 1)->value('montant_net') ?? 0;

        // Pedagogical indicators
        $totalAbandons = Eleve::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->where('etat_dossier', 2)->where('id_annee', $idAnnee)->count();

        $tauxAbandon = $totalEleves > 0 ? round(($totalAbandons / ($totalEleves + $totalAbandons)) * 100, 1) : 0;

        // Class distribution
        $classesData = Classe::when($schoolId, function ($query, $schoolId) {
            $query->where('idEcole', $schoolId);
        })->get()->map(function($classe) use ($idAnnee) {
            return [
                'nom' => $classe->nom_classe,
                'total' => $classe->eleves()->where('id_annee', $idAnnee)->where('etat_dossier', 0)->count(),
                'filles' => $classe->eleves()->where('id_annee', $idAnnee)->where('etat_dossier', 0)->where('genre_eleve', 'Féminin')->count(),
                'garcons' => $classe->eleves()->where('id_annee', $idAnnee)->where('etat_dossier', 0)->where('genre_eleve', 'Masculin')->count(),
            ];
        })->sortByDesc('total');

        $recentEleves = Eleve::with('classe')
            ->when($schoolId, function ($query, $schoolId) {
                $query->where('id_ecole', $schoolId);
            })
            ->where('etat_dossier', 0)
            ->where('id_annee', $idAnnee)
            ->orderBy('id_eleve', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalEleves', 
            'totalGarcons', 
            'totalFilles', 
            'totalEnseignants', 
            'totalClasses',
            'totalRecettes',
            'soldeCaisse',
            'recentEleves',
            'anneeEnCours',
            'tauxAbandon',
            'totalAbandons',
            'classesData'
        ));
    }
}
