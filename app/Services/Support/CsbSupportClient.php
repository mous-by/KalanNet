<?php

namespace App\Services\Support;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Client de l'API de tickets du site CSB (Computer Service Barry). Chaque utilisateur de KalanNet ouvre et suit ses
 * propres tickets : son identifiant (`reporter_ref`) les cloisonne côté CSB. CSB est prévenu tout de suite (e-mail,
 * notification push, WhatsApp) et répond depuis son administration.
 */
class CsbSupportClient
{
    public function configured(): bool
    {
        return filled(config('services.csb_support.url')) && filled(config('services.csb_support.token'));
    }

    /** Types de demande et urgences proposés par CSB. @return array{categories: array<string, string>, priorities: array<string, string>} */
    public function options(): array
    {
        return Cache::remember('csb_support.options', now()->addHours(6), fn () => $this->call(fn ($http) => $http->get('/api/v1/tickets/options'))->json());
    }

    /** @return list<array<string, mixed>> */
    public function tickets(User $user): array
    {
        return $this->call(fn ($http) => $http->get('/api/v1/tickets', ['reporter_ref' => $this->ref($user)]))->json('data', []);
    }

    /** @return array<string, mixed> */
    public function ticket(User $user, string $number): array
    {
        return $this->call(fn ($http) => $http->get('/api/v1/tickets/'.rawurlencode($number), ['reporter_ref' => $this->ref($user)]))->json('data');
    }

    /** @param array{subject: string, category: string, priority: string, device?: ?string, message: string} $data */
    public function open(User $user, array $data, ?UploadedFile $attachment = null): array
    {
        $reporter = [
            'ref' => $this->ref($user), 'name' => $user->nomPrenom, 'role' => $user->droit,
            'organization' => $user->ecole?->nomEcole, 'email' => $user->email ?: null, 'phone' => $user->telephone ? (string) $user->telephone : null,
        ];

        return $this->call(fn ($http) => $this->post($http, '/api/v1/tickets', $data + ['reporter' => array_filter($reporter)], $attachment))->json('data');
    }

    public function reply(User $user, string $number, string $body, ?UploadedFile $attachment = null): array
    {
        return $this->call(fn ($http) => $this->post($http, '/api/v1/tickets/'.rawurlencode($number).'/messages', ['reporter_ref' => $this->ref($user), 'body' => $body], $attachment))->json('data');
    }

    public function attachment(User $user, string $number, int $messageId): Response
    {
        return $this->call(fn ($http) => $http->get('/api/v1/tickets/'.rawurlencode($number).'/messages/'.$messageId.'/piece-jointe', ['reporter_ref' => $this->ref($user)]));
    }

    private function ref(User $user): string
    {
        return (string) $user->idUtilisateur;
    }

    /** JSON, ou multipart avec la pièce jointe (champs aplatis : « reporter[name] »). */
    private function post(PendingRequest $http, string $url, array $data, ?UploadedFile $file): Response
    {
        if (! $file) {
            return $http->asJson()->post($url, $data);
        }

        $fields = collect(Arr::dot(array_filter($data, fn ($v) => $v !== null)))
            ->mapWithKeys(fn ($value, $key) => [preg_replace('/\\.([^.]+)/', '[$1]', $key) => $value])->all();

        return $http->attach('attachment', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())->post($url, $fields);
    }

    /**
     * Appel à CSB : 422 → erreurs de formulaire, 404 → ticket introuvable, autre échec → CsbSupportUnavailable.
     *
     * @param  callable(PendingRequest): Response  $request
     */
    private function call(callable $request): Response
    {
        if (! $this->configured()) {
            throw new CsbSupportUnavailable(__('support_csb.non_configure'));
        }

        try {
            $response = $request(Http::baseUrl(rtrim((string) config('services.csb_support.url'), '/'))
                ->withToken((string) config('services.csb_support.token'))->acceptJson()->timeout(20));
        } catch (ConnectionException) {
            throw new CsbSupportUnavailable(__('support_csb.injoignable'));
        }

        if ($response->status() === 422) {
            throw ValidationException::withMessages(collect($response->json('errors', []))
                ->mapWithKeys(fn ($messages, $field) => [str_replace('reporter.', '', $field) => $messages])->all());
        }
        if ($response->status() === 404) {
            abort(404);
        }
        if ($response->failed()) {
            report(new RuntimeException("Support CSB : HTTP {$response->status()}"));

            throw new CsbSupportUnavailable(__('support_csb.injoignable'));
        }

        return $response;
    }
}
