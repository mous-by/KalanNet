<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AbonnementPaiement;
use App\Models\Ecole;
use App\Models\RevendeurOffre;
use App\Services\Abonnements\AbonnementPaymentService;
use App\Support\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class AbonnementController extends Controller
{
    public function index(AbonnementPaymentService $payments)
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $ecole = $schoolId ? Ecole::withoutGlobalScopes()->find($schoolId) : null;
        $allOffres = AbonnementOffre::orderBy('montant')->get();
        // Self-service (réabonnement école) : on exclut les offres à vie (duree_jours = 0, ex: ACHAT),
        // attribuées par le SuperAdmin uniquement, et les offres réservées à l'autre type d'école
        // (public/privé). $allOffres reste complet pour la configuration admin.
        $offres = $allOffres->where('actif', true)->where('duree_jours', '>', 0)
            ->filter(fn (AbonnementOffre $offre) => $this->offreMatchesEcole($offre, $ecole))
            ->values();

        // Le prix affiché à l'école doit être celui qu'elle paiera réellement —
        // le prix de revente de son revendeur s'il y en a un, sinon le catalogue.
        if ($schoolId) {
            $offres->each(function (AbonnementOffre $offre) use ($payments, $schoolId) {
                $offre->montant_effectif = $payments->resolveMontant((int) $schoolId, $offre);
            });
        } else {
            $offres->each(fn (AbonnementOffre $offre) => $offre->montant_effectif = (float) $offre->montant);
        }
        $abonnement = Abonnement::with('offre')
            ->where('ecole_id', $schoolId)
            ->orderByDesc('id')
            ->first();
        $paiements = AbonnementPaiement::with('offre')
            ->where('ecole_id', $schoolId)
            ->orderByDesc('id')
            ->limit(12)
            ->get();
        $canConfigure = $this->canConfigureAbonnements($user);
        $canReview = $this->canReviewAbonnements($user);
        // Les numéros de dépôt affichés (formulaire + carrousel) sont ceux du
        // revendeur de l'école s'il en a un, sinon ceux de la plateforme.
        $manualModes = $payments->manualModesFor($ecole);
        $manualNumbers = $payments->manualPaymentNumbers($ecole);
        $adminPaiements = collect();
        $canSubmitManual = (bool) $schoolId;

        // Un Admin/Gestionnaire d'une école bloquée, sans droit de configuration
        // ni de validation, ne doit voir qu'un écran de réabonnement minimal —
        // pas l'historique complet ni les autres sections de cette page.
        if ($schoolId && !$canConfigure && !$canReview && SubscriptionGate::isBlocked((int) $schoolId)) {
            return view('abonnements.blocked', compact('offres', 'manualModes', 'manualNumbers', 'canSubmitManual'));
        }

        if ($canReview) {
            $adminPaiements = AbonnementPaiement::with(['offre', 'ecole'])
                ->when(request('status'), fn ($query, $status) => $query->where('statut', $status))
                ->orderByRaw("CASE WHEN statut = 'en_attente' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->limit(80)
                ->get();
        }

        return view('abonnements.index', compact('offres', 'allOffres', 'abonnement', 'paiements', 'canConfigure', 'canReview', 'manualModes', 'adminPaiements', 'canSubmitManual'));
    }

    public function payer(Request $request, AbonnementPaymentService $payments)
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $data = $request->validate([
            'offre_id' => 'required|integer|exists:abonnement_offres,id',
            'fournisseur' => 'required|string|in:' . implode(',', array_keys(AbonnementPaymentService::PROVIDERS)),
            'numero_payeur' => 'nullable|string|max:40',
        ]);

        $offre = AbonnementOffre::where('actif', true)->findOrFail($data['offre_id']);
        // La licence à vie (duree_jours = 0, ex: ACHAT) est attribuée par le SuperAdmin uniquement.
        abort_if($offre->duree_jours <= 0, 422, "Cette offre n'est pas disponible à la souscription en ligne.");
        abort_unless($this->offreMatchesEcole($offre, Ecole::withoutGlobalScopes()->find($schoolId)), 422, "Cette offre n'est pas disponible pour votre type d'école.");
        $paiement = $payments->initiate($schoolId, $offre, $data['fournisseur'], $data['numero_payeur'] ?? null);

        if ($paiement->checkout_url) {
            return redirect()->away($paiement->checkout_url);
        }

        return redirect()->route('abonnements.paiements.show', $paiement->reference)
            ->with('success', 'Paiement initié. Configurez le fournisseur ou validez le callback pour activer automatiquement l’abonnement.');
    }

    public function manualSubmit(Request $request, AbonnementPaymentService $payments)
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user) || !$schoolId) {
            abort(403);
        }

        $data = $request->validate([
            'offre_id' => 'required|integer|exists:abonnement_offres,id',
            'mode_paiement' => 'required|string|in:' . implode(',', array_keys(AbonnementPaymentService::MANUAL_MODES)),
            'transaction_ref' => 'nullable|string|max:120',
            'owner_note' => 'nullable|string|max:1000',
            'receipt' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $offre = AbonnementOffre::where('actif', true)->findOrFail($data['offre_id']);
        // La licence à vie (duree_jours = 0, ex: ACHAT) est attribuée par le SuperAdmin uniquement.
        abort_if($offre->duree_jours <= 0, 422, "Cette offre n'est pas disponible à la souscription en ligne.");
        abort_unless($this->offreMatchesEcole($offre, Ecole::withoutGlobalScopes()->find($schoolId)), 422, "Cette offre n'est pas disponible pour votre type d'école.");
        $data['preuve_url'] = $this->storeReceipt($request);

        try {
            $paiement = $payments->initiateManual($schoolId, $offre, $data);
        } catch (RuntimeException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        return redirect()->route('abonnements.paiements.show', $paiement->reference)
            ->with('success', 'Demande envoyée. En attente de validation.');
    }

    public function paiement($reference)
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $paiement = AbonnementPaiement::with(['offre', 'abonnement'])
            ->where('ecole_id', $schoolId)
            ->where('reference', $reference)
            ->firstOrFail();

        return view('abonnements.paiement', compact('paiement'));
    }

    public function approvePaiement(Request $request, AbonnementPaiement $paiement, AbonnementPaymentService $payments)
    {
        $user = Auth::user();
        if (!$this->canReviewAbonnements($user)) {
            abort(403);
        }
        $this->authorizePaiementReview($user, $paiement);

        $data = $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        try {
            $payments->approveManualPayment($paiement, Auth::id(), $data['review_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return back()->with('success', 'Paiement validé avec succès. L’abonnement est activé.');
    }

    public function rejectPaiement(Request $request, AbonnementPaiement $paiement, AbonnementPaymentService $payments)
    {
        $user = Auth::user();
        if (!$this->canReviewAbonnements($user)) {
            abort(403);
        }
        $this->authorizePaiementReview($user, $paiement);

        $data = $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        try {
            $payments->rejectManualPayment($paiement, Auth::id(), $data['review_note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return back()->with('success', 'Paiement rejeté.');
    }

    /**
     * SupAdmin reviews pending payments across every school by design (the
     * SupAdmin dashboard's global pending-validations list). But
     * abonnements_validation is an ordinary assignable permission — an
     * Admin/Gestionnaire granted it must only review their OWN school's
     * payments, never one guessed via a foreign AbonnementPaiement id.
     */
    protected function authorizePaiementReview($user, AbonnementPaiement $paiement): void
    {
        if ($user->droit === 'SupAdmin') {
            return;
        }

        // Un revendeur ne valide que les paiements des écoles qu'il a apportées.
        if ($user->droit === 'revendeur') {
            $ecole = Ecole::withoutGlobalScopes()->find($paiement->ecole_id);
            if ($ecole && $user->id_revendeur && (int) $ecole->id_revendeur === (int) $user->id_revendeur) {
                return;
            }
            abort(403);
        }

        $idEcole = session('idEcole') ?: $user->idEcole;
        if ((int) $paiement->ecole_id !== (int) $idEcole) {
            abort(403);
        }
    }

    public function webhook(Request $request, string $provider, AbonnementPaymentService $payments)
    {
        if (!array_key_exists($provider, AbonnementPaymentService::PROVIDERS)) {
            abort(404);
        }

        if ($provider === 'wave' && !$payments->verifyWaveWebhookSignature($request->getContent(), $request->header('Wave-Signature'))) {
            return response()->json(['ok' => false, 'message' => 'Signature Wave invalide.'], 401);
        }

        $paiement = $payments->markFromWebhook($provider, $request->all());

        return response()->json([
            'ok' => (bool) $paiement,
            'reference' => $paiement?->reference,
            'status' => $paiement?->statut,
        ]);
    }

    public function storeOffre(Request $request)
    {
        if (!$this->canConfigureAbonnements(Auth::user())) {
            abort(403);
        }

        AbonnementOffre::create($this->validatedOffre($request));

        return back()->with('success', 'Formule d’abonnement créée.');
    }

    public function updateOffre(Request $request, AbonnementOffre $offre)
    {
        if (!$this->canConfigureAbonnements(Auth::user())) {
            abort(403);
        }

        $offre->update($this->validatedOffre($request, $offre->id));

        return back()->with('success', 'Formule d’abonnement mise à jour.');
    }

    public function toggleOffre(AbonnementOffre $offre)
    {
        if (!$this->canConfigureAbonnements(Auth::user())) {
            abort(403);
        }

        $active = !$offre->actif;
        $offre->update(['actif' => $active]);

        return back()->with('success', $active ? 'Formule activée.' : 'Formule désactivée.');
    }

    /**
     * type_ecole_cible = null => offre valable pour tout type d'école, sinon elle
     * doit correspondre au statut (public/prive) de l'école. Et si l'école a été
     * apportée par un revendeur, elle ne doit voir QUE les formules que ce
     * revendeur lui a explicitement ouvertes — jamais le catalogue complet.
     */
    protected function offreMatchesEcole(AbonnementOffre $offre, ?Ecole $ecole): bool
    {
        if ($offre->type_ecole_cible && (!$ecole || $offre->type_ecole_cible !== $ecole->statut)) {
            return false;
        }

        if ($ecole?->id_revendeur) {
            return RevendeurOffre::where('id_revendeur', $ecole->id_revendeur)
                ->where('id_offre', $offre->id)
                ->where('actif', true)
                ->exists();
        }

        return true;
    }

    protected function canManageAbonnements($user): bool
    {
        return in_array($user?->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true)
            || $user?->userHasAnyPermission(['abonnements_apercu', 'abonnements_paiement']);
    }

    /**
     * Fixer les tarifs des formules est une action plateforme, jamais délégable
     * à un Admin/Gestionnaire d'école — même via la permission
     * 'abonnements_configuration', qui reste dans le catalogue pour ne pas
     * casser d'éventuelles attributions existantes mais n'accorde plus cet
     * accès (le badge "Superadmin" affiché sur cette section doit être exact).
     */
    protected function canConfigureAbonnements($user): bool
    {
        return $user?->droit === 'SupAdmin';
    }

    protected function canReviewAbonnements($user): bool
    {
        return $user?->droit === 'SupAdmin'
            // Un revendeur valide les paiements de ses propres écoles (portée
            // vérifiée par authorizePaiementReview) — le SupAdmin garde
            // toujours, en plus, le contrôle total sur tout.
            || $user?->droit === 'revendeur'
            || $user?->userHasPermission('abonnements_validation');
    }

    protected function storeReceipt(Request $request): string
    {
        $file = $request->file('receipt');
        $directory = public_path('uploads/subscription_receipts');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = 'receipt_' . (int) (session('idEcole') ?: Auth::user()->idEcole) . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return '/uploads/subscription_receipts/' . $filename;
    }

    protected function validatedOffre(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('abonnement_offres', 'code')->ignore($ignoreId),
            ],
            'nom' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            // montant=0 et duree_jours=0 autorisés = licence à vie (offre ACHAT).
            'montant' => 'required|numeric|min:0',
            'devise' => 'required|string|max:8',
            'duree_jours' => 'required|integer|min:0|max:3650',
            'type_ecole_cible' => 'nullable|in:public,prive',
            'actif' => 'nullable|boolean',
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['devise'] = strtoupper(trim($data['devise']));
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
