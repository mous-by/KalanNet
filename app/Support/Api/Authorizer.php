<?php

namespace App\Support\Api;

use App\Models\Ecole;
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
        if ($user->droit === 'SupAdmin') {
            return $request->integer('ecole_id') ?: null;
        }

        // DAE / DCAP : seulement une école de leur territoire (académie ou CAP).
        if (in_array($user->droit, ['DAE', 'DCAP'], true)) {
            $idEcole = $request->integer('ecole_id') ?: null;
            if ($idEcole !== null) {
                $colonne = $user->droit === 'DAE' ? 'id_academie' : 'id_cap';
                $territoire = $user->droit === 'DAE' ? $user->id_academie : $user->id_cap;
                $dansTerritoire = $territoire && Ecole::withoutGlobalScopes()->whereKey($idEcole)->where($colonne, $territoire)->exists();
                if (!$dansTerritoire) {
                    throw new HttpException(403, 'Cette école ne fait pas partie de votre territoire.');
                }
            }

            return $idEcole;
        }

        if ($user->idEcole) {
            return $user->idEcole;
        }

        $user->loadMissing(['enseignant', 'parent']);

        return $user->enseignant?->id_ecole ?: $user->parent?->idEcole ?: null;
    }
}
