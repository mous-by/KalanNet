<?php

namespace App\Support;

use App\Models\Ecole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SchoolOrderAccess
{
    public const ORDERS = [
        'fondamentale1' => 'Fondamentale I',
        'fondamentale2' => 'Fondamentale II',
        'secondairegenerale' => 'Secondaire Général',
        'secondairetechniqueetprofessionnel' => 'Secondaire Technique et Professionnel',
    ];

    public static function normalize(?string $order): ?string
    {
        $value = strtolower(trim((string) $order));
        $value = str_replace([' ', '-', '_', 'é', 'è', 'ê', 'à'], ['', '', '', 'e', 'e', 'e', 'a'], $value);

        return match ($value) {
            'fondamentale1', 'fondamentalei' => 'fondamentale1',
            'fondamentale2', 'fondamentaleii' => 'fondamentale2',
            'secondaire', 'secondairegeneral', 'secondairegenerale', 'lycee' => 'secondairegenerale',
            'technique', 'professionnel', 'professionnelle', 'secondairetechnique', 'secondairetechniqueetprofessionnel', 'secondairetechniqueetprofessionnelle' => 'secondairetechniqueetprofessionnel',
            default => $value ?: null,
        };
    }

    public static function normalizeMany(?array $orders): array
    {
        return collect($orders ?? [])
            ->map(fn ($order) => self::normalize($order))
            ->filter(fn ($order) => array_key_exists($order, self::ORDERS))
            ->unique()
            ->values()
            ->all();
    }

    public static function isComplex(?Ecole $school): bool
    {
        return strtolower(trim((string) ($school?->typeEcole ?? ''))) === 'complexe scolaire';
    }

    public static function unrestricted(User $user): bool
    {
        return in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP', 'Admin'], true);
    }

    public static function allowedOrders(User $user, ?Ecole $school = null): array
    {
        $school ??= $user->ecole;

        if (!self::isComplex($school) || self::unrestricted($user)) {
            return array_keys(self::ORDERS);
        }

        return self::normalizeMany($user->managed_orders ?? []);
    }

    public static function userNeedsOrderFilter(User $user, ?Ecole $school = null): bool
    {
        $school ??= $user->ecole;

        return self::isComplex($school) && !self::unrestricted($user);
    }

    public static function applyClassOrderFilter(Builder $query, User $user, ?Ecole $school = null, string $relation = 'classe'): Builder
    {
        if (!self::userNeedsOrderFilter($user, $school)) {
            return $query;
        }

        $orders = self::allowedOrders($user, $school);

        if (empty($orders)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas($relation, function (Builder $classQuery) use ($orders) {
            $classQuery->whereIn('ordreEnseignement', $orders);
        });
    }

    public static function applyClasseFilter(Builder $query, User $user, ?Ecole $school = null): Builder
    {
        if (!self::userNeedsOrderFilter($user, $school)) {
            return $query;
        }

        $orders = self::allowedOrders($user, $school);

        if (empty($orders)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('ordreEnseignement', $orders);
    }
}
