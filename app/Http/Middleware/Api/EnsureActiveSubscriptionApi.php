<?php

namespace App\Http\Middleware\Api;

use App\Support\Api\Authorizer;
use App\Support\SubscriptionGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscriptionApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $ecoleId = Authorizer::resolveEcoleId($user, $request);

        if (!SubscriptionGate::isBlockedForUser($user, $ecoleId)) {
            return $next($request);
        }

        return response()->json([
            'message' => "Votre abonnement a expiré. Veuillez renouveler l'abonnement pour continuer.",
            'subscription_blocked' => true,
        ], 403);
    }
}
