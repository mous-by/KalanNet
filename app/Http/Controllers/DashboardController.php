<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\AnneeScolaire;
use App\Models\AppNotification;
use App\Models\AppelEpreuve;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Classe;
use App\Models\Ecole;
use App\Models\Emargement;
use App\Models\LigneClasse;
use App\Models\LigneEvaluation;
use App\Models\Paiement;
use App\Models\ParentModel;
use App\Models\PlanPaiement;
use App\Models\Presence;
use Carbon\Carbon;
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

        if ($user->droit === 'SupAdmin') {
            return $this->supAdminDashboard($user);
        }

        $anneeEnCours = AnneeScolaire::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->orderBy('id_anneeScolaire', 'desc')->first();
        $idAnnee = $anneeEnCours ? $anneeEnCours->id_anneeScolaire : null;

        // General stats
        $activeStudentsQuery = Eleve::when($schoolId, function ($query, $schoolId) {
            $query->where('id_ecole', $schoolId);
        })->when($idAnnee, function ($query, $idAnnee) {
            $query->where('id_annee', $idAnnee);
        })->where('etat_dossier', 0);

        $totalEleves = (clone $activeStudentsQuery)->count();
        $totalGarcons = (clone $activeStudentsQuery)->whereIn('genre_eleve', ['Masculin', 'M', 'Garçon', 'Garcon', 'Homme', 'H'])->count();
        $totalFilles = (clone $activeStudentsQuery)->whereIn('genre_eleve', ['Féminin', 'Feminin', 'F', 'Fille', 'Femme'])->count();

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
        })->when($idAnnee, function ($query, $idAnnee) {
            $query->where('id_annee', $idAnnee);
        })->where('etat_dossier', 2)->count();

        $tauxAbandon = $totalEleves > 0 ? round(($totalAbandons / ($totalEleves + $totalAbandons)) * 100, 1) : 0;

        // Class distribution
        $classesData = Classe::when($schoolId, function ($query, $schoolId) {
            $query->where('idEcole', $schoolId);
        })->get()->map(function($classe) use ($idAnnee) {
            $students = $classe->eleves()
                ->when($idAnnee, fn ($query, $idAnnee) => $query->where('id_annee', $idAnnee))
                ->where('etat_dossier', 0);

            return [
                'nom' => $classe->nom_classe,
                'total' => (clone $students)->count(),
                'filles' => (clone $students)->whereIn('genre_eleve', ['Féminin', 'Feminin', 'F', 'Fille', 'Femme'])->count(),
                'garcons' => (clone $students)->whereIn('genre_eleve', ['Masculin', 'M', 'Garçon', 'Garcon', 'Homme', 'H'])->count(),
            ];
        })->sortByDesc('total');

        $recentEleves = Eleve::with('classe')
            ->when($schoolId, function ($query, $schoolId) {
                $query->where('id_ecole', $schoolId);
            })
            ->when($idAnnee, function ($query, $idAnnee) {
                $query->where('id_annee', $idAnnee);
            })
            ->where('etat_dossier', 0)
            ->orderBy('id_eleve', 'desc')
            ->limit(5)
            ->get();

        $teacherProgressRows = $this->schoolTeacherProgress($schoolId, $idAnnee);
        $presenceProgressRows = $this->schoolPresenceProgress($schoolId, $idAnnee);
        $subscriptionOverview = $user->droit === 'SupAdmin' ? $this->subscriptionOverview() : collect();

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
            'classesData',
            'teacherProgressRows',
            'presenceProgressRows',
            'subscriptionOverview'
        ));
    }

    public function updateSubscriptionDates(Request $request, Abonnement $abonnement)
    {
        if (Auth::user()?->droit !== 'SupAdmin') {
            abort(403);
        }

        $data = $request->validate([
            'debut_at' => 'required|date',
            'fin_at' => 'required|date|after:debut_at',
        ]);

        $abonnement->update([
            'statut' => 'actif',
            'debut_at' => Carbon::parse($data['debut_at'])->startOfDay(),
            'fin_at' => Carbon::parse($data['fin_at'])->endOfDay(),
        ]);

        $this->notifySchoolUsersSubscriptionUpdated($abonnement);

        return back()->with('success', 'Dates de l’abonnement mises à jour.');
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
        $teacherPresenceProgressRows = $this->schoolPresenceProgress($schoolId, null, $teacherId);

        $teacherPermissions = [
            'emargement' => $user->userHasPermission('emargement_faire'),
            'presence' => $user->droit === 'enseignant' || $user->userHasPermission('presence_apercu'),
            'evaluations' => $user->userHasPermission('evaluation_apercu'),
            'programmes' => $user->userHasAnyPermission(['programmes_apercu', 'programmes_pdf', 'voir_pdf_programme']),
            'timetable' => $user->droit === 'enseignant' || $user->userHasPermission('enseignants_emploi') || $user->userHasPermission('classes_apercu'),
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
            'teacherPresenceProgressRows',
            'teacherPermissions'
        ));
    }

    private function supAdminDashboard($user)
    {
        $subscriptionOverview = $this->subscriptionOverview();
        
        // Connected Users (active in the last 15 minutes)
        $connectedUsers = \App\Models\User::with('ecole')
            ->whereNotNull('last_activity')
            ->where('last_activity', '>=', now()->subMinutes(15))
            ->orderByDesc('last_activity')
            ->get();

        // App Health Stats
        $health = [
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'php_version' => phpversion(),
            'disk_free_space' => function_exists('disk_free_space') ? round(disk_free_space('/') / 1024 / 1024 / 1024, 2) : 'N/A', // en GB
            'disk_total_space' => function_exists('disk_total_space') ? round(disk_total_space('/') / 1024 / 1024 / 1024, 2) : 'N/A', // en GB
            'db_connection' => 'OK',
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['db_connection'] = 'ERREUR';
        }

        // Pending Subscriptions to validate
        $pendingValidations = \App\Models\AbonnementPaiement::with(['offre', 'ecole'])
            ->where('statut', 'en_attente')
            ->orderByDesc('id')
            ->get();

        return view('dashboards.supadmin', compact(
            'subscriptionOverview',
            'connectedUsers',
            'health',
            'pendingValidations'
        ));
    }

    private function subscriptionOverview()
    {
        return Ecole::query()
            ->orderBy('nomEcole')
            ->get()
            ->map(function (Ecole $ecole) {
                $abonnement = Abonnement::with('offre')
                    ->where('ecole_id', $ecole->idEcole)
                    ->orderByRaw("CASE WHEN statut = 'actif' THEN 0 ELSE 1 END")
                    ->orderByDesc('fin_at')
                    ->orderByDesc('id')
                    ->first();

                $daysRemaining = null;
                if ($abonnement?->fin_at) {
                    $daysRemaining = now()->startOfDay()->diffInDays($abonnement->fin_at->copy()->startOfDay(), false);
                }

                return [
                    'ecole' => $ecole,
                    'abonnement' => $abonnement,
                    'days_remaining' => $daysRemaining,
                ];
            });
    }

    private function notifySchoolUsersSubscriptionUpdated(Abonnement $abonnement): void
    {
        if (!Schema::hasTable('app_notifications')) {
            return;
        }

        $abonnement->loadMissing('ecole', 'offre');
        $message = 'Les dates de votre abonnement ont été mises à jour. Nouvelle fin: '
            . optional($abonnement->fin_at)->format('d/m/Y') . '.';

        $users = \App\Models\User::where('idEcole', $abonnement->ecole_id)
            ->whereIn('droit', ['Admin', 'Gestionnaire'])
            ->get();

        foreach ($users as $target) {
            AppNotification::create([
                'user_id' => $target->idUtilisateur,
                'type' => 'abonnement',
                'title' => 'Abonnement mis à jour',
                'message' => $message,
                'link' => route('abonnements.index'),
                'data' => [
                    'event' => 'SUBSCRIPTION_DATES_UPDATED',
                    'abonnement_id' => $abonnement->id,
                    'ecole_id' => $abonnement->ecole_id,
                ],
            ]);
        }
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

        $allPayments = Paiement::with(['eleve', 'classe'])
            ->whereIn('id_eleve', $childIds)
            ->when($schoolId, fn ($query, $id) => $query->where('idEcole', $id))
            ->where(function ($query) {
                $query->whereNull('statut')->orWhere('statut', 'valide');
            })
            ->get();

        $payments = $allPayments
            ->sortByDesc('date_paiement')
            ->take(8)
            ->values();

        $financialRows = $children
            ->map(fn (Eleve $child) => $this->parentFinancialRow($child, $allPayments->where('id_eleve', $child->id_eleve)->values()))
            ->filter(fn (array $row) => $row['attendu'] > 0 || $row['paye'] > 0)
            ->values();

        $totalExpected = $financialRows->sum('attendu');
        $totalPaid = $financialRows->sum('paye');
        $totalRemaining = $financialRows->sum('reste');
        $latePlans = $financialRows->where('retards', '>', 0)->count();
        $annonces = $this->parentAnnouncements($parent, $schoolId);
        $publishedBulletins = $this->parentPublishedBulletins($children);
        $childrenProgressRows = $this->parentChildrenProgress($children, $anneeEnCours?->id_anneeScolaire);
        $childrenPresenceProgressRows = $this->parentChildrenPresenceProgress($children, $anneeEnCours?->id_anneeScolaire);
        $callPeriod = request('appel_presence_periode', 'today');
        $childrenCallRows = $this->parentChildrenCallRows($children, $callPeriod);

        return view('dashboards.parent', compact(
            'parent',
            'children',
            'payments',
            'financialRows',
            'childrenProgressRows',
            'childrenPresenceProgressRows',
            'childrenCallRows',
            'callPeriod',
            'annonces',
            'publishedBulletins',
            'anneeEnCours',
            'totalExpected',
            'totalPaid',
            'totalRemaining',
            'latePlans'
        ));
    }

    private function schoolTeacherProgress(?int $schoolId, ?int $idAnnee)
    {
        $assignments = LigneClasse::with(['enseignant', 'classe', 'matiere'])
            ->when($schoolId, fn ($query, $id) => $query->whereHas('classe', fn ($classe) => $classe->where('idEcole', $id)))
            ->get()
            ->filter(fn ($line) => $line->enseignant && $line->classe && $line->matiere);

        if ($assignments->isEmpty()) {
            return collect();
        }

        $classIds = $assignments->pluck('id_classe')->filter()->unique()->values();
        $matterIds = $assignments->pluck('id_matiere')->filter()->unique()->values();

        $totalLessons = DB::table('classe as c')
            ->join('programme_classes as pc', 'c.id_classe_officielle', '=', 'pc.id_classe')
            ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
            ->whereIn('c.id_classe', $classIds)
            ->whereIn('pc.id_matiere', $matterIds)
            ->select('c.id_classe', 'pc.id_matiere', DB::raw('COUNT(DISTINCT pl.id_lecon) as total'))
            ->groupBy('c.id_classe', 'pc.id_matiere')
            ->get()
            ->keyBy(fn ($row) => $row->id_classe . '_' . $row->id_matiere);

        $completedLessons = Emargement::query()
            ->where('valide', 1)
            ->whereIn('id_enseignant', $assignments->pluck('id_enseignants')->filter()->unique())
            ->whereIn('id_classe', $classIds)
            ->whereIn('id_matiere', $matterIds)
            ->when($idAnnee, fn ($query, $id) => $query->where('id_anneeScolaire', $id))
            ->select(
                'id_enseignant',
                'id_classe',
                'id_matiere',
                DB::raw('COUNT(DISTINCT id_lecon) as completed'),
                DB::raw('SUM(CAST(nombre_heure AS DECIMAL(10,2))) as hours')
            )
            ->groupBy('id_enseignant', 'id_classe', 'id_matiere')
            ->get()
            ->keyBy(fn ($row) => $row->id_enseignant . '_' . $row->id_classe . '_' . $row->id_matiere);

        return $assignments
            ->map(function ($line) use ($totalLessons, $completedLessons) {
                $total = (int) ($totalLessons[$line->id_classe . '_' . $line->id_matiere]->total ?? 0);
                $done = $completedLessons[$line->id_enseignants . '_' . $line->id_classe . '_' . $line->id_matiere] ?? null;
                $completed = min((int) ($done->completed ?? 0), $total);
                $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

                return [
                    'teacher' => $line->enseignant->nom_prenom_enseignant,
                    'classe' => $line->classe->nom_classe,
                    'matiere' => $line->matiere->nom_matiere,
                    'completed' => $completed,
                    'total' => $total,
                    'hours' => (float) ($done->hours ?? 0),
                    'percent' => $percent,
                ];
            })
            ->sortBy('percent')
            ->values()
            ->take(10);
    }

    private function schoolPresenceProgress(?int $schoolId, ?int $idAnnee = null, ?int $teacherId = null)
    {
        return Presence::with(['enseignant', 'classe', 'lecons'])
            ->where('valide', 1)
            ->when($schoolId, fn ($query, $id) => $query->where('id_ecole', $id))
            ->when($idAnnee, fn ($query, $id) => $query->where('id_anneeScolaire', $id))
            ->when($teacherId, fn ($query, $id) => $query->where('id_enseignant', $id))
            ->orderByDesc('date_presence')
            ->limit(20)
            ->get()
            ->flatMap(function (Presence $presence) {
                return $presence->lecons->map(function ($lecon) use ($presence) {
                    return [
                        'teacher' => $presence->enseignant?->nom_prenom_enseignant ?? 'Enseignant',
                        'classe' => $presence->classe?->nom_classe ?? 'Classe',
                        'date' => $presence->date_presence,
                        'titre' => $lecon->titre,
                        'hours' => (float) $lecon->nombre_heure,
                        'percent' => round((float) $lecon->progression, 2),
                    ];
                });
            })
            ->take(30)
            ->values();
    }

    private function parentChildrenProgress($children, ?int $idAnnee)
    {
        $classIds = $children->pluck('id_classe')->filter()->unique()->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        $assignments = LigneClasse::with(['enseignant', 'classe', 'matiere'])
            ->whereIn('id_classe', $classIds)
            ->get()
            ->filter(fn ($line) => $line->classe && $line->matiere);

        if ($assignments->isEmpty()) {
            return collect();
        }

        $matterIds = $assignments->pluck('id_matiere')->filter()->unique()->values();

        $totalLessons = DB::table('classe as c')
            ->join('programme_classes as pc', 'c.id_classe_officielle', '=', 'pc.id_classe')
            ->join('programme_lecons as pl', 'pc.id_programme_classe', '=', 'pl.id_programme_classe')
            ->whereIn('c.id_classe', $classIds)
            ->whereIn('pc.id_matiere', $matterIds)
            ->select('c.id_classe', 'pc.id_matiere', DB::raw('COUNT(DISTINCT pl.id_lecon) as total'))
            ->groupBy('c.id_classe', 'pc.id_matiere')
            ->get()
            ->keyBy(fn ($row) => $row->id_classe . '_' . $row->id_matiere);

        $completedLessons = Emargement::query()
            ->where('valide', 1)
            ->whereIn('id_classe', $classIds)
            ->whereIn('id_matiere', $matterIds)
            ->when($idAnnee, fn ($query, $id) => $query->where('id_anneeScolaire', $id))
            ->select('id_classe', 'id_matiere', DB::raw('COUNT(DISTINCT id_lecon) as completed'))
            ->groupBy('id_classe', 'id_matiere')
            ->get()
            ->keyBy(fn ($row) => $row->id_classe . '_' . $row->id_matiere);

        return $children
            ->flatMap(function (Eleve $child) use ($assignments, $totalLessons, $completedLessons) {
                return $assignments
                    ->where('id_classe', $child->id_classe)
                    ->map(function ($line) use ($child, $totalLessons, $completedLessons) {
                        $key = $line->id_classe . '_' . $line->id_matiere;
                        $total = (int) ($totalLessons[$key]->total ?? 0);
                        $completed = min((int) ($completedLessons[$key]->completed ?? 0), $total);
                        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

                        return [
                            'child' => $child,
                            'classe' => $child->classe,
                            'teacher' => $line->enseignant?->nom_prenom_enseignant ?? 'Non assigné',
                            'teacher_phone' => $line->enseignant?->telephone_enseignant,
                            'matiere' => $line->matiere->nom_matiere,
                            'completed' => $completed,
                            'total' => $total,
                            'percent' => $percent,
                        ];
                    });
            })
            ->sortBy(fn ($row) => ($row['child']->prenom_eleve ?? '') . ' ' . ($row['child']->nom_eleve ?? '') . ' ' . $row['matiere'])
            ->values();
    }

    private function parentChildrenPresenceProgress($children, ?int $idAnnee)
    {
        $classIds = $children->pluck('id_classe')->filter()->unique()->values();

        if ($classIds->isEmpty()) {
            return collect();
        }

        $presencesByClass = Presence::with(['enseignant', 'classe', 'lecons'])
            ->where('valide', 1)
            ->whereIn('id_classe', $classIds)
            ->when($idAnnee, fn ($query, $id) => $query->where('id_anneeScolaire', $id))
            ->orderByDesc('date_presence')
            ->get()
            ->groupBy('id_classe');

        return $children
            ->flatMap(function (Eleve $child) use ($presencesByClass) {
                return ($presencesByClass[$child->id_classe] ?? collect())
                    ->flatMap(function (Presence $presence) use ($child) {
                        return $presence->lecons->map(function ($lecon) use ($child, $presence) {
                            return [
                                'child' => $child,
                                'classe' => $child->classe,
                                'teacher' => $presence->enseignant?->nom_prenom_enseignant ?? 'Enseignant',
                                'teacher_phone' => $presence->enseignant?->telephone_enseignant,
                                'date' => $presence->date_presence,
                                'titre' => $lecon->titre,
                                'hours' => (float) $lecon->nombre_heure,
                                'percent' => round((float) $lecon->progression, 2),
                            ];
                        });
                    });
            })
            ->take(40)
            ->values();
    }

    private function parentChildrenCallRows($children, string $period = 'today')
    {
        $childIds = $children->pluck('id_eleve')->filter()->unique()->values();

        if ($childIds->isEmpty()) {
            return collect();
        }

        $query = AppelEpreuve::with(['eleve', 'classe', 'matiere', 'statutControle'])
            ->whereIn('id_eleve', $childIds)
            ->where('notifier_parent', true);

        if ($period === 'today') {
            $query->whereDate('date', now()->toDateString());
        } elseif ($period === '7days') {
            $query->whereDate('date', '>=', now()->subDays(7)->toDateString());
        } elseif ($period === '30days') {
            $query->whereDate('date', '>=', now()->subDays(30)->toDateString());
        }

        return $query
            ->orderByDesc('date')
            ->orderByDesc('id_controle_eleve')
            ->limit($period === 'all' ? 30 : 12)
            ->get()
            ->map(fn (AppelEpreuve $appel) => [
                'child' => $appel->eleve,
                'classe' => $appel->classe,
                'matiere' => $appel->matiere,
                'statut' => $appel->statutControle,
                'date' => $appel->date,
                'libelle' => $appel->libelle,
                'heure_debut' => $appel->heure_debut,
                'heure_fin' => $appel->heure_fin,
            ]);
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
            ->when(Schema::hasColumn('annonces_admin_gestionnaire', 'statut_annonce'), fn ($query) => $query->where('annonces.statut_annonce', 'publie'))
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

    private function parentPublishedBulletins($children)
    {
        if ($children->isEmpty() || !Schema::hasTable('bulletin_publications')) {
            return collect();
        }

        $classIds = $children->pluck('id_classe')->filter()->unique()->values();
        $yearIds = $children->pluck('id_annee')->filter()->unique()->values();
        $childrenByClass = $children->groupBy('id_classe');

        $publications = DB::table('bulletin_publications as bp')
            ->leftJoin('trimestre as t', 'bp.id_trimestre', '=', 't.id_trimestre')
            ->whereIn('bp.id_classe', $classIds)
            ->whereIn('bp.id_annee', $yearIds)
            ->whereNotNull('bp.published_at')
            ->select('bp.*', 't.nom_trimestre')
            ->orderByDesc('bp.published_at')
            ->get();

        $moisOptions = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];

        return $publications
            ->flatMap(function ($publication) use ($childrenByClass, $moisOptions) {
                return ($childrenByClass[$publication->id_classe] ?? collect())
                    ->where('id_annee', $publication->id_annee)
                    ->map(function (Eleve $child) use ($publication, $moisOptions) {
                        return [
                            'child' => $child,
                            'periode' => $publication->mois
                                ? ($moisOptions[(int) $publication->mois] ?? 'Mois')
                                : ($publication->nom_trimestre ?? 'Trimestre'),
                            'published_at' => $publication->published_at,
                            'url' => route('pedagogie.bulletins.download', [
                                'id' => $child->id_eleve,
                                'id_annee' => $publication->id_annee,
                                'id_trimestre' => $publication->id_trimestre,
                                'mois' => $publication->mois,
                            ]),
                        ];
                    });
            })
            ->take(10)
            ->values();
    }

    private function parentFinancialRow(Eleve $child, $payments): array
    {
        $payments = $payments->filter(function ($payment) use ($child) {
            return empty($payment->id_annee) || empty($child->id_annee) || (int) $payment->id_annee === (int) $child->id_annee;
        });

        $plan = $child->plansPaiement
            ->when($child->id_annee, fn ($plans) => $plans->where('annee_scolaire_id', $child->id_annee))
            ->sortByDesc('id')
            ->first();

        $legacyPlanifications = $this->parentLegacyPlanifications($child);
        $legacyExpected = $legacyPlanifications->sum(fn ($planification) => (float) $planification->montant_planification);
        $expected = (float) ($plan?->montant_final ?: $plan?->montant_total ?: 0);
        if ($expected <= 0 && $legacyExpected > 0) {
            $expected = $legacyExpected;
        }

        $paid = $payments->sum(fn ($payment) => (float) ($payment->montant_paye ?? $payment->montant ?? 0));
        $remaining = max($expected - $paid, 0);
        $lateCount = $this->parentLatePaymentsCount($plan, $legacyPlanifications, $payments);

        return [
            'eleve' => $child,
            'classe' => $child->classe,
            'annee' => null,
            'attendu' => $expected,
            'paye' => $paid,
            'reste' => $remaining,
            'retards' => $lateCount,
            'statut' => $remaining <= 0 && $expected > 0 ? 'À jour' : ($lateCount > 0 ? 'Retard' : 'En cours'),
        ];
    }

    private function parentLatePaymentsCount(?PlanPaiement $plan, $legacyPlanifications, $payments): int
    {
        if ($plan) {
            return $plan->echeances->filter(function ($echeance) use ($payments) {
                $paid = $payments
                    ->where('echeance_id', $echeance->id)
                    ->sum(fn ($payment) => (float) ($payment->montant_paye ?? $payment->montant ?? 0));

                return $echeance->date_limite
                    && $echeance->date_limite->isPast()
                    && max((float) $echeance->montant_prevu - $paid, 0) > 0;
            })->count();
        }

        return $legacyPlanifications->filter(function ($planification) use ($payments) {
            if (!$planification->date_fin) {
                return false;
            }

            $paid = $payments
                ->where('id_planification', $planification->id_planification)
                ->sum(fn ($payment) => (float) ($payment->montant_paye ?? $payment->montant ?? 0));

            return \Illuminate\Support\Carbon::parse($planification->date_fin)->endOfDay()->isPast()
                && max((float) $planification->montant_planification - $paid, 0) > 0;
        })->count();
    }

    private function parentLegacyPlanifications(Eleve $child)
    {
        if (!Schema::hasTable('ligne_inscription') || !Schema::hasTable('planification')) {
            return collect();
        }

        return DB::table('ligne_inscription as li')
            ->join('planification as p', 'li.id_planification', '=', 'p.id_planification')
            ->where('li.id_eleve', $child->id_eleve)
            ->when($child->id_annee, fn ($query) => $query->where('li.id_annee', $child->id_annee))
            ->select([
                'p.id_planification',
                'p.motif',
                'p.date_fin',
                'p.montant_planification',
            ])
            ->get();
    }

    public function markNotificationAsRead(Request $request, $id)
    {
        $notification = AppNotification::where('user_id', Auth::id())->find($id);
        if ($notification) {
            $notification->update(['read_at' => now()]);
        }
        return response()->json(['success' => true]);
    }
}
