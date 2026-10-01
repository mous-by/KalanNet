<?php

namespace App\Support;

use App\Models\Classe;

/**
 * La LV2 (deuxième langue vivante) n'existe qu'au secondaire (général ou
 * technique) : pas au fondamental, ni dans une École de Santé (classes sans
 * ordre d'enseignement, organisées par filière).
 */
class Lv2
{
    public static function classeConcernee(?Classe $classe): bool
    {
        $ordre = strtolower((string) $classe?->ordreEnseignement);

        return str_starts_with($ordre, 'secondaire') || $ordre === 'technique';
    }
}
