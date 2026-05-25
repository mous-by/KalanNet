<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AbonnementPaiement;
use App\Services\Abonnements\AbonnementPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class AbonnementController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schoolId = session('idEcole') ?: $user->idEcole;

        if (!$this->canManageAbonnements($user)) {
            abort(403);
        }

        $allOffres = AbonnementOffre::orderBy('montant')->get();
        $offres = $allOffres->where('actif', true)->values();
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
        $manualModes = AbonnementPaymentService::MANUAL_MODES;
        $adminPaiements = collect();
        $canSubmitManual = (bool) $schoolId;

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
        $data['preuve_url'] = $this->storeReceipt($request);

        try {
            $paiement = $payments->initiateManual($schoolId, $offre, $data);
        } catch (RuntimeException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        return redirect()->route('abonnements.paiements.show', $paiement->reference)
            ->with('success', 'Demande envoyée. En attente de validation superadmin.');
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
        if (!$this->canReviewAbonnements(Auth::user())) {
            abort(403);
        }

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
        if (!$this->canReviewAbonnements(Auth::user())) {
            abort(403);
        }

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

    private function canManageAbonnements($user): bool
    {
        return in_array($user?->droit, ['SupAdmin', 'Admin', 'Gestionnaire'], true)
            || $user?->userHasAnyPermission(['abonnements_apercu', 'abonnements_paiement']);
    }

    private function canConfigureAbonnements($user): bool
    {
        return $user?->droit === 'SupAdmin'
            || $user?->userHasPermission('abonnements_configuration');
    }

    private function canReviewAbonnements($user): bool
    {
        return $user?->droit === 'SupAdmin'
            || $user?->userHasPermission('abonnements_validation');
    }

    private function storeReceipt(Request $request): string
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

    private function validatedOffre(Request $request, ?int $ignoreId = null): array
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
            'montant' => 'required|numeric|min:1',
            'devise' => 'required|string|max:8',
            'duree_jours' => 'required|integer|min:1|max:3650',
            'actif' => 'nullable|boolean',
        ]);

        $data['code'] = strtolower(trim($data['code']));
        $data['devise'] = strtoupper(trim($data['devise']));
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
