<?php

namespace App\Http\Controllers;

use App\Models\AnneeScolaire;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Classe;
use App\Models\Emargement;
use App\Models\LigneClasse;
use App\Models\LigneEvaluation;
use App\Models\Paiement;
use App\Models\ParentModel;
use App\Models\PlanPaiement;
use App\Models\Presence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: ($user->idEcole ?? null);

        if ($user->droit === 'enseignant') {
            return $this->teacherDashboard($user, $schoolId);
        }

        if ($user->droit === 'parent') {
            return $this->parentDashboard($user, $schoolId);
        }

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

    private function teacherDashboard($user, ?int $schoolId)
    {
        $teacherId = $user->id_enseignant;
        $enseignant = $user->enseignant;

        $assignments = LigneClasse::with(['classe', 'matiere'])
            ->where('id_enseignants', $teacherId)
            ->when($schoolId, fn ($query) => $query->whereHas('classe', fn ($classe) => $classe->where('idEcole', $schoolId)))
            ->orderBy('id_classe')
            ->get();

        $classeIds = $assignments->pluck('id_classe')->filter()->unique()->values();
        $matiereIds = $assignments->pluck('id_matiere')->filter()->unique()->values();

        $totalClasses = $classeIds->count();
        $totalMatieres = $matiereIds->count();

        $totalEleves = Eleve::whereIn('id_classe', $classeIds)
            ->where('etat_dossier', 0)
            ->count();

        $emargements = Emargement::with(['classe', 'matiere'])
            ->where('id_enseignant', $teacherId);

        $presences = Presence::with(['classe'])
            ->where('id_enseignant', $teacherId);

        $evaluations = LigneEvaluation::with(['evaluation', 'classe', 'matiere'])
            ->where('id_enseignant', $teacherId);

        $totalEmargements = (clone $emargements)->count();
        $heuresEmargees = (clone $emargements)->sum('nombre_heure');
        $totalPresences = (clone $presences)->count();
        $evaluationsCount = (clone $evaluations)->distinct('id_evaluation')->count('id_evaluation');

        $recentEmargements = (clone $emargements)
            ->orderByDesc('date_emargement')
            ->limit(6)
            ->get();

        $recentPresences = (clone $presences)
            ->orderByDesc('date_presence')
            ->limit(6)
            ->get();

        $recentEvaluations = (clone $evaluations)
            ->latest('id_ligneEvaluation')
            ->limit(6)
            ->get()
            ->unique('id_evaluation')
            ->values();

        $teacherPermissions = [
            'emargement' => $user->userHasPermission('emargement_faire'),
            'presence' => $user->userHasPermission('presence_apercu'),
            'evaluations' => $user->userHasPermission('evaluation_apercu'),
            'programmes' => $user->userHasAnyPermission(['programmes_apercu', 'programmes_pdf', 'voir_pdf_programme']),
            'timetable' => $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('classes_apercu'),
        ];

        return view('dashboards.teacher', compact(
            'enseignant',
            'assignments',
            'totalClasses',
            'totalMatieres',
            'totalEleves',
            'totalEmargements',
            'heuresEmargees',
            'totalPresences',
            'evaluationsCount',
            'recentEmargements',
            'recentPresences',
            'recentEvaluations',
            'teacherPermissions'
        ));
    }

    private function parentDashboard($user, ?int $schoolId)
    {
        $parent = $user->parent ?: ParentModel::with('ecole')->find($user->id_parent);

        if (!$parent) {
            abort(403, 'Aucun profil parent n’est lié à ce compte.');
        }

        $anneeEnCours = AnneeScolaire::when($schoolId, fn ($query, $id) => $query->where('id_ecole', $id))
            ->orderByDesc('id_anneeScolaire')
            ->first();

        $children = $parent->eleves()
            ->with(['classe', 'plansPaiement.echeances.paiements'])
            ->when($schoolId, fn ($query, $id) => $query->where('eleve.id_ecole', $id))
            ->where('etat_dossier', 0)
            ->orderBy('prenom_eleve')
            ->orderBy('nom_eleve')
            ->get();

        $childIds = $children->pluck('id_eleve')->filter()->values();

        $payments = Paiement::with(['eleve', 'classe'])
            ->whereIn('id_eleve', $childIds)
            ->when($schoolId, fn ($query, $id) => $query->where('idEcole', $id))
            ->where(function ($query) {
                $query->whereNull('statut')->orWhere('statut', 'valide');
            })
            ->orderByDesc('date_paiement')
            ->limit(8)
            ->get();

        $plans = PlanPaiement::with(['eleve', 'classe', 'anneeScolaire', 'echeances.paiements'])
            ->whereIn('eleve_id', $childIds)
            ->when($schoolId, fn ($query, $id) => $query->where('ecole_id', $id))
            ->when($anneeEnCours, fn ($query, $annee) => $query->where('annee_scolaire_id', $annee->id_anneeScolaire))
            ->get();

        $financialRows = $plans->map(function (PlanPaiement $plan) {
            $echeanceIds = $plan->echeances->pluck('id')->filter();
            $paid = Paiement::whereIn('echeance_id', $echeanceIds)
                ->where(function ($query) {
                    $query->whereNull('statut')->orWhere('statut', 'valide');
                })
                ->sum('montant_paye');
            $expected = (float) ($plan->montant_final ?: $plan->montant_total);
            $remaining = max($expected - (float) $paid, 0);
            $lateCount = $plan->echeances->filter(fn ($echeance) => $echeance->date_limite && $echeance->date_limite->isPast() && $echeance->statut !== 'paye')->count();

            return [
                'eleve' => $plan->eleve,
                'classe' => $plan->classe,
                'annee' => $plan->anneeScolaire,
                'attendu' => $expected,
                'paye' => (float) $paid,
                'reste' => $remaining,
                'retards' => $lateCount,
                'statut' => $remaining <= 0 ? 'À jour' : ($lateCount > 0 ? 'Retard' : 'En cours'),
            ];
        });

        $totalExpected = $financialRows->sum('attendu');
        $totalPaid = $financialRows->sum('paye');
        $totalRemaining = $financialRows->sum('reste');
        $latePlans = $financialRows->where('retards', '>', 0)->count();
        $annonces = $this->parentAnnouncements($parent, $schoolId);

        return view('dashboards.parent', compact(
            'parent',
            'children',
            'payments',
            'financialRows',
            'annonces',
            'anneeEnCours',
            'totalExpected',
            'totalPaid',
            'totalRemaining',
            'latePlans'
        ));
    }

    private function parentAnnouncements(ParentModel $parent, ?int $schoolId)
    {
        if (!$schoolId || !Schema::hasTable('annonces_admin_gestionnaire')) {
            return collect();
        }

        return DB::table('annonces_admin_gestionnaire as annonces')
            ->leftJoin('utilisateurs as users', 'users.idUtilisateur', '=', 'annonces.id_utilisateur')
            ->where('annonces.id_ecole', $schoolId)
            ->whereIn('annonces.public_cible', ['tous', 'parents', 'parent'])
            ->select([
                'annonces.id_annonce',
                'annonces.titre',
                'annonces.contenu',
                'annonces.public_cible',
                'annonces.fichier_joint',
                'annonces.type_fichier',
                'annonces.date_publication',
                'users.nomPrenom as auteur',
            ])
            ->orderByDesc('annonces.date_publication')
            ->limit(5)
            ->get();
    }
}
