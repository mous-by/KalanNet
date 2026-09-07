<?php

namespace App\Support;

use App\Models\Abonnement;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class SubscriptionGate
{
    public static function isBlocked(int $ecoleId): bool
    {
        if ($ecoleId <= 0 || !Schema::hasTable('abonnements')) {
            return false;
        }

        // Licence à vie (offre ACHAT) : abonnement actif SANS date de fin => jamais bloqué.
        $hasLifetime = Abonnement::query()
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'actif')
            ->whereNull('fin_at')
            ->exists();

        if ($hasLifetime) {
            return false;
        }

        $subscription = Abonnement::query()
            ->where('ecole_id', $ecoleId)
            ->where('statut', 'actif')
            ->whereNotNull('fin_at')
            ->orderByDesc('fin_at')
            ->first();

        return !$subscription || $subscription->fin_at->copy()->endOfDay()->isPast();
    }

    public static function isBlockedForUser(User $user, ?int $ecoleId): bool
    {
        if (!$ecoleId || in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true)) {
            return false;
        }

        return self::isBlocked($ecoleId);
    }
}
