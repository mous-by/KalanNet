<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Ecole;
use App\Models\User;
use App\Rules\MaliPhone;
use App\Support\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'pwd' => ['required'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'identifier.required' => 'Veuillez saisir votre email ou votre numéro de téléphone.',
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Trop de tentatives. Réessayez plus tard.',
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        $identifier = trim($credentials['identifier']);
        $phoneIdentifier = MaliPhone::normalize($identifier);

        $users = User::with(['ecole' => function ($q) {
            $q->withoutGlobalScopes();
        }])
            ->where(function ($query) use ($identifier, $phoneIdentifier) {
                $query->where('email', $identifier)
                    ->orWhere('telephone', $phoneIdentifier);
            })
            ->get();

        $users = $users->filter(fn (User $user) => Hash::check($credentials['pwd'], $user->pwd))->values();

        if ($users->isEmpty()) {
            RateLimiter::hit($throttleKey, 180);

            return response()->json([
                'message' => 'Identifiant ou mot de passe incorrect.',
            ], 401);
        }

        if ($users->count() > 1) {
            RateLimiter::clear($throttleKey);

            return response()->json([
                'requires_school_selection' => true,
                'accounts' => $users->map(fn (User $user) => [
                    'id_utilisateur' => $user->idUtilisateur,
                    'id_ecole' => $user->idEcole,
                    'nom_ecole' => $user->ecole->nomEcole ?? null,
                    'droit' => $user->droit,
                ])->all(),
            ]);
        }

        $user = $users[0];

        if ((int) $user->statut === 0) {
            return response()->json([
                'message' => 'Ce compte est désactivé.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        return $this->issueToken($user, $request);
    }

    public function selectSchool(Request $request)
    {
        $request->validate([
            'id_utilisateur' => 'required|exists:utilisateurs,idUtilisateur',
            'id_ecole' => 'required|exists:ecole,idEcole',
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::withoutGlobalScopes()
            ->where('idUtilisateur', $request->input('id_utilisateur'))
            ->where('idEcole', $request->input('id_ecole'))
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Sélection invalide.'], 422);
        }

        if ((int) $user->statut === 0) {
            return response()->json(['message' => 'Ce compte est désactivé.'], 403);
        }

        return $this->issueToken($user, $request);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->loadMissing(['ecole' => fn ($q) => $q->withoutGlobalScopes()]);

        $ecoleId = $user->idEcole;

        return response()->json([
            'user' => new UserResource($user),
            'subscription_blocked' => SubscriptionGate::isBlockedForUser($user, $ecoleId),
        ]);
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate([
            'theme' => 'required|string|in:bleu-sombre,light,dark,vert,violet,rouge,orange,ocean,ambre,ardoise,brume',
        ]);

        $user = $request->user();
        $user->theme_preference = $data['theme'];
        $user->save();

        return response()->json(['user' => new UserResource($user)]);
    }

    public function updateLocale(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.supported_locales', [])))],
        ]);

        $user = $request->user();
        $user->locale_preference = $data['locale'];
        $user->save();

        return response()->json(['user' => new UserResource($user)]);
    }

    private function issueToken(User $user, Request $request)
    {
        $user->last_login_at = now();
        $user->last_activity = now();
        $user->save();

        $user->loadMissing(['ecole' => fn ($q) => $q->withoutGlobalScopes(), 'enseignant', 'parent']);
        $ecoleId = $user->idEcole ?: $user->enseignant?->id_ecole ?: $user->parent?->idEcole;

        if ($ecoleId && !$user->relationLoaded('ecole')) {
            $user->setRelation('ecole', Ecole::withoutGlobalScopes()->find($ecoleId));
        }

        $deviceName = $request->input('device_name', 'mobile');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
            'subscription_blocked' => SubscriptionGate::isBlockedForUser($user, $ecoleId),
        ]);
    }

    private function throttleKey(Request $request): string
    {
        $identifier = strtolower(trim((string) $request->input('identifier', '')));
        $normalized = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : MaliPhone::normalize($identifier);

        return 'api-login:' . $normalized . '|' . $request->ip();
    }
}
