<?php

namespace App\Http\Middleware;

use App\Models\Abonnement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !$this->shouldCheckSubscription($request)) {
            return $next($request);
        }

        if (!$this->isSubscriptionBlocked((int) session('idEcole'))) {
            return $next($request);
        }

        $message = "Votre abonnement a expiré. Veuillez vous réabonner pour accéder à l'application.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'subscription_blocked' => true,
            ], 403);
        }

        if ($request->routeIs('dashboard')) {
            return $next($request);
        }

        return redirect()
            ->route('abonnements.index')
            ->with('error', $message);
    }

    private function shouldCheckSubscription(Request $request): bool
    {
        $user = auth()->user();

        if (!$user || in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true)) {
            return false;
        }

        if (!session()->has('idEcole') || !Schema::hasTable('abonnements')) {
            return false;
        }

        return !$request->routeIs(
            'login',
            'login.post',
            'login.select-school',
            'logout',
            'dashboard',
            'abonnements.index',
            'abonnements.paiements.manual',
            'abonnements.paiements.show'
        );
    }

    private function isSubscriptionBlocked(int $schoolId): bool
    {
        if ($schoolId <= 0) {
            return false;
        }

        $subscription = Abonnement::query()
            ->where('ecole_id', $schoolId)
            ->where('statut', 'actif')
            ->whereNotNull('fin_at')
            ->orderByDesc('fin_at')
            ->first();

        return !$subscription || $subscription->fin_at->copy()->endOfDay()->isPast();
    }
}
