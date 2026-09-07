<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AbonnementController as WebAbonnementController;
use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AbonnementPaiement;
use App\Services\Abonnements\AbonnementPaymentService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The payment-provider webhook is a server-to-server callback, not a
 * mobile concern, and stays only on the web routes. Online checkout
 * (payer) below hands the mobile client a URL to open in an in-app
 * browser instead of doing a server-side redirect.
 */
class AbonnementController extends WebAbonnementController
{
    public function payer(Request $request, AbonnementPaymentService $payments)
    {
        $user = $request->user();
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
        abort_if($offre->duree_jours <= 0, 422, "Cette offre n'est pas disponible à la souscription en ligne.");
        $paiement = $payments->initiate($schoolId, $offre, $data['fournisseur'], $data['numero_payeur'] ?? null);

        return response()->json([
            'reference' => $paiement->reference,
            'checkout_url' => $paiement->checkout_url,
        ], 201);
    }

    public function index()
    {
        $request = request();
        $user = $request->user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $allOffres = AbonnementOffre::orderBy('montant')->get();
        $offres = $allOffres->where('actif', true)->where('duree_jours', '>', 0)->values();
        $abonnement = Abonnement::with('offre')->where('ecole_id', $schoolId)->orderByDesc('id')->first();
        $paiements = AbonnementPaiement::with('offre')->where('ecole_id', $schoolId)->orderByDesc('id')->limit(12)->get();
        $canReview = $this->canReviewAbonnements($user);
        $adminPaiements = collect();

        if ($canReview) {
            $adminPaiements = AbonnementPaiement::with(['offre', 'ecole'])
                ->when($request->get('status'), fn ($query, $status) => $query->where('statut', $status))
                ->orderByRaw("CASE WHEN statut = 'en_attente' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->limit(80)
                ->get();
        }

        return response()->json([
            'offres' => $offres,
            'all_offres' => $allOffres,
            'abonnement' => $abonnement,
            'paiements' => $paiements,
            'can_configure' => $this->canConfigureAbonnements($user),
            'can_review' => $canReview,
            'admin_paiements' => $adminPaiements,
        ]);
    }

    public function manualSubmit(Request $request, AbonnementPaymentService $payments)
    {
        $user = $request->user();
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
        abort_if($offre->duree_jours <= 0, 422, "Cette offre n'est pas disponible à la souscription en ligne.");
        $data['preuve_url'] = $this->storeReceipt($request);

        try {
            $paiement = $payments->initiateManual($schoolId, $offre, $data);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($paiement, 201);
    }

    public function paiement($reference)
    {
        $user = request()->user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $paiement = AbonnementPaiement::with(['offre', 'abonnement'])
            ->where('ecole_id', $schoolId)
            ->where('reference', $reference)
            ->firstOrFail();

        return response()->json($paiement);
    }

    public function approvePaiement(Request $request, AbonnementPaiement $paiement, AbonnementPaymentService $payments)
    {
        if (!$this->canReviewAbonnements($request->user())) {
            abort(403);
        }

        $data = $request->validate(['review_note' => 'nullable|string|max:1000']);

        try {
            $payments->approveManualPayment($paiement, $request->user()->idUtilisateur, $data['review_note'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function rejectPaiement(Request $request, AbonnementPaiement $paiement, AbonnementPaymentService $payments)
    {
        if (!$this->canReviewAbonnements($request->user())) {
            abort(403);
        }

        $data = $request->validate(['review_note' => 'nullable|string|max:1000']);

        try {
            $payments->rejectManualPayment($paiement, $request->user()->idUtilisateur, $data['review_note'] ?? null);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function storeOffre(Request $request)
    {
        if (!$this->canConfigureAbonnements($request->user())) {
            abort(403);
        }

        $offre = AbonnementOffre::create($this->validatedOffre($request));

        return response()->json($offre, 201);
    }

    public function updateOffre(Request $request, AbonnementOffre $offre)
    {
        if (!$this->canConfigureAbonnements($request->user())) {
            abort(403);
        }

        $offre->update($this->validatedOffre($request, $offre->id));

        return response()->json($offre->fresh());
    }

    public function toggleOffre(AbonnementOffre $offre)
    {
        if (!$this->canConfigureAbonnements(request()->user())) {
            abort(403);
        }

        $offre->update(['actif' => !$offre->actif]);

        return response()->json($offre->fresh());
    }
}
