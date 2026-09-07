<?php

namespace App\Support\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Authorizer
{
    public static function ensurePermission(User $user, string $permission): void
    {
        if ($user->droit === 'SupAdmin' || $user->userHasPermission($permission)) {
            return;
        }

        throw new HttpException(403, 'Permission insuffisante.');
    }

    public static function ensureAnyPermission(User $user, array $permissions): void
    {
        if ($user->droit === 'SupAdmin' || $user->userHasAnyPermission($permissions)) {
            return;
        }

        throw new HttpException(403, 'Permission insuffisante.');
    }

    /**
     * Resolve which "idEcole" a request should be scoped to.
     *
     * Admin/Gestionnaire/enseignant/parent are always scoped to their own
     * school (mirrors the fallback already built into BelongsToSchool).
     * SupAdmin/DAE/DCAP supervise multiple schools, so they must pass an
     * explicit `ecole_id` to target one (same pattern already used by the
     * web SupAdmin filters) — null means "no single school selected", which
     * callers can treat as "use the multi-school scope" where relevant.
     */
    public static function resolveEcoleId(User $user, Request $request): ?int
    {
        if (in_array($user->droit, ['SupAdmin', 'DAE', 'DCAP'], true)) {
            return $request->integer('ecole_id') ?: null;
        }

        if ($user->idEcole) {
            return $user->idEcole;
        }

        $user->loadMissing(['enseignant', 'parent']);

        return $user->enseignant?->id_ecole ?: $user->parent?->idEcole ?: null;
    }
}
