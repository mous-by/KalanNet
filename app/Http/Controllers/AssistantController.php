<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AssistantService $assistant): JsonResponse
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

        try {
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
