<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use App\Services\Kalanbot\KalanbotAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AssistantService $assistant, KalanbotAgentService $agent): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|max:1200',
            'history' => 'nullable|array',
            'history.*.role' => 'required_with:history|string|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:1200',
            'context' => 'nullable|array',
            'context.route' => 'nullable|string|max:120',
            'context.path' => 'nullable|string|max:255',
            'context.title' => 'nullable|string|max:160',
        ]);

        // Le mode agent (function calling) n'est câblé que pour Gemini pour l'instant ;
        // les autres fournisseurs conservent le mode explicatif d'origine.
        $provider = strtolower((string) config('services.assistant.provider', 'gemini'));

        try {
            if ($provider === 'gemini' && $request->user()) {
                // Les appels Gemini en mode agent peuvent enchaîner un appel de routage
                // par module puis l'exécution : on élargit temporairement le budget PHP.
                if (function_exists('set_time_limit')) {
                    @set_time_limit(45);
                }

                $result = $agent->handle(
                    $request->user(),
                    $data['message'],
                    $data['history'] ?? [],
                    $data['context'] ?? []
                );

                return response()->json($result);
            }

            $reply = $assistant->reply(
                $data['message'],
                $data['history'] ?? [],
                $data['context'] ?? []
            );

            return response()->json(['reply' => $reply]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'reply' => "KalanBot est momentanément indisponible. Réessayez dans quelques instants.",
            ], 200);
        }
    }
}
