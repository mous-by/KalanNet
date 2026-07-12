<?php

namespace App\Services\Kalanbot;

use App\Models\KalanbotActionLog;
use App\Models\User;
use App\Services\Kalanbot\Contracts\KalanbotToolInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Orchestration de KalanBot en mode agent : c'est Gemini qui décide, via function
 * calling, si un message est une simple question (réponse texte) ou une instruction
 * (appel d'outil). Les actions d'écriture ne sont jamais exécutées directement :
 * elles sont reformulées et attendent un "oui" explicite avant exécution.
 */
class KalanbotAgentService
{
    private const MODULE_ROUTING_THRESHOLD = 40;
    private const CONFIRMATION_TTL = 300;

    public function __construct(private ToolRegistry $registry)
    {
    }

    public function handle(User $user, string $message, array $history, array $context): array
    {
        $pendingKey = $this->pendingKey($user);
        $pending = Cache::get($pendingKey);

        if ($pending && (int) ($pending['stage'] ?? 1) === 2) {
            Cache::forget($pendingKey);

            if ($this->interpretStrictConfirmation($message)) {
                return $this->runTool($pending['tool'], $pending['args'], $user);
            }

            return ['reply' => "Action annulée. Aucune modification n'a été effectuée."];
        }

        if ($pending) {
            $decision = $this->interpretConfirmation($message);
            Cache::forget($pendingKey);

            if ($decision === true) {
                $tool = $this->registry->find($pending['tool']);
                if ($tool && $tool->requiresDoubleConfirmation()) {
                    Cache::put($pendingKey, ['tool' => $pending['tool'], 'args' => $pending['args'], 'stage' => 2], self::CONFIRMATION_TTL);

                    return [
                        'reply' => "⚠️ Confirmation finale requise : cette action est irréversible et ne pourra pas être annulée. "
                            . "Répondez exactement « CONFIRMER » pour continuer, ou « non » pour annuler.",
                        'requires_confirmation' => true,
                    ];
                }

                return $this->runTool($pending['tool'], $pending['args'], $user);
            }
            if ($decision === false) {
                return ['reply' => 'Action annulée. Que puis-je faire pour vous ?'];
            }
            // Message ambigu pendant une confirmation en attente : on abandonne
            // l'action en attente (jamais exécutée sans "oui" explicite) et on
            // traite le nouveau message normalement.
        }

        try {
            $result = $this->callGeminiWithTools($user, $message, $history, $context);
        } catch (\Throwable $e) {
            report($e);

            return ['reply' => $this->unavailableMessage()];
        }

        if (!$result['functionCall']) {
            return ['reply' => $result['text'] !== '' ? $result['text'] : $this->unavailableMessage()];
        }

        $toolName = (string) ($result['functionCall']['name'] ?? '');
        $args = (array) ($result['functionCall']['args'] ?? []);
        $tool = $this->registry->find($toolName);

        if (!$tool) {
            return ['reply' => "Je ne sais pas encore effectuer cette action."];
        }

        if (!$tool->authorize($user)) {
            $this->log($user, $tool, $args, 'denied', "Permission insuffisante.");

            return ['reply' => "Vous n'avez pas la permission d'effectuer cette action."];
        }

        if (!$tool->requiresConfirmation()) {
            return $this->runTool($toolName, $args, $user);
        }

        try {
            $confirmationMessage = $tool->confirmationMessage($args, $user);
        } catch (\Throwable $e) {
            report($e);

            return ['reply' => "Je n'ai pas pu préparer cette action : informations manquantes ou invalides."];
        }

        Cache::put($pendingKey, ['tool' => $toolName, 'args' => $args, 'stage' => 1], self::CONFIRMATION_TTL);

        return ['reply' => $confirmationMessage, 'requires_confirmation' => true];
    }

    private function runTool(string $toolName, array $args, User $user): array
    {
        $tool = $this->registry->find($toolName);
        if (!$tool || !$tool->authorize($user)) {
            return ['reply' => "Vous n'avez pas la permission d'effectuer cette action."];
        }

        try {
            $outcome = $tool->execute($args, $user);
        } catch (\Throwable $e) {
            report($e);
            $this->log($user, $tool, $args, 'error', $e->getMessage());

            return ['reply' => "Une erreur est survenue pendant l'exécution de cette action."];
        }

        $status = ($outcome['success'] ?? false) ? 'success' : 'error';
        $message = $outcome['message'] ?? ($status === 'success' ? 'Action effectuée.' : "L'action a échoué.");
        $this->log($user, $tool, $args, $status, $message, $outcome['data'] ?? null);

        return ['reply' => $message];
    }

    private function callGeminiWithTools(User $user, string $message, array $history, array $context): array
    {
        $key = config('services.assistant.gemini.key');
        if (!$key) {
            return ['text' => "KalanBot n'est pas encore configuré pour Gemini. Ajoutez la clé API dans le fichier .env.", 'functionCall' => null];
        }

        $authorizedTools = $this->registry->authorizedFor($user);
        $tools = $authorizedTools;

        if ($authorizedTools->count() > self::MODULE_ROUTING_THRESHOLD) {
            $module = $this->identifyModule($user, $message, $history);
            $tools = $module ? $this->registry->authorizedForModule($user, $module) : $authorizedTools;
        }

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $this->systemPrompt($context, $tools)]]],
            'contents' => $this->buildContents($history, $message),
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 1024,
                // Le "thinking" de Gemini 2.5 consomme le budget de tokens de sortie et peut
                // tronquer la réponse (finishReason=MAX_TOKENS) ; inutile pour du function calling.
                'thinkingConfig' => ['thinkingBudget' => 0],
            ],
        ];

        $declarations = $this->registry->declarationsFor($tools);
        if (!empty($declarations)) {
            $payload['tools'] = [['functionDeclarations' => $declarations]];
        }

        $response = Http::timeout($this->timeout())
            ->withHeaders(['x-goog-api-key' => $key])
            ->post($this->endpoint('generateContent'), $payload);

        if (!$response->successful()) {
            report(new \RuntimeException('Gemini agent error: ' . $response->body()));

            return ['text' => $this->unavailableMessage(), 'functionCall' => null];
        }

        $parts = (array) data_get($response->json(), 'candidates.0.content.parts', []);
        $text = trim(collect($parts)->pluck('text')->filter()->implode("\n"));
        $functionCall = collect($parts)->pluck('functionCall')->filter()->first();

        return ['text' => $text, 'functionCall' => $functionCall];
    }

    /** Étape 1 du routage à 2 niveaux (>40 outils) : identifie le module concerné avant d'y envoyer les outils. */
    private function identifyModule(User $user, string $message, array $history): ?string
    {
        $modules = $this->registry->modulesFor($user);
        $key = config('services.assistant.gemini.key');
        if (empty($modules) || !$key) {
            return null;
        }

        $prompt = "Identifie le module fonctionnel de KalanNet concerné par le dernier message de l'utilisateur, "
            . "parmi cette liste stricte : " . implode(', ', $modules) . ". "
            . "Réponds uniquement par le nom exact d'un module de la liste, sans aucun autre texte, ou par 'aucun' "
            . "si le message ne correspond à aucune action précise.";

        $response = Http::timeout($this->timeout())
            ->withHeaders(['x-goog-api-key' => $key])
            ->post($this->endpoint('generateContent'), [
                'systemInstruction' => ['parts' => [['text' => $prompt]]],
                'contents' => $this->buildContents($history, $message),
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => 20,
                    'thinkingConfig' => ['thinkingBudget' => 0],
                ],
            ]);

        if (!$response->successful()) {
            return null;
        }

        $normalized = Str::lower(trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text')));

        return in_array($normalized, $modules, true) ? $normalized : null;
    }

    private function systemPrompt(array $context, Collection $tools): string
    {
        $route = $this->sanitizeText((string) ($context['route'] ?? ''));
        $path = $this->sanitizeText((string) ($context['path'] ?? ''));
        $title = $this->sanitizeText((string) ($context['title'] ?? ''));

        $toolsList = $tools->isEmpty()
            ? "Aucun outil n'est disponible pour cet utilisateur : réponds uniquement en mode explicatif, sans jamais prétendre avoir exécuté une action."
            : $tools->map(fn (KalanbotToolInterface $t) => "- {$t->name()} : {$t->description()}")->implode("\n");

        return <<<PROMPT
Tu es KalanBot, l'assistant officiel de KalanNet, une application de gestion scolaire multi-école.
Tu réponds toujours en français sauf si l'utilisateur écrit dans une autre langue.
Tu as DEUX modes de réponse, et c'est à toi de choisir le bon selon le message de l'utilisateur :

1. MODE EXPLICATIF : si l'utilisateur pose une question générale ou demande comment faire quelque chose
   ("comment inscrire un élève ?", "où sont les paiements ?"), réponds en texte simple, professionnel,
   précis et bienveillant. N'appelle aucun outil dans ce cas.

2. MODE AGENT : si l'utilisateur donne une instruction claire correspondant à un outil listé ci-dessous
   ("mets 15/20 à Aïcha en maths", "programme une évaluation de français pour la 6ème A"), appelle l'outil
   correspondant avec les arguments déduits de la conversation.
   - Si une information obligatoire manque ou est ambiguë (par exemple plusieurs élèves possibles, ou un
     identifiant de classe inconnu), pose une question de clarification en texte au lieu d'appeler l'outil.
   - N'invente JAMAIS un identifiant numérique (id_classe, id_evaluation, id_ligneEvaluation...) : utilise
     toujours un outil de lecture pour le retrouver d'abord si tu ne le connais pas déjà avec certitude.
   - Les actions d'écriture sont reformulées et confirmées automatiquement par l'application après ton appel
     d'outil : tu n'as pas besoin de demander toi-même une confirmation dans ta réponse.

Outils disponibles :
{$toolsList}

Contexte de navigation non sensible :
- Page : {$title}
- Route : {$route}
- Chemin : {$path}

Règles impératives :
- Tu ne réponds qu'aux questions et actions liées à KalanNet et à la gestion scolaire.
- Ne demande jamais de mot de passe ou de données bancaires complètes.
PROMPT;
    }

    private function buildContents(array $history, string $message): array
    {
        $maxHistory = max(0, (int) config('services.assistant.max_history', 8));

        $trimmed = collect($history)
            ->take(-$maxHistory)
            ->map(fn ($item) => [
                'role' => ($item['role'] ?? '') === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $this->sanitizeText((string) ($item['content'] ?? ''))]],
            ])
            ->filter(fn ($item) => filled($item['parts'][0]['text']))
            ->values()
            ->all();

        return array_merge($trimmed, [
            ['role' => 'user', 'parts' => [['text' => $this->sanitizeText($message)]]],
        ]);
    }

    private function interpretConfirmation(string $message): ?bool
    {
        $normalized = trim(Str::ascii(Str::lower($message)));

        $affirmative = ['oui', 'ok', 'okay', "d'accord", 'daccord', 'confirme', 'confirmer', 'je confirme', 'vas-y', 'vas y', 'yes', 'valide'];
        $negative = ['non', 'annule', 'annuler', 'stop', 'no', 'pas maintenant', 'laisse tomber', 'annulation'];

        foreach ($affirmative as $word) {
            if ($normalized === $word || str_starts_with($normalized, $word . ' ')) {
                return true;
            }
        }

        foreach ($negative as $word) {
            if ($normalized === $word || str_starts_with($normalized, $word . ' ')) {
                return false;
            }
        }

        return null;
    }

    /** Confirmation finale (stage 2) pour les suppressions/actions de masse : mot-clé strict requis. */
    private function interpretStrictConfirmation(string $message): bool
    {
        $normalized = trim(Str::ascii(Str::lower($message)));

        return (bool) preg_match('/\bconfirmer\b/', $normalized);
    }

    private function log(User $user, KalanbotToolInterface $tool, array $args, string $status, ?string $message, mixed $data = null): void
    {
        try {
            KalanbotActionLog::create([
                'user_id' => $user->idUtilisateur,
                'id_ecole' => session('idEcole') ?: $user->idEcole,
                'module' => $tool->module(),
                'tool_name' => $tool->name(),
                'arguments' => $args,
                'status' => $status,
                'message' => $message,
                'result_data' => $data,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function pendingKey(User $user): string
    {
        return 'kalanbot:pending:' . $user->idUtilisateur;
    }

    private function endpoint(string $method): string
    {
        $endpoint = rtrim((string) config('services.assistant.gemini.endpoint'), '/');
        $model = config('services.assistant.gemini.model', 'gemini-2.5-flash');

        return "{$endpoint}/models/{$model}:{$method}";
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

    private function unavailableMessage(): string
    {
        return "KalanBot est momentanément indisponible. Réessayez dans quelques instants ou contactez l'administration si la question est urgente.";
    }
}
