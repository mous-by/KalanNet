<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\Banque;
use App\Models\Classe;
use App\Models\Paiement;
use App\Models\Encaissement;
use App\Models\Decaissement;
use App\Models\Ecole;
use App\Models\EcheancePaiement;
use App\Models\Eleve;
use App\Models\FraisScolaire;
use App\Models\LignePaiementEleve;
use App\Models\ParentModel;
use App\Models\Planification;
use App\Models\PlanPaiement;
use App\Models\ReductionPaiementConfig;
use App\Models\Retrait;
use App\Models\Trimestre;
use App\Models\AnneeScolaire;
use App\Models\Versement;
use App\Services\Paiements\EcheanceService;
use App\Services\Paiements\PaiementEleveReportService;
use App\Services\Paiements\PaiementEleveService;
use App\Services\Paiements\ReferencePaiementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function __construct(
        private readonly PaiementEleveService $paiementService,
        private readonly PaiementEleveReportService $reportService,
        private readonly ReferencePaiementService $referenceService,
    ) {
    }

    public function index()
    {
        $idEcole = session('idEcole');
        
        $caisse = Caisse::where('id_ecole', $idEcole)->where('status', 1)->first();
        
        $recentPaiements = Paiement::with(['eleve', 'classe'])
            ->where('idEcole', $idEcole)
            ->orderBy('date_paiement', 'desc')
            ->limit(10)
            ->get();

        $totalRecettes = Paiement::where('idEcole', $idEcole)
            ->where('statut', 'valide')
            ->sum(DB::raw('COALESCE(montant_paye, montant)'));
        $totalDepenses = Decaissement::whereHas('caisse', function($q) use ($idEcole) {
                $q->where('id_ecole', $idEcole);
            })->where('valide', 1)->sum('montant_decaissement');

        return view('finances.index', compact('caisse', 'recentPaiements', 'totalRecettes', 'totalDepenses'));
    }

    public function listePaiements(Request $request)
    {
        $idEcole = session('idEcole');
        $this->ensurePermission('paiements_apercu');

        $storedFilters = session('finances_paiements_filters', []);
        $requestFilters = $request->only(['id_classe', 'id_annee', 'id_trimestre', 'date_paiement', 'type_planification']);

        if ($request->isMethod('post')) {
            $filters = $requestFilters;
            session(['finances_paiements_filters' => $filters]);
        } else {
            $filters = $storedFilters;
        }

        $paiements = Paiement::with(['eleve', 'classe', 'echeance'])
            ->where('idEcole', $idEcole)
            ->orderBy('date_paiement', 'desc')
            ->paginate(20);

        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $trimestres = Trimestre::withoutGlobalScopes()
            ->where(function ($query) use ($idEcole) {
                $query->where('id_ecole', $idEcole)->orWhereNull('id_ecole');
            })
            ->orderBy('id_trimestre')
            ->get();
        $caisse = Caisse::where('id_ecole', $idEcole)->where('status', 1)->first();
        $ecole = Ecole::find($idEcole);
        $paymentRows = collect();

        if (!empty($filters['id_classe']) && !empty($filters['id_annee'])) {
            Classe::where('idEcole', $idEcole)->findOrFail($filters['id_classe']);
            $paymentRows = $this->legacyPaymentRows(
                (int) $filters['id_classe'],
                (int) $filters['id_annee'],
                (string) ($filters['type_planification'] ?? '')
            );
        }
        $newRef = $this->nextPaiementReference();

        return view('finances.paiements', compact(
            'paiements',
            'classes',
            'annees',
            'trimestres',
            'caisse',
            'ecole',
            'filters',
            'paymentRows',
            'newRef'
        ));
    }

    public function filterPaiements(Request $request)
    {
        $this->ensurePermission('paiements_apercu');

        $filters = $request->only(['id_classe', 'id_annee', 'id_trimestre', 'date_paiement', 'type_planification']);
        session(['finances_paiements_filters' => $filters]);

        return redirect()->route('finances.paiements');
    }

    public function storePaiement(Request $request)
    {
        $this->ensurePermission('paiements_faire');
        $data = $request->validate([
            'echeance_id' => 'required|exists:echeances_paiement,id',
            'date_paiement' => 'required|date',
            'motif' => 'nullable|string|max:100',
            'montant_paye' => 'required|numeric|min:1',
            'mode_reglement' => 'required|string|max:40',
            'parent_id' => 'nullable|exists:parents,id_parent',
            'nom_payeur' => 'nullable|string|max:100',
            'telephone' => 'nullable|string|max:50',
        ]);

        try {
            $paiement = $this->paiementService->encaisser($data);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return redirect()->route('finances.paiements')
                ->withInput()
                ->with('error', 'Impossible d’enregistrer le paiement. Veuillez réessayer.');
        }

        return redirect()
            ->route('finances.paiements')
            ->with('success', "Règlement enregistré. Reçu N° {$paiement->numero_recu} prêt à imprimer.");
    }

    public function listeLegacyPlanifications(Request $request)
    {
        $idEcole = (int) session('idEcole');
        $this->ensurePermission('finances_planifications_apercu');

        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        $filters = $request->only(['id_classe', 'id_annee']);
        $planifications = collect();

        if (!empty($filters['id_classe']) && !empty($filters['id_annee'])) {
            Classe::where('idEcole', $idEcole)->findOrFail($filters['id_classe']);
            $planifications = Planification::where('id_classe', $filters['id_classe'])
                ->where('id_annee', $filters['id_annee'])
                ->orderBy('motif')
                ->get();
        }

        return view('finances.planifications.index', compact('classes', 'annees', 'filters', 'planifications'));
    }

    public function createLegacyPlanification()
    {
        $idEcole = (int) session('idEcole');
        $this->ensureAnyPermission(['finances_planifications_creation', 'paiements_faire']);

        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::whereDate('date_debut', '<=', now())
            ->whereDate('date_fin', '>=', now())
            ->orderByDesc('id_anneeScolaire')
            ->get();
        if ($annees->isEmpty()) {
            $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        }

        return view('finances.planifications.create', compact('classes', 'annees'));
    }

    public function storeLegacyPlanification(Request $request)
    {
        $this->ensureAnyPermission(['finances_planifications_creation', 'paiements_faire']);

        $data = $request->validate([
            'id_classes' => 'required|array|min:1',
            'id_classes.*' => 'required|integer|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'motif' => 'required|array|min:1',
            'motif.*' => 'required|string|max:255',
            'date_debut' => 'required|array|min:1',
            'date_debut.*' => 'required|date',
            'date_fin' => 'required|array|min:1',
            'date_fin.*' => 'required|date',
            'montant' => 'required|array|min:1',
            'montant.*' => 'required|numeric|min:1',
        ]);

        $idEcole = (int) session('idEcole');
        $classeIds = collect($data['id_classes'])->map(fn ($id) => (int) $id)->unique()->values();
        $allowedClasseIds = Classe::where('idEcole', $idEcole)
            ->whereIn('id_classe', $classeIds)
            ->pluck('id_classe')
            ->map(fn ($id) => (int) $id);

        if ($allowedClasseIds->count() !== $classeIds->count()) {
            throw ValidationException::withMessages([
                'id_classes' => 'Une ou plusieurs classes sélectionnées n’appartiennent pas à votre école.',
            ]);
        }

        $errors = [];
        $created = 0;

        try {
            DB::transaction(function () use ($data, $allowedClasseIds, &$errors, &$created) {
                $classNames = Classe::whereIn('id_classe', $allowedClasseIds)
                    ->pluck('nom_classe', 'id_classe');

                foreach ($allowedClasseIds as $classeId) {
                    $existing = Planification::where('id_classe', $classeId)
                        ->where('id_annee', $data['id_annee'])
                        ->pluck('motif')
                        ->map(fn ($motif) => strtolower(trim((string) $motif)))
                        ->all();
                    $requiredTypes = ['annuelle', 'mensuelle', 'trimestrielle'];

                    if (empty(array_diff($requiredTypes, $existing))) {
                        $errors[] = "La classe {$classNames[$classeId]} est déjà planifiée avec les trois types.";
                        continue;
                    }

                    foreach ($data['motif'] as $index => $motif) {
                        $motif = strtolower(trim((string) $motif));
                        $dateDebut = $data['date_debut'][$index] ?? null;
                        $dateFin = $data['date_fin'][$index] ?? null;
                        $montant = $data['montant'][$index] ?? null;

                        if (strtotime((string) $dateFin) <= strtotime((string) $dateDebut)) {
                            $errors[] = 'La date de fin doit être strictement postérieure à la date de début à la ligne ' . ($index + 1) . '.';
                            continue;
                        }
                        if (in_array($motif, $existing, true)) {
                            $errors[] = "Le type de planification '{$motif}' existe déjà pour {$classNames[$classeId]} à la ligne " . ($index + 1) . '.';
                            continue;
                        }

                        Planification::create([
                            'motif' => $motif,
                            'id_classe' => $classeId,
                            'id_annee' => $data['id_annee'],
                            'date_debut' => $dateDebut,
                            'date_fin' => $dateFin,
                            'montant_planification' => $montant,
                        ]);

                        $existing[] = $motif;
                        $created++;
                    }
                }
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return redirect()->route('finances.planifications.create')
                ->withInput()
                ->with('error', 'Impossible d’enregistrer la planification du paiement.');
        }

        $message = $created > 0 ? "{$created} planification(s) insérée(s) avec succès." : 'Aucune planification insérée.';
        return redirect()->route('finances.planifications.create')
            ->with($created > 0 ? 'success' : 'error', $message)
            ->with('planification_errors', $errors);
    }

    public function updateLegacyPlanification(Request $request, $id)
    {
        $this->ensureAnyPermission(['finances_planifications_modification', 'paiements_faire']);
        $idEcole = (int) session('idEcole');

        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'motif' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'montant_planification' => 'required|numeric|min:1',
            'affecter_aux_eleves' => 'nullable|boolean',
        ]);

        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);

        try {
            DB::transaction(function () use ($data, $id, $idEcole, $request) {
                $planification = Planification::where('id_classe', $data['id_classe'])
                    ->where('id_annee', $data['id_annee'])
                    ->findOrFail($id);

                $planification->update([
                    'motif' => trim($data['motif']),
                    'date_debut' => $data['date_debut'],
                    'date_fin' => $data['date_fin'],
                    'montant_planification' => $data['montant_planification'],
                ]);

            });
        } catch (\Throwable) {
            return redirect()->route('finances.paiements')
                ->withInput()
                ->with('error', 'Impossible de modifier cette planification.');
        }

        return redirect()
            ->route('finances.paiements')
            ->with('success', 'Planification du paiement modifiée.');
    }

    public function deleteLegacyPlanification($id)
    {
        $this->ensureAnyPermission(['finances_planifications_supprimer', 'paiements_faire']);

        $planification = Planification::findOrFail($id);
        Classe::where('idEcole', session('idEcole'))->findOrFail($planification->id_classe);

        $linked = DB::table('ligne_inscription')->where('id_planification', $planification->id_planification)->count();
        if ($linked > 0) {
            return redirect()->route('finances.planifications')
                ->with('error', 'Impossible de supprimer : des élèves sont liés à cette planification.');
        }

        $planification->delete();

        return redirect()->route('finances.planifications')->with('success', 'Suppression réussie.');
    }

    public function storePaiementsGroupes(Request $request)
    {
        $this->ensurePermission('paiements_faire');

        $data = $request->validate([
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
            'id_trimestre' => 'required|exists:trimestre,id_trimestre',
            'date_paiement' => 'required|date',
            'type_planification' => 'nullable|string|max:40',
            'alert' => 'required|array|min:1',
            'alert.*' => 'integer',
            'id_eleve' => 'required|array',
            'id_planification' => 'required|array',
            'motif' => 'required|array',
            'montant_recu' => 'required|array',
            'parent_id' => 'nullable|array',
            'autre_personne_nom' => 'nullable|array',
            'autre_personne_telephone' => 'nullable|array',
        ]);

        $idEcole = (int) session('idEcole');
        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);
        $selected = collect($data['alert'])->map(fn ($id) => (int) $id)->flip();
        $createdIds = [];
        $errors = [];

        foreach ($data['id_eleve'] as $index => $eleveId) {
            $eleveId = (int) $eleveId;
            if (!$selected->has($eleveId)) {
                continue;
            }

            try {
                $paiement = DB::transaction(function () use ($data, $index, $eleveId, $idEcole) {
                    $eleve = Eleve::where('id_ecole', $idEcole)
                        ->where('id_classe', $data['id_classe'])
                        ->where('id_annee', $data['id_annee'])
                        ->where('etat_dossier', 0)
                        ->findOrFail($eleveId);

                    $planificationId = (int) ($data['id_planification'][$index] ?? 0);
                    $planification = Planification::where('id_classe', $data['id_classe'])
                        ->where('id_annee', $data['id_annee'])
                        ->findOrFail($planificationId);

                    $montantRecu = (float) ($data['montant_recu'][$index] ?? 0);
                    $reste = $this->legacyRemainingForPlan($eleveId, $planification, (int) $data['id_annee'], $data['date_paiement']);
                    if ($montantRecu <= 0) {
                        throw ValidationException::withMessages(['montant_recu' => 'Le montant reçu doit être supérieur à zéro.']);
                    }
                    if ($montantRecu > $reste) {
                        throw ValidationException::withMessages(['montant_recu' => 'Le montant reçu dépasse le reste à payer.']);
                    }

                    $payer = $this->resolveLegacyPayer(
                        $eleve,
                        $data['parent_id'][$index] ?? null,
                        $data['autre_personne_nom'][$index] ?? null,
                        $data['autre_personne_telephone'][$index] ?? null
                    );

                    $caisse = Caisse::where('id_ecole', $idEcole)
                        ->where('status', 1)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $motifInput = trim((string) ($data['motif'][$index] ?? ''));
                    $paiement = Paiement::create([
                        'montant' => $montantRecu,
                        'montant_paye' => $montantRecu,
                        'date_paiement' => $data['date_paiement'],
                        'mode_reglement' => 'especes',
                        'statut' => 'valide',
                        'motif' => $motifInput !== '' ? $motifInput : $planification->motif,
                        'id_classe' => $data['id_classe'],
                        'id_annee' => $data['id_annee'],
                        'id_trimestre' => $data['id_trimestre'],
                        'reference' => $this->referenceService->nextReference(),
                        'idEcole' => $idEcole,
                        'id_eleve' => $eleve->id_eleve,
                        'parent' => $payer['parent'],
                        'nom_payeur' => $payer['nom_payeur'],
                        'telephone' => $payer['telephone'],
                        'id_utilisateur' => Auth::id(),
                        'id_caisse' => $caisse->id_caisse,
                        'numero_recu' => $this->referenceService->nextReceiptNumber($idEcole),
                        'id_planification' => $planification->id_planification,
                    ]);

                    $encaissement = Encaissement::create([
                        'paiement_id' => $paiement->id_paiement,
                        'type_operation' => 'Paiement eleve',
                        'date_encaissement' => $data['date_paiement'],
                        'motif_encaissement' => $paiement->motif,
                        'montant_encaissement' => $montantRecu,
                        'statut' => 'valide',
                        'id_annee_scolaire' => $data['id_annee'],
                        'id_caisse' => $caisse->id_caisse,
                        'idUtilisateur' => Auth::id(),
                    ]);

                    $paiement->encaissement_id = $encaissement->id_encaissement;
                    $paiement->save();

                    LignePaiementEleve::create([
                        'id_classe' => $data['id_classe'],
                        'id_annee' => $data['id_annee'],
                        'id_paiement' => $paiement->id_paiement,
                        'id_eleve' => $eleve->id_eleve,
                        'id_trimestre' => $data['id_trimestre'],
                        'idEcole' => $idEcole,
                    ]);

                    $caisse->montant_net = (float) $caisse->montant_net + $montantRecu;
                    $caisse->save();

                    return $paiement;
                });

                $createdIds[] = $paiement->id_paiement;
            } catch (ValidationException $exception) {
                $errors[] = "Élève #{$eleveId}: " . collect($exception->errors())->flatten()->first();
            } catch (\Throwable) {
                $errors[] = "Élève #{$eleveId}: paiement non enregistré.";
            }
        }

        $message = count($createdIds) . ' paiement(s) enregistré(s).';
        if ($errors) {
            $message .= ' ' . count($errors) . ' ligne(s) ignorée(s).';
        }

        return redirect()
            ->route('finances.paiements')
            ->with($createdIds ? 'success' : 'error', $message)
            ->with('payment_errors', $errors)
            ->with('created_payment_ids', $createdIds);
    }

    public function updatePaiement(Request $request, $id)
    {
        $this->ensurePermission('paiements_faire');
        $idEcole = session('idEcole');
        $paiement = Paiement::where('idEcole', $idEcole)->findOrFail($id);

        $data = $request->validate([
            'reference' => 'required|string|max:100',
            'date_paiement' => 'required|date',
            'motif' => 'required|string|max:50',
            'id_classe' => 'required|exists:classe,id_classe',
            'id_annee' => 'required|exists:anneescolaire,id_anneeScolaire',
        ]);

        Classe::where('idEcole', $idEcole)->findOrFail($data['id_classe']);

        $paiement->update([
            'reference' => trim($data['reference']),
            'date_paiement' => $data['date_paiement'],
            'motif' => trim($data['motif']),
            'id_classe' => $data['id_classe'],
            'id_annee' => $data['id_annee'],
        ]);

        return redirect()
            ->route('finances.paiements')
            ->with('success', "Paiement #{$paiement->numero_recu} modifié avec succès.");
    }

    public function contexteEleve(Request $request, $id)
    {
        $this->ensurePermission('paiements_apercu');
        $data = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:anneescolaire,id_anneeScolaire',
        ]);

        $context = $this->paiementService->studentContext((int) $id, $data['annee_scolaire_id'] ?? null);
        $plan = $context['plan'];

        return response()->json([
            'eleve' => [
                'id' => $context['eleve']->id_eleve,
                'nom' => trim($context['eleve']->nom_eleve . ' ' . $context['eleve']->prenom_eleve),
                'statut_paiement' => $context['eleve']->statut_paiement ?? 'normal',
            ],
            'ecole' => [
                'id' => $context['ecole']?->idEcole,
                'nom' => $context['ecole']?->nomEcole,
                'type' => $context['type_ecole'],
            ],
            'classe' => [
                'id' => $context['classe']?->id_classe,
                'nom' => $context['classe']?->nom_classe,
            ],
            'frais' => $context['frais']->map(fn ($frais) => [
                'id' => $frais->id,
                'type_frais' => $frais->type_frais,
                'montant' => (float) $frais->montant,
                'obligatoire' => (bool) $frais->obligatoire,
            ]),
            'plan' => $plan ? [
                'id' => $plan->id,
                'mode_paiement' => $plan->mode_paiement,
                'montant_total' => (float) $plan->montant_total,
                'reduction' => (float) $plan->reduction,
                'montant_final' => (float) $plan->montant_final,
                'payeur_type' => $plan->payeur_type,
                'payeur_libelle' => $plan->payeur_libelle,
                'echeances' => $plan->echeances->map(fn ($echeance) => [
                    'id' => $echeance->id,
                    'libelle' => $echeance->libelle,
                    'montant_prevu' => (float) $echeance->montant_prevu,
                    'reste' => $this->paiementService->remainingForEcheance($echeance),
                    'date_limite' => $echeance->date_limite->toDateString(),
                    'statut' => $echeance->statut,
                ]),
            ] : null,
            'resume' => $context['resume'],
        ]);
    }

    public function storeFraisScolaire(Request $request)
    {
        $this->ensurePermission('paiements_faire');
        $idEcole = session('idEcole');

        $data = $request->validate([
            'classe_id' => 'nullable|exists:classe,id_classe',
            'annee_scolaire_id' => 'required|exists:anneescolaire,id_anneeScolaire',
            'type_frais' => 'required|string|max:80',
            'montant' => 'required|numeric|min:0',
            'obligatoire' => 'nullable|boolean',
        ]);

        $ecole = Ecole::findOrFail($idEcole);
        $isPublicSchool = str_contains(strtolower((string) $ecole->typeEcole), 'public');

        if ($isPublicSchool) {
            if (!in_array($data['type_frais'], PaiementEleveService::PUBLIC_FEE_TYPES, true)) {
                return redirect()->route('finances.paiements')
                    ->withInput()
                    ->with('error', 'Cette cotisation n’est pas autorisée pour une école publique.');
            }
            $data['classe_id'] = null;
        } else {
            if (empty($data['classe_id'])) {
                return redirect()->route('finances.paiements')
                    ->withInput()
                    ->with('error', 'La classe est obligatoire pour configurer un frais d’école privée.');
            }
            Classe::where('idEcole', $idEcole)->findOrFail($data['classe_id']);
        }

        FraisScolaire::updateOrCreate(
            [
                'ecole_id' => $idEcole,
                'classe_id' => $data['classe_id'] ?? null,
                'annee_scolaire_id' => $data['annee_scolaire_id'],
                'type_frais' => trim($data['type_frais']),
            ],
            [
                'montant' => $data['montant'],
                'obligatoire' => $request->boolean('obligatoire', true),
                'actif' => true,
            ]
        );

        return redirect()->route('finances.paiements')->with('success', 'Frais scolaire enregistré.');
    }

    public function storeReductionConfig(Request $request)
    {
        $this->ensurePermission('paiements_faire');
        $idEcole = session('idEcole');
        $ecole = Ecole::findOrFail($idEcole);
        if (str_contains(strtolower((string) $ecole->typeEcole), 'public')) {
            return redirect()->route('finances.paiements')
                ->with('error', 'Les réductions de frais scolaires ne concernent pas les écoles publiques.');
        }

        $data = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:anneescolaire,id_anneeScolaire',
            'statut_paiement' => 'required|string|max:40',
            'type_reduction' => 'required|string|max:40',
            'valeur' => 'required|numeric|min:0',
            'payeur_libelle' => 'nullable|string|max:120',
        ]);

        ReductionPaiementConfig::updateOrCreate(
            [
                'ecole_id' => $idEcole,
                'annee_scolaire_id' => $data['annee_scolaire_id'] ?? null,
                'statut_paiement' => $data['statut_paiement'],
            ],
            [
                'type_reduction' => $data['type_reduction'],
                'valeur' => $data['valeur'],
                'payeur_libelle' => $data['payeur_libelle'] ?? null,
                'actif' => true,
            ]
        );

        return redirect()->route('finances.paiements')->with('success', 'Règle de réduction enregistrée.');
    }

    public function generatePlanPaiement(Request $request)
    {
        $this->ensurePermission('paiements_faire');
        $data = $request->validate([
            'eleve_id' => 'required|exists:eleve,id_eleve',
            'annee_scolaire_id' => 'required|exists:anneescolaire,id_anneeScolaire',
            'mode_paiement' => 'required|in:' . implode(',', EcheanceService::MODES),
            'echeances' => 'nullable|array',
            'echeances.*.libelle' => 'required_with:echeances|string|max:120',
            'echeances.*.montant_prevu' => 'required_with:echeances|numeric|min:0',
            'echeances.*.date_limite' => 'required_with:echeances|date',
        ]);

        try {
            $plan = $this->paiementService->createOrUpdatePlan(
                (int) $data['eleve_id'],
                (int) $data['annee_scolaire_id'],
                $data['mode_paiement'],
                $data['echeances'] ?? null,
            );
        } catch (\Throwable) {
            return redirect()->route('finances.paiements')
                ->withInput()
                ->with('error', 'Impossible de générer les échéances. Vérifiez les frais configurés.');
        }

        return redirect()->route('finances.paiements')
            ->with('success', "Plan de paiement généré pour {$plan->eleve->nom_eleve} {$plan->eleve->prenom_eleve}.");
    }

    public function cancelPaiement(Request $request, $id)
    {
        $this->ensurePermission('paiements_faire');
        $data = $request->validate([
            'motif_annulation' => 'required|string|max:255',
        ]);

        $paiement = Paiement::where('idEcole', session('idEcole'))->findOrFail($id);

        try {
            $this->paiementService->cancelPayment($paiement, $data['motif_annulation']);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            return redirect()->route('finances.paiements')->with('error', 'Impossible d’annuler ce paiement.');
        }

        return redirect()->route('finances.paiements')->with('success', 'Paiement annulé comptablement.');
    }

    public function showCaisse()
    {
        $idEcole = session('idEcole');
        $caisse = Caisse::where('id_ecole', $idEcole)->first();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();
        
        if (!$caisse) {
            return view('finances.caisse', ['caisse' => null, 'mouvements' => [], 'annees' => $annees]);
        }

        // Combine encaissements and decaissements for a ledger view
        $encaissements = Encaissement::where('id_caisse', $caisse->id_caisse)->get()->map(function($item) {
            $item->type = 'RECETTE';
            $item->date = $item->date_encaissement;
            $item->montant = $item->montant_encaissement;
            $item->motif = $item->motif_encaissement;
            return $item;
        });

        $decaissements = Decaissement::where('id_caisse', $caisse->id_caisse)->get()->map(function($item) {
            $item->type = 'DEPENSE';
            $item->date = $item->date_decaissement;
            $item->montant = $item->montant_decaissement;
            $item->motif = $item->motif_decaissement;
            return $item;
        });

        $mouvements = $encaissements->concat($decaissements)->sortByDesc('date');

        return view('finances.caisse', compact('caisse', 'mouvements', 'annees'));
    }

    public function storeCaisse(Request $request)
    {
        $idEcole = session('idEcole');

        $request->validate([
            'libelle' => 'required|string|max:255',
            'montant_initial' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
        ]);

        if (Caisse::where('id_ecole', $idEcole)->exists()) {
            return redirect()->route('finances.caisse')->with('error', 'Une caisse existe déjà pour cet établissement.');
        }

        $ecole = Ecole::find($idEcole);
        $prefix = 'CS';
        if ($ecole) {
            $prefix .= '-' . substr(preg_replace('/\s+/', '', strtoupper($ecole->nomEcole)), 0, 2);
        }

        Caisse::create([
            'reference' => $prefix . '-' . random_int(1000, 9999),
            'libelle' => $request->libelle,
            'montant_initial' => $request->montant_initial,
            'montant_net' => $request->montant_initial,
            'id_ecole' => $idEcole,
            'status' => $request->status,
            'created_at' => now()->toDateString(),
        ]);

        return redirect()->route('finances.caisse')->with('success', 'Caisse ajoutée avec succès.');
    }

    public function storeEncaissement(Request $request)
    {
        $data = $request->validate([
            'id_caisse' => 'required|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|exists:anneescolaire,id_anneeScolaire',
            'type_operation' => 'required|string|max:255',
            'date_encaissement' => 'required|date',
            'motif_encaissement' => 'required|string|max:255',
            'montant_encaissement' => 'required|numeric|min:1',
        ]);

        $caisse = $this->getOwnedCaisse($data['id_caisse']);

        DB::transaction(function () use ($data, $caisse) {
            Encaissement::create([
                'type_operation' => $data['type_operation'],
                'date_encaissement' => $data['date_encaissement'],
                'motif_encaissement' => $data['motif_encaissement'],
                'montant_encaissement' => $data['montant_encaissement'],
                'id_annee_scolaire' => $data['id_annee_scolaire'],
                'id_caisse' => $caisse->id_caisse,
                'idUtilisateur' => Auth::id(),
            ]);

            $caisse->increment('montant_net', $data['montant_encaissement']);
        });

        return redirect()->route('finances.caisse')->with('success', 'Encaissement ajouté avec succès.');
    }

    public function storeDecaissement(Request $request)
    {
        $data = $request->validate([
            'id_caisse' => 'required|exists:caisse,id_caisse',
            'id_annee_scolaire' => 'required|exists:anneescolaire,id_anneeScolaire',
            'date_decaissement' => 'required|date',
            'motif_decaissement' => 'required|string|max:255',
            'montant_decaissement' => 'required|numeric|min:1',
        ]);

        $caisse = $this->getOwnedCaisse($data['id_caisse']);

        if ((float) $caisse->montant_net < (float) $data['montant_decaissement']) {
            return redirect()->route('finances.caisse')->with('error', 'Solde insuffisant pour effectuer cette sortie.');
        }

        DB::transaction(function () use ($data, $caisse) {
            Decaissement::create([
                'date_decaissement' => $data['date_decaissement'],
                'motif_decaissement' => $data['motif_decaissement'],
                'montant_decaissement' => $data['montant_decaissement'],
                'id_annee_scolaire' => $data['id_annee_scolaire'],
                'id_caisse' => $caisse->id_caisse,
                'idUtilisateur' => Auth::id(),
                'valide' => 1,
            ]);

            $caisse->decrement('montant_net', $data['montant_decaissement']);
        });

        return redirect()->route('finances.caisse')->with('success', 'Décaissement ajouté avec succès.');
    }

    public function banques()
    {
        $this->ensurePermission('banques_apercu');
        $idEcole = session('idEcole');
        $banques = Banque::where('id_ecole', $idEcole)->orderByDesc('id_banques')->get();
        $caisse = Caisse::where('id_ecole', $idEcole)->where('status', 1)->first();

        return view('finances.banques.index', compact('banques', 'caisse'));
    }

    public function storeBanque(Request $request)
    {
        $this->ensurePermission('banques_création');
        $data = $request->validate([
            'numero_compte' => 'required|string|max:255',
            'nom_banque' => 'required|string|max:255',
            'montant_initial' => 'required|numeric|min:0',
        ]);

        Banque::create([
            'numero_compte' => $data['numero_compte'],
            'nom_banque' => $data['nom_banque'],
            'solde' => $data['montant_initial'],
            'id_ecole' => session('idEcole'),
            'date_creation' => now()->toDateString(),
        ]);

        return redirect()->route('finances.banques')->with('success', 'Banque ajoutée avec succès.');
    }

    public function updateBanque(Request $request, $id)
    {
        $this->ensurePermission('banques_modification');
        $banque = Banque::where('id_ecole', session('idEcole'))->findOrFail($id);
        $data = $request->validate([
            'numero_compte' => 'required|string|max:255',
            'nom_banque' => 'required|string|max:255',
            'solde' => 'required|numeric|min:0',
        ]);
        $banque->update($data + ['updated_at' => now()->toDateString()]);

        return redirect()->route('finances.banques')->with('success', 'Banque modifiée avec succès.');
    }

    public function versements()
    {
        $this->ensurePermission('versements_apercu');
        $idEcole = session('idEcole');
        $versements = Versement::with(['banque', 'utilisateur'])
            ->whereHas('banque', fn ($q) => $q->where('id_ecole', $idEcole))
            ->orderByDesc('date_versement')
            ->get();
        $banques = Banque::where('id_ecole', $idEcole)->orderBy('nom_banque')->get();
        $caisse = Caisse::where('id_ecole', $idEcole)->where('status', 1)->first();

        return view('finances.banques.versements', compact('versements', 'banques', 'caisse'));
    }

    public function storeVersement(Request $request)
    {
        $this->ensurePermission('versements_création');
        $data = $request->validate([
            'id_banque' => 'required|exists:banques,id_banques',
            'montant_versement' => 'required|numeric|min:1',
            'motif_versement' => 'required|string|max:255',
        ]);

        $idEcole = session('idEcole');
        $annee = $this->currentSchoolYear();

        try {
            DB::transaction(function () use ($data, $idEcole, $annee) {
                $caisse = Caisse::where('id_ecole', $idEcole)->where('status', 1)->lockForUpdate()->firstOrFail();
                $banque = Banque::where('id_ecole', $idEcole)->lockForUpdate()->findOrFail($data['id_banque']);
                $montant = (float) $data['montant_versement'];

                if ((float) $caisse->montant_net < $montant) {
                    throw ValidationException::withMessages(['montant_versement' => 'Montant insuffisant dans la caisse.']);
                }

                Versement::create([
                    'date_versement' => now()->toDateString(),
                    'motif_versement' => $data['motif_versement'],
                    'montant_versement' => $montant,
                    'id_annee_scolaire' => $annee->id_anneeScolaire,
                    'id_banque' => $banque->id_banques,
                    'idUtilisateur' => Auth::id(),
                ]);

                $banque->solde = (float) $banque->solde + $montant;
                $banque->save();
                $caisse->montant_net = (float) $caisse->montant_net - $montant;
                $caisse->save();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            return redirect()->route('finances.versements')->with('error', 'Erreur lors du versement.');
        }

        return redirect()->route('finances.versements')->with('success', 'Versement effectué avec succès.');
    }

    public function retraits()
    {
        $this->ensurePermission('retraits_apercu');
        $idEcole = session('idEcole');
        $retraits = Retrait::with(['banque', 'utilisateur'])
            ->whereHas('banque', fn ($q) => $q->where('id_ecole', $idEcole))
            ->orderByDesc('date_retrait')
            ->get();
        $banques = Banque::where('id_ecole', $idEcole)->orderBy('nom_banque')->get();

        return view('finances.banques.retraits', compact('retraits', 'banques'));
    }

    public function storeRetrait(Request $request)
    {
        $this->ensurePermission('retraits_création');
        $data = $request->validate([
            'id_banque' => 'required|exists:banques,id_banques',
            'date_retrait' => 'required|date',
            'montant_retrait' => 'required|numeric|min:1',
            'motif_retrait' => 'required|string|max:255',
        ]);

        $idEcole = session('idEcole');
        $annee = $this->currentSchoolYear();
        $isAdmin = Auth::user()?->droit === 'Admin';

        try {
            DB::transaction(function () use ($data, $idEcole, $annee, $isAdmin) {
                $banque = Banque::where('id_ecole', $idEcole)->lockForUpdate()->findOrFail($data['id_banque']);
                $montant = (float) $data['montant_retrait'];

                if ($isAdmin && (float) $banque->solde < $montant) {
                    throw ValidationException::withMessages(['montant_retrait' => 'Solde insuffisant dans la banque.']);
                }

                Retrait::create([
                    'id_banque' => $banque->id_banques,
                    'date_retrait' => $data['date_retrait'],
                    'montant_retrait' => $montant,
                    'motif_retrait' => $data['motif_retrait'],
                    'id_annee_scolaire' => $annee->id_anneeScolaire,
                    'idUtilisateur' => Auth::id(),
                    'created_at' => now(),
                    'valide' => $isAdmin,
                ]);

                if ($isAdmin) {
                    $banque->solde = (float) $banque->solde - $montant;
                    $banque->save();
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            return redirect()->route('finances.retraits')->with('error', 'Erreur lors du retrait.');
        }

        return redirect()->route('finances.retraits')->with('success', $isAdmin ? 'Retrait effectué avec succès.' : 'Retrait soumis en attente de validation.');
    }

    public function validateRetrait($id)
    {
        $this->ensurePermission('retraits_modification');
        $idEcole = session('idEcole');

        try {
            DB::transaction(function () use ($id, $idEcole) {
                $retrait = Retrait::with('banque')
                    ->whereHas('banque', fn ($q) => $q->where('id_ecole', $idEcole))
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($retrait->valide) {
                    return;
                }

                $banque = Banque::whereKey($retrait->id_banque)->lockForUpdate()->firstOrFail();
                if ((float) $banque->solde < (float) $retrait->montant_retrait) {
                    throw ValidationException::withMessages(['retrait' => 'Solde insuffisant dans la banque.']);
                }

                $banque->solde = (float) $banque->solde - (float) $retrait->montant_retrait;
                $banque->save();
                $retrait->valide = 1;
                $retrait->save();
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            return redirect()->route('finances.retraits')->with('error', 'Impossible de valider ce retrait.');
        }

        return redirect()->route('finances.retraits')->with('success', 'Retrait validé avec succès.');
    }

    public function downloadRecu($id)
    {
        $idEcole = session('idEcole');
        $paiement = Paiement::with(['eleve', 'classe', 'echeance'])
            ->where('idEcole', $idEcole)
            ->findOrFail($id);
        $this->authorizePaymentReceipt($paiement);

        return $this->reportService
            ->receiptPdf($paiement)
            ->download('Recu_Paiement_' . $paiement->numero_recu . '.pdf');
    }

    public function downloadRecuThermique($id)
    {
        $idEcole = session('idEcole');
        $paiement = Paiement::with(['eleve', 'classe'])
            ->where('idEcole', $idEcole)
            ->findOrFail($id);
        $this->authorizePaymentReceipt($paiement);

        return $this->reportService
            ->thermalReceiptPdf($paiement)
            ->download('Recu_Thermique_' . $paiement->numero_recu . '.pdf');
    }

    public function historiquePaiements(Request $request)
    {
        $this->ensurePermission('historique_paiement_apercu');
        $idEcole = session('idEcole');

        $storedFilters = session('finances_historique_paiements_filters', []);
        if ($request->isMethod('post')) {
            $storedFilters = $request->only(['classe_id', 'annee_scolaire_id', 'statut']);
            session(['finances_historique_paiements_filters' => $storedFilters]);
        }

        $filters = $storedFilters;
        $paiements = $this->reportService->legacyHistoryQuery($idEcole, $filters)
            ->paginate(25);

        $classes = Classe::where('idEcole', $idEcole)->orderBy('nom_classe')->get();
        $annees = AnneeScolaire::orderByDesc('id_anneeScolaire')->get();

        return view('finances.paiements.historique', compact('paiements', 'classes', 'annees', 'filters'));
    }

    public function exportHistoriquePaiements(Request $request)
    {
        $this->ensurePermission('historique_paiement_export');
        $idEcole = session('idEcole');
        $format = strtolower($request->query('format', 'xlsx'));
        $filters = session('finances_historique_paiements_filters', []);
        $paiements = $this->reportService->legacyHistoryQuery($idEcole, $filters)->get();

        if ($format === 'pdf') {
            return $this->reportService->legacyHistoryPdf($paiements)->download('historique_paiements_eleves.pdf');
        }

        if ($format === 'csv') {
            return $this->reportService->legacyCsv($paiements);
        }

        return $this->reportService->legacyXlsx($paiements);
    }

    private function legacyPaymentRows(int $classeId, int $anneeId, string $typePlanification = '')
    {
        $idEcole = (int) session('idEcole');
        $planifications = Planification::where('id_classe', $classeId)
            ->where('id_annee', $anneeId)
            ->get()
            ->keyBy('id_planification');

        $students = Eleve::with('parents')
            ->where('id_ecole', $idEcole)
            ->where('id_classe', $classeId)
            ->where('id_annee', $anneeId)
            ->where('etat_dossier', 0)
            ->orderBy('nom_eleve')
            ->orderBy('prenom_eleve')
            ->get();

        $inscriptions = DB::table('ligne_inscription')
            ->where('id_classe', $classeId)
            ->where('id_annee', $anneeId)
            ->whereIn('id_eleve', $students->pluck('id_eleve'))
            ->get()
            ->keyBy('id_eleve');

        return $students->map(function ($student) use ($inscriptions, $planifications, $typePlanification, $anneeId) {
            $inscription = $inscriptions->get($student->id_eleve);
            $planification = $inscription ? $planifications->get($inscription->id_planification) : null;
            if (!$planification || !$this->legacyPlanMatchesType($planification->motif, $typePlanification)) {
                return null;
            }

            $total = (float) $planification->montant_planification;
            $paid = $this->legacyPaidForPlan((int) $student->id_eleve, $planification, $anneeId);
            $remaining = max(0, $total - $paid);
            if ($remaining <= 0) {
                return null;
            }

            return (object) [
                'eleve' => $student,
                'planification' => $planification,
                'montant_total' => $total,
                'montant_deja_paye' => $paid,
                'reste_a_payer' => $remaining,
                'parents' => $student->parents,
                'row_class' => $this->legacyPaymentDelayClass($planification),
            ];
        })->filter()->values();
    }

    private function legacyRemainingForPlan(int $eleveId, Planification $planification, int $anneeId, ?string $date = null): float
    {
        $paid = $this->legacyPaidForPlan($eleveId, $planification, $anneeId, $date);

        return max(0, (float) $planification->montant_planification - $paid);
    }

    private function legacyPaidForPlan(int $eleveId, Planification $planification, int $anneeId, ?string $date = null): float
    {
        $query = Paiement::where('id_eleve', $eleveId)
            ->where('id_planification', $planification->id_planification)
            ->where('id_annee', $anneeId)
            ->where(function ($q) {
                $q->whereNull('statut')->orWhere('statut', 'valide');
            });

        return (float) $query->sum(DB::raw('COALESCE(montant_paye, montant, 0)'));
    }

    private function legacyPlanMatchesType(?string $motif, string $typePlanification): bool
    {
        if (trim($typePlanification) === '') {
            return true;
        }

        return $this->legacyPlanType($motif) === $this->legacyPlanType($typePlanification);
    }

    private function legacyPlanType(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        if (str_contains($value, 'mensuel')) {
            return 'mensuel';
        }
        if (str_contains($value, 'trimestr')) {
            return 'trimestriel';
        }
        if (str_contains($value, 'annuel')) {
            return 'annuel';
        }

        return $value;
    }

    private function legacyPaymentDelayClass(Planification $planification): string
    {
        if (!$planification->date_fin) {
            return '';
        }

        $end = \Illuminate\Support\Carbon::parse($planification->date_fin)->endOfDay();
        if (now()->greaterThan($end)) {
            return 'table-danger';
        }
        if (now()->diffInDays($end, false) <= 7) {
            return 'table-warning';
        }

        return '';
    }

    private function resolveLegacyPayer(Eleve $eleve, $parentId, ?string $otherName, ?string $otherPhone): array
    {
        if ($parentId && $parentId !== 'autre') {
            $parent = $eleve->parents()->where('parents.id_parent', $parentId)->first();
            if (!$parent) {
                throw ValidationException::withMessages(['parent_id' => 'Le parent sélectionné n’est pas lié à cet élève.']);
            }

            return [
                'parent' => (string) $parent->id_parent,
                'nom_payeur' => (string) $parent->nom_prenom_parent,
                'telephone' => (string) $parent->telephone_parent,
            ];
        }

        $otherName = trim((string) $otherName);
        $otherPhone = trim((string) $otherPhone);
        if ($otherName === '' || $otherPhone === '') {
            throw ValidationException::withMessages(['nom_payeur' => 'Le nom et le téléphone du payeur sont obligatoires.']);
        }
        if (!preg_match('/^[0-9 +().-]{6,30}$/', $otherPhone)) {
            throw ValidationException::withMessages(['telephone' => 'Le téléphone du payeur est invalide.']);
        }

        return [
            'parent' => 'Autre',
            'nom_payeur' => $otherName,
            'telephone' => $otherPhone,
        ];
    }

    private function getOwnedCaisse(int $id): Caisse
    {
        return Caisse::where('id_ecole', session('idEcole'))->findOrFail($id);
    }

    private function currentSchoolYear(): AnneeScolaire
    {
        return AnneeScolaire::whereDate('date_debut', '<=', now())
            ->whereDate('date_fin', '>=', now())
            ->firstOrFail();
    }

    private function nextPaiementReference(): string
    {
        $lastReference = Paiement::where('reference', 'like', 'PAIEMENT-%')
            ->orderByDesc('id_paiement')
            ->value('reference');

        $lastNumber = 0;
        if ($lastReference && preg_match('/^PAIEMENT-(\d+)$/', $lastReference, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return 'PAIEMENT-' . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }

    private function ensurePermission(string $permission): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }
        if ($user->droit !== 'SupAdmin' && !$user->userHasPermission($permission)) {
            abort(403, 'Permission insuffisante.');
        }
    }

    private function authorizePaymentReceipt(Paiement $paiement): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if ($user->droit === 'SupAdmin' || $user->userHasPermission('paiements_apercu')) {
            return;
        }

        if ($user->droit === 'parent') {
            $parentId = $user->id_parent ?: $user->parent?->id_parent;
            if ($parentId && $paiement->eleve?->parents()->where('parents.id_parent', $parentId)->exists()) {
                return;
            }
        }

        abort(403, 'Permission insuffisante.');
    }

    private function ensureAnyPermission(array $permissions): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }
        if ($user->droit === 'SupAdmin') {
            return;
        }
        foreach ($permissions as $permission) {
            if ($user->userHasPermission($permission)) {
                return;
            }
        }

        abort(403, 'Permission insuffisante.');
    }
}
