<?php

namespace App\Services\Paiements;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\EcheancePaiement;
use App\Models\Eleve;
use App\Models\Encaissement;
use App\Models\FraisScolaire;
use App\Models\Paiement;
use App\Models\ParentModel;
use App\Models\PlanPaiement;
use App\Models\ReductionPaiementConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementEleveService
{
    public const PUBLIC_FEE_TYPES = ['Cooperative', 'Inscription', 'Badge', 'Tenue', 'Activites', 'Autres cotisations'];

    public function __construct(
        private readonly ReductionService $reductionService,
        private readonly EcheanceService $echeanceService,
        private readonly ReferencePaiementService $referenceService,
    ) {
    }

    public function studentContext(int $eleveId, ?int $anneeId = null): array
    {
        $eleve = Eleve::with(['ecole', 'classe', 'parents'])->findOrFail($eleveId);
        $annee = $anneeId
            ? AnneeScolaire::findOrFail($anneeId)
            : AnneeScolaire::where(function ($query) use ($eleve) {
                $query->where('id_ecole', $eleve->id_ecole)->orWhereNull('id_ecole');
            })->orderByDesc('id_anneeScolaire')->firstOrFail();

        $frais = $this->availableFees($eleve, $annee->id_anneeScolaire);
        $plan = PlanPaiement::with('echeances')
            ->where('eleve_id', $eleve->id_eleve)
            ->where('annee_scolaire_id', $annee->id_anneeScolaire)
            ->first();

        return [
            'eleve' => $eleve,
            'ecole' => $eleve->ecole,
            'classe' => $eleve->classe,
            'annee' => $annee,
            'type_ecole' => $this->schoolType($eleve),
            'frais' => $frais,
            'plan' => $plan,
            'resume' => $plan ? $this->planSummary($plan) : null,
        ];
    }

    public function createOrUpdatePlan(int $eleveId, int $anneeId, string $modePaiement, ?array $customEcheances = null): PlanPaiement
    {
        return DB::transaction(function () use ($eleveId, $anneeId, $modePaiement, $customEcheances) {
            $eleve = Eleve::with('ecole')->lockForUpdate()->findOrFail($eleveId);
            $annee = AnneeScolaire::findOrFail($anneeId);
            $frais = $this->availableFees($eleve, $anneeId);
            $montantTotal = (float) $frais->sum('montant');
            $reduction = $this->reductionService->apply($eleve, $montantTotal, $anneeId);

            $plan = PlanPaiement::updateOrCreate(
                ['eleve_id' => $eleve->id_eleve, 'annee_scolaire_id' => $anneeId],
                [
                    'ecole_id' => $eleve->id_ecole,
                    'classe_id' => $eleve->id_classe,
                    'mode_paiement' => $modePaiement,
                    'statut_paiement' => $reduction['statut_paiement'],
                    'montant_total' => $montantTotal,
                    'reduction' => $reduction['reduction'],
                    'montant_final' => $reduction['montant_final'],
                    'payeur_type' => $reduction['payeur_type'],
                    'payeur_libelle' => $reduction['payeur_libelle'],
                    'details_frais' => $frais->map(fn (FraisScolaire $frais) => [
                        'id' => $frais->id,
                        'type_frais' => $frais->type_frais,
                        'montant' => (float) $frais->montant,
                    ])->values()->all(),
                ]
            );

            if ($this->schoolType($eleve) === 'publique' && empty($customEcheances)) {
                $customEcheances = $frais->map(fn (FraisScolaire $frais) => [
                    'libelle' => $frais->type_frais,
                    'montant_prevu' => (float) $frais->montant,
                    'date_limite' => $annee->date_fin,
                ])->values()->all();
            }

            $this->echeanceService->generate($plan, $annee, $customEcheances);

            return $plan->refresh()->load(['eleve', 'classe', 'anneeScolaire', 'echeances']);
        });
    }

    public function encaisser(array $payload): Paiement
    {
        return DB::transaction(function () use ($payload) {
            $echeance = EcheancePaiement::with('planPaiement.eleve.parents')
                ->lockForUpdate()
                ->findOrFail($payload['echeance_id']);
            $plan = $echeance->planPaiement;
            $eleve = $plan->eleve;

            $caisse = Caisse::where('id_ecole', $plan->ecole_id)
                ->where('status', 1)
                ->lockForUpdate()
                ->first();

            if (!$caisse) {
                throw ValidationException::withMessages([
                    'caisse' => 'Aucune caisse active trouvée pour cette école.',
                ]);
            }

            $reste = $this->remainingForEcheance($echeance);
            $montant = round((float) $payload['montant_paye'], 2);

            if ($montant <= 0) {
                throw ValidationException::withMessages(['montant_paye' => 'Le montant reçu est invalide.']);
            }

            if ($montant > $reste) {
                throw ValidationException::withMessages([
                    'montant_paye' => 'Le montant reçu dépasse le reste à payer.',
                ]);
            }

            [$nomPayeur, $telephone, $parentId] = $this->resolvePayer($payload, $eleve, $plan);
            $reference = $this->referenceService->nextReference();
            $numeroRecu = $this->referenceService->nextReceiptNumber((int) $plan->ecole_id);

            $paiement = Paiement::create([
                'date_paiement' => $payload['date_paiement'],
                'mode_reglement' => $payload['mode_reglement'] ?? 'especes',
                'reference' => $reference,
                'id_classe' => $plan->classe_id,
                'id_annee' => $plan->annee_scolaire_id,
                'id_eleve' => $eleve->id_eleve,
                'echeance_id' => $echeance->id,
                'motif' => $payload['motif'] ?? $echeance->libelle,
                'montant' => $montant,
                'montant_paye' => $montant,
                'parent' => $parentId,
                'nom_payeur' => $nomPayeur,
                'telephone' => $telephone,
                'id_trimestre' => $payload['id_trimestre'] ?? 0,
                'idEcole' => $plan->ecole_id,
                'id_utilisateur' => Auth::id(),
                'id_caisse' => $caisse->id_caisse,
                'numero_recu' => $numeroRecu,
                'id_planification' => $payload['id_planification'] ?? 0,
                'statut' => 'valide',
            ]);

            $encaissement = Encaissement::create([
                'paiement_id' => $paiement->id_paiement,
                'type_operation' => 'Paiement eleve',
                'date_encaissement' => $payload['date_paiement'],
                'motif_encaissement' => $payload['motif'] ?? $echeance->libelle,
                'montant_encaissement' => $montant,
                'id_annee_scolaire' => $plan->annee_scolaire_id,
                'id_caisse' => $caisse->id_caisse,
                'idUtilisateur' => Auth::id(),
                'statut' => 'valide',
            ]);

            $paiement->encaissement_id = $encaissement->id_encaissement;
            $paiement->save();

            $caisse->montant_net = (float) $caisse->montant_net + $montant;
            $caisse->save();

            $this->refreshEcheanceStatus($echeance);

            return $paiement->load(['eleve', 'classe', 'echeance', 'encaissement']);
        });
    }

    public function cancelPayment(Paiement $paiement, string $motif): void
    {
        DB::transaction(function () use ($paiement, $motif) {
            $paiement = Paiement::whereKey($paiement->getKey())->lockForUpdate()->firstOrFail();

            if ($paiement->statut === 'annule') {
                return;
            }

            $caisse = Caisse::whereKey($paiement->id_caisse)->lockForUpdate()->firstOrFail();
            $montant = (float) ($paiement->montant_paye ?? $paiement->montant);

            if ((float) $caisse->montant_net < $montant) {
                throw ValidationException::withMessages([
                    'paiement' => 'Solde de caisse insuffisant pour annuler ce paiement.',
                ]);
            }

            $paiement->update([
                'statut' => 'annule',
                'annule_at' => now(),
                'annule_par' => Auth::id(),
                'motif_annulation' => $motif,
            ]);

            if ($paiement->encaissement_id) {
                Encaissement::whereKey($paiement->encaissement_id)->update(['statut' => 'annule']);
            }

            Encaissement::create([
                'paiement_id' => $paiement->id_paiement,
                'type_operation' => 'Annulation paiement eleve',
                'date_encaissement' => now()->toDateString(),
                'motif_encaissement' => $motif,
                'montant_encaissement' => -1 * $montant,
                'id_annee_scolaire' => $paiement->id_annee,
                'id_caisse' => $paiement->id_caisse,
                'idUtilisateur' => Auth::id(),
                'statut' => 'valide',
            ]);

            $caisse->montant_net = (float) $caisse->montant_net - $montant;
            $caisse->save();

            if ($paiement->echeance_id) {
                $this->refreshEcheanceStatus(EcheancePaiement::find($paiement->echeance_id));
            }
        });
    }

    public function availableFees(Eleve $eleve, int $anneeScolaireId)
    {
        $query = FraisScolaire::query()
            ->where('ecole_id', $eleve->id_ecole)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('actif', true);

        if ($this->schoolType($eleve) === 'publique') {
            $query->whereIn('type_frais', self::PUBLIC_FEE_TYPES)
                ->where(function ($q) use ($eleve) {
                    $q->whereNull('classe_id')->orWhere('classe_id', $eleve->id_classe);
                });
        } else {
            $query->where('classe_id', $eleve->id_classe);
        }

        return $query->orderBy('type_frais')->get();
    }

    public function planSummary(PlanPaiement $plan): array
    {
        $plan->loadMissing('echeances.paiements');
        $totalPaye = Paiement::whereIn('echeance_id', $plan->echeances->pluck('id'))
            ->where('statut', 'valide')
            ->sum(DB::raw('COALESCE(montant_paye, montant)'));
        $reste = max(0, (float) $plan->montant_final - (float) $totalPaye);
        $retard = $plan->echeances
            ->filter(fn ($echeance) => $echeance->date_limite->isPast() && $echeance->statut !== 'paye')
            ->sum(fn ($echeance) => $this->remainingForEcheance($echeance));

        return [
            'total_a_payer' => (float) $plan->montant_final,
            'total_paye' => (float) $totalPaye,
            'reste_a_payer' => $reste,
            'avance' => max(0, (float) $totalPaye - (float) $plan->montant_final),
            'retard' => (float) $retard,
        ];
    }

    public function remainingForEcheance(EcheancePaiement $echeance): float
    {
        $paid = Paiement::where('echeance_id', $echeance->id)
            ->where('statut', 'valide')
            ->sum(DB::raw('COALESCE(montant_paye, montant)'));

        return max(0, round((float) $echeance->montant_prevu - (float) $paid, 2));
    }

    private function refreshEcheanceStatus(?EcheancePaiement $echeance): void
    {
        if (!$echeance) {
            return;
        }

        $remaining = $this->remainingForEcheance($echeance);
        $paid = (float) $echeance->montant_prevu - $remaining;

        $status = match (true) {
            $remaining <= 0.0 => 'paye',
            $paid > 0.0 => 'partiel',
            now()->toDateString() > $echeance->date_limite->toDateString() => 'retard',
            default => 'en_attente',
        };

        $echeance->update(['statut' => $status]);
    }

    private function resolvePayer(array $payload, Eleve $eleve, PlanPaiement $plan): array
    {
        if (in_array($plan->payeur_type, ['etat', 'organisme'], true)) {
            return [$plan->payeur_libelle ?: ucfirst($plan->payeur_type), '', null];
        }

        if ($plan->payeur_type === 'aucun') {
            throw ValidationException::withMessages(['paiement' => 'Aucun paiement requis pour cet élève.']);
        }

        $parentId = $payload['parent_id'] ?? null;
        if ($parentId) {
            $parent = ParentModel::where('idEcole', $eleve->id_ecole)->findOrFail($parentId);
            if (!$eleve->parents()->where('parents.id_parent', $parent->id_parent)->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'Le parent choisi n’est pas lié à cet élève.']);
            }

            return [$parent->nom_prenom_parent, $parent->telephone_parent, $parent->id_parent];
        }

        $nom = trim((string) ($payload['nom_payeur'] ?? ''));
        $telephone = preg_replace('/\D/', '', (string) ($payload['telephone'] ?? ''));

        if ($nom === '' || $telephone === '') {
            throw ValidationException::withMessages(['nom_payeur' => 'Le nom et le téléphone du payeur sont obligatoires.']);
        }

        if (!preg_match('/^[0-9]{8,15}$/', $telephone)) {
            throw ValidationException::withMessages(['telephone' => 'Le numéro de téléphone du payeur est invalide.']);
        }

        return [$nom, $telephone, null];
    }

    private function schoolType(Eleve $eleve): string
    {
        $type = strtolower((string) ($eleve->ecole?->typeEcole ?? ''));

        return str_contains($type, 'public') ? 'publique' : 'privee';
    }
}
