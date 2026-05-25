<?php

namespace App\Services\Abonnements;

use App\Models\Abonnement;
use App\Models\AbonnementOffre;
use App\Models\AbonnementPaiement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AbonnementPaymentService
{
    public const PROVIDERS = [
        'orange_money' => 'Orange Money',
        'mobile_money' => 'Mobile Money',
        'mobicash' => 'Mobicash',
        'wave' => 'Wave',
    ];

    public const MANUAL_MODES = [
        'Orange Money' => 'Orange Money - 74745669',
        'Wave' => 'Wave - 74745669',
        'MobiCash' => 'MobiCash - 67205736',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'en_attente' => 'En attente',
        'paye' => 'Payé',
        'echec' => 'Échoué',
        'annule' => 'Annulé',
        'rembourse' => 'Remboursé',
    ];

    public function initiate(int $schoolId, AbonnementOffre $offre, string $provider, ?string $payerPhone): AbonnementPaiement
    {
        if (!array_key_exists($provider, self::PROVIDERS)) {
            throw new \InvalidArgumentException('Fournisseur de paiement non supporté.');
        }

        return DB::transaction(function () use ($schoolId, $offre, $provider, $payerPhone) {
            $abonnement = Abonnement::firstOrCreate(
                ['ecole_id' => $schoolId, 'statut' => 'en_attente'],
                ['offre_id' => $offre->id]
            );

            $abonnement->update(['offre_id' => $offre->id]);

            $paiement = AbonnementPaiement::create([
                'abonnement_id' => $abonnement->id,
                'ecole_id' => $schoolId,
                'offre_id' => $offre->id,
                'fournisseur' => $provider,
                'reference' => $this->reference(),
                'numero_payeur' => $payerPhone,
                'montant' => $offre->montant,
                'devise' => $offre->devise,
                'statut' => 'en_attente',
            ]);

            $gateway = $this->callProvider($paiement);
            $paiement->update([
                'reference_fournisseur' => $gateway['provider_reference'] ?? null,
                'checkout_url' => $gateway['checkout_url'] ?? null,
                'payload' => $gateway,
            ]);

            return $paiement;
        });
    }

    public function initiateManual(int $schoolId, AbonnementOffre $offre, array $data): AbonnementPaiement
    {
        if ($this->hasPendingManualPayment($schoolId)) {
            throw new RuntimeException('Une demande en attente existe déjà pour votre école.');
        }

        return DB::transaction(function () use ($schoolId, $offre, $data) {
            $anchor = Abonnement::create([
                'ecole_id' => $schoolId,
                'offre_id' => $offre->id,
                'statut' => 'en_attente',
            ]);

            return AbonnementPaiement::create([
                'abonnement_id' => $anchor->id,
                'ecole_id' => $schoolId,
                'offre_id' => $offre->id,
                'fournisseur' => $data['mode_paiement'],
                'mode_paiement' => 'MANUEL',
                'reference' => $this->manualReference(),
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'owner_note' => $data['owner_note'] ?? null,
                'preuve_url' => $data['preuve_url'],
                'montant' => $offre->montant,
                'devise' => $offre->devise,
                'statut' => 'en_attente',
            ]);
        });
    }

    public function hasPendingManualPayment(int $schoolId): bool
    {
        return AbonnementPaiement::query()
            ->where('ecole_id', $schoolId)
            ->where('mode_paiement', 'MANUEL')
            ->where('statut', 'en_attente')
            ->exists();
    }

    public function approveManualPayment(AbonnementPaiement $paiement, int $reviewerId, ?string $reviewNote = null): AbonnementPaiement
    {
        if ($paiement->statut !== 'en_attente') {
            throw new RuntimeException('Seuls les paiements en attente peuvent être validés.');
        }

        return DB::transaction(function () use ($paiement, $reviewerId, $reviewNote) {
            $paiement->loadMissing('offre');

            $start = now()->startOfDay();
            $latestEnd = Abonnement::query()
                ->where('ecole_id', $paiement->ecole_id)
                ->where('statut', 'actif')
                ->whereNotNull('fin_at')
                ->max('fin_at');

            if ($latestEnd && Carbon::parse($latestEnd)->isFuture()) {
                $start = Carbon::parse($latestEnd)->startOfDay();
            }

            $end = $start->copy()->addDays((int) $paiement->offre->duree_jours);

            $abonnement = Abonnement::create([
                'ecole_id' => $paiement->ecole_id,
                'offre_id' => $paiement->offre_id,
                'statut' => 'actif',
                'debut_at' => $start,
                'fin_at' => $end,
                'dernier_paiement_id' => $paiement->id,
            ]);

            $paiement->update([
                'abonnement_id' => $abonnement->id,
                'statut' => 'paye',
                'review_note' => $reviewNote,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'paye_at' => now(),
            ]);

            return $paiement->fresh();
        });
    }

    public function rejectManualPayment(AbonnementPaiement $paiement, int $reviewerId, ?string $reviewNote = null): AbonnementPaiement
    {
        if ($paiement->statut !== 'en_attente') {
            throw new RuntimeException('Seuls les paiements en attente peuvent être rejetés.');
        }

        $paiement->update([
            'statut' => 'echec',
            'review_note' => $reviewNote,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return $paiement->fresh();
    }

    public static function statusLabel(?string $status): string
    {
        return self::PAYMENT_STATUS_LABELS[$status] ?? ucfirst((string) $status);
    }

    public function markAsPaid(AbonnementPaiement $paiement, array $payload = []): AbonnementPaiement
    {
        if ($paiement->statut === 'paye') {
            return $paiement;
        }

        return DB::transaction(function () use ($paiement, $payload) {
            $paiement->loadMissing('offre', 'abonnement');
            $start = now();
            $currentEnd = $paiement->abonnement?->fin_at;

            if ($currentEnd && $currentEnd->isFuture()) {
                $start = $currentEnd->copy();
            }

            $end = Carbon::parse($start)->addDays((int) $paiement->offre->duree_jours);

            $abonnement = $paiement->abonnement ?: Abonnement::create([
                'ecole_id' => $paiement->ecole_id,
                'offre_id' => $paiement->offre_id,
                'statut' => 'actif',
            ]);

            $paiement->update([
                'statut' => 'paye',
                'paye_at' => now(),
                'payload' => array_merge($paiement->payload ?? [], ['confirmation' => $payload]),
            ]);

            $abonnement->update([
                'offre_id' => $paiement->offre_id,
                'statut' => 'actif',
                'debut_at' => $abonnement->debut_at ?: now(),
                'fin_at' => $end,
                'dernier_paiement_id' => $paiement->id,
            ]);

            return $paiement->fresh();
        });
    }

    public function markFromWebhook(string $provider, array $payload): ?AbonnementPaiement
    {
        $reference = $payload['reference']
            ?? $payload['external_reference']
            ?? $payload['transaction_reference']
            ?? data_get($payload, 'data.client_reference')
            ?? data_get($payload, 'data.custom_fields.reference')
            ?? null;

        $providerReference = $payload['provider_reference']
            ?? $payload['transaction_id']
            ?? data_get($payload, 'data.id')
            ?? data_get($payload, 'data.transaction_id')
            ?? null;

        if (!$reference && !$providerReference) {
            return null;
        }

        $paiement = AbonnementPaiement::query()
            ->where('fournisseur', $provider)
            ->where(function ($query) use ($reference, $providerReference) {
                if ($reference) {
                    $query->where('reference', $reference);
                }
                if ($providerReference) {
                    $method = $reference ? 'orWhere' : 'where';
                    $query->{$method}('reference_fournisseur', $providerReference);
                }
            })
            ->first();

        if (!$paiement) {
            return null;
        }

        $status = strtolower((string) ($payload['status'] ?? $payload['statut'] ?? data_get($payload, 'data.payment_status') ?? $payload['type'] ?? ''));
        $eventType = strtolower((string) ($payload['type'] ?? ''));
        $checkoutStatus = strtolower((string) data_get($payload, 'data.checkout_status', ''));
        $paid = in_array($status, ['success', 'succeeded', 'paid', 'paye', 'merchant.payment_received'], true)
            || ($eventType === 'checkout.session.completed' && $checkoutStatus === 'complete')
            || (bool) ($payload['paid'] ?? false);

        if ($paid) {
            return $this->markAsPaid($paiement, $payload);
        }

        $paiement->update([
            'statut' => in_array($status, ['failed', 'cancelled', 'canceled', 'annule', 'error'], true)
                || $eventType === 'checkout.session.payment_failed'
                    ? 'echec'
                    : $paiement->statut,
            'payload' => array_merge($paiement->payload ?? [], ['webhook' => $payload]),
        ]);

        return $paiement->fresh();
    }

    private function callProvider(AbonnementPaiement $paiement): array
    {
        if ($paiement->fournisseur === 'wave') {
            return $this->callWave($paiement);
        }

        $provider = $paiement->fournisseur;
        $baseUrl = rtrim((string) config("services.abonnements.{$provider}.endpoint"), '/');
        $token = config("services.abonnements.{$provider}.token");

        $payload = [
            'reference' => $paiement->reference,
            'amount' => (float) $paiement->montant,
            'currency' => $paiement->devise,
            'payer_phone' => $paiement->numero_payeur,
            'success_url' => route('abonnements.paiements.show', $paiement->reference),
            'error_url' => route('abonnements.paiements.show', $paiement->reference),
            'callback_url' => route('abonnements.webhook', $provider),
            'description' => 'Abonnement KalanNet',
            'custom_fields' => [
                'reference' => $paiement->reference,
                'ecole_id' => (string) $paiement->ecole_id,
            ],
        ];

        if (!$baseUrl || !$token) {
            return [
                'mode' => 'configuration_manquante',
                'message' => 'Configurez le endpoint et le token du fournisseur pour activer le paiement automatique.',
                'request' => $payload,
            ];
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl, $payload);

        $json = $response->json() ?: [];

        return [
            'mode' => 'api',
            'http_status' => $response->status(),
            'provider_reference' => $json['id'] ?? $json['reference'] ?? $json['transaction_id'] ?? null,
            'checkout_url' => $json['checkout_url'] ?? $json['payment_url'] ?? $json['url'] ?? null,
            'response' => $json,
            'request' => $payload,
        ];
    }

    public function verifyWaveWebhookSignature(string $rawBody, ?string $header): bool
    {
        $secret = config('services.abonnements.wave.webhook_secret');

        if (!$secret) {
            return true;
        }

        $parts = collect(explode(',', $header ?: ''))
            ->mapWithKeys(function ($part) {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

                return $key ? [$key => $value] : [];
            });

        $timestamp = (int) $parts->get('t');
        $signature = (string) $parts->get('v1');

        if (!$timestamp || !$signature) {
            return false;
        }

        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $rawBody, $secret);

        return hash_equals($expected, $signature);
    }

    private function callWave(AbonnementPaiement $paiement): array
    {
        $token = config('services.abonnements.wave.token');
        $endpoint = config('services.abonnements.wave.endpoint') ?: 'https://api.wave.com/v1/checkout/sessions';

        $payload = [
            'amount' => $this->formatAmountForWave($paiement),
            'currency' => strtoupper($paiement->devise),
            'client_reference' => $paiement->reference,
            'success_url' => route('abonnements.paiements.show', $paiement->reference),
            'error_url' => route('abonnements.paiements.show', $paiement->reference),
        ];

        if ($paiement->numero_payeur) {
            $payload['restrict_payer_mobile'] = $this->normalizePhone($paiement->numero_payeur);
        }

        if (config('services.abonnements.wave.aggregated_merchant_id')) {
            $payload['aggregated_merchant_id'] = config('services.abonnements.wave.aggregated_merchant_id');
        }

        if (!$token) {
            return [
                'mode' => 'configuration_manquante',
                'message' => 'Configurez ABONNEMENT_WAVE_API_KEY pour activer Wave Checkout.',
                'request' => $payload,
            ];
        }

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Impossible de préparer la requête Wave.');
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if (config('services.abonnements.wave.signing_secret')) {
            $timestamp = time();
            $headers['Wave-Signature'] = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', $timestamp . $body, config('services.abonnements.wave.signing_secret'));
        }

        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->withBody($body, 'application/json')
            ->post($endpoint);

        $json = $response->json() ?: [];

        return [
            'mode' => 'wave_checkout',
            'http_status' => $response->status(),
            'provider_reference' => $json['id'] ?? null,
            'checkout_url' => $json['wave_launch_url'] ?? null,
            'response' => $json,
            'request' => $payload,
        ];
    }

    private function formatAmountForWave(AbonnementPaiement $paiement): string
    {
        if (strtoupper($paiement->devise) === 'XOF') {
            return (string) (int) round((float) $paiement->montant);
        }

        return number_format((float) $paiement->montant, 2, '.', '');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone) ?: $phone;

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '00')) {
            return '+' . substr($phone, 2);
        }

        return '+223' . ltrim($phone, '0');
    }

    private function reference(): string
    {
        return 'ABN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    private function manualReference(): string
    {
        return 'SUB-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
    }
}
