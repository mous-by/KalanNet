<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AssistantService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Tu es KalanBot, l'assistant officiel de KalanNet,
une application de gestion scolaire multi-école.
Tu aides les utilisateurs à comprendre et utiliser
les fonctionnalités de l'application : gestion des élèves,
paiements scolaires, classes, enseignants, notes,
bulletins et paramètres. Tu réponds toujours en français
sauf si l'utilisateur écrit dans une autre langue.
Tu es professionnel, précis et bienveillant.
Tu ne réponds qu'aux questions liées à KalanNet
et à la gestion scolaire.
PROMPT;

    public function reply(string $message, array $history = [], array $context = []): string
    {
        $provider = strtolower((string) config('services.assistant.provider', 'gemini'));
        $messages = $this->buildMessages($message, $history, $context);

        try {
            return match ($provider) {
                'groq' => $this->chatOpenAiCompatible('groq', $messages),
                'openrouter' => $this->chatOpenAiCompatible('openrouter', $messages),
                'ollama' => $this->chatOllama($messages),
                default => $this->chatGemini($messages),
            };
        } catch (\Throwable $exception) {
            report($exception);

            return $this->unavailableMessage();
        }
    }

    private function buildMessages(string $message, array $history, array $context): array
    {
        $maxHistory = max(0, (int) config('services.assistant.max_history', 8));
        $history = collect($history)
            ->take(-$maxHistory)
            ->map(function ($item) {
                $role = ($item['role'] ?? '') === 'assistant' ? 'assistant' : 'user';

                return [
                    'role' => $role,
                    'content' => $this->sanitizeText((string) ($item['content'] ?? '')),
                ];
            })
            ->filter(fn ($item) => filled($item['content']))
            ->values()
            ->all();

        $contextText = $this->contextPrompt($context);

        return array_merge([
            ['role' => 'system', 'content' => trim(self::SYSTEM_PROMPT . "\n\n" . $contextText)],
        ], $history, [
            ['role' => 'user', 'content' => $this->sanitizeText($message)],
        ]);
    }

    private function contextPrompt(array $context): string
    {
        $route = $this->sanitizeText((string) ($context['route'] ?? ''));
        $path = $this->sanitizeText((string) ($context['path'] ?? ''));
        $title = $this->sanitizeText((string) ($context['title'] ?? ''));

        return <<<TEXT
Contexte de navigation non sensible :
- Page : {$title}
- Route : {$route}
- Chemin : {$path}

Modules principaux de KalanNet :
- Tableau de bord : indicateurs, notifications, annonces.
- Élèves & parents : inscriptions, dossiers, transferts, réintégration, parents.
- Classes & cours : classes, matières, programmes, emploi du temps.
- Enseignants : fiches, émargements, cahier de présence, salaires.
- Évaluations : notes, validation des notes, appels de présence, bulletins.
- Finances : paiements, caisse, banques, versements, retraits.
- Configuration : années, écoles, utilisateurs, permissions, types de notes.

Ne demande pas de données sensibles. Si une réponse dépend de droits, conseille à l'utilisateur de vérifier ses permissions ou de contacter l'administration.
TEXT;
    }

    private function chatGemini(array $messages): string
    {
        $key = config('services.assistant.gemini.key');
        if (!$key) {
            return $this->missingProviderMessage('Gemini');
        }

        $model = config('services.assistant.gemini.model', 'gemini-1.5-flash');
        $endpoint = rtrim(config('services.assistant.gemini.endpoint'), '/');
        $system = collect($messages)->firstWhere('role', 'system')['content'] ?? self::SYSTEM_PROMPT;
        $contents = collect($messages)
            ->whereIn('role', ['user', 'assistant'])
            ->map(fn ($message) => [
                'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message['content']]],
            ])
            ->values()
            ->all();

        $response = Http::timeout($this->timeout())
            ->withHeaders(['x-goog-api-key' => $key])
            ->post("{$endpoint}/models/{$model}:generateContent", [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 700,
                ],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException('Gemini assistant error: ' . $response->body()));
            return $this->unavailableMessage();
        }

        return trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text')) ?: $this->unavailableMessage();
    }

    private function chatOpenAiCompatible(string $provider, array $messages): string
    {
        $config = config("services.assistant.{$provider}");
        $key = $config['key'] ?? null;
        if (!$key) {
            return $this->missingProviderMessage(Str::title($provider));
        }

        $request = Http::timeout($this->timeout())
            ->withToken($key)
            ->acceptJson();

        if ($provider === 'openrouter') {
            $request = $request
                ->withHeaders([
                    'HTTP-Referer' => config('app.url'),
                    'X-Title' => 'KalanNet KalanBot',
                ]);
        }

        $response = $request->post($config['endpoint'], [
            'model' => $config['model'],
            'messages' => $messages,
            'temperature' => 0.3,
            'max_tokens' => 700,
        ]);

        if (!$response->successful()) {
            report(new \RuntimeException("{$provider} assistant error: " . $response->body()));
            return $this->unavailableMessage();
        }

        return trim((string) data_get($response->json(), 'choices.0.message.content')) ?: $this->unavailableMessage();
    }

    private function chatOllama(array $messages): string
    {
        $response = Http::timeout($this->timeout())
            ->post(config('services.assistant.ollama.endpoint'), [
                'model' => config('services.assistant.ollama.model', 'llama3'),
                'stream' => false,
                'messages' => $messages,
                'options' => ['temperature' => 0.3],
            ]);

        if (!$response->successful()) {
            report(new \RuntimeException('Ollama assistant error: ' . $response->body()));
            return $this->unavailableMessage();
        }

        return trim((string) data_get($response->json(), 'message.content')) ?: $this->unavailableMessage();
    }

    private function sanitizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email masque]', $text);
        $text = preg_replace('/\+?\d[\d\s().-]{7,}\d/', '[telephone masque]', $text);

        return Str::limit(trim((string) $text), 1800, '');
    }

    private function timeout(): int
    {
        return max(5, (int) config('services.assistant.timeout', 20));
    }

    private function missingProviderMessage(string $provider): string
    {
        return "KalanBot n'est pas encore configuré pour {$provider}. Ajoutez la clé API dans le fichier .env, puis réessayez.";
    }

    private function unavailableMessage(): string
    {
        return "KalanBot est momentanément indisponible. Réessayez dans quelques instants ou contactez l'administration si la question est urgente.";
    }
}
