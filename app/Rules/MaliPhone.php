<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a Mali phone number.
 * Accepted formats: 76123456 | +22376123456 | 0022376123456
 * Rules: 8 local digits, first digit 2-9, with optional +223 / 00223 prefix.
 */
class MaliPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleaned = preg_replace('/[\s\-\.]/', '', (string) $value);

        // Strip country code if present
        if (str_starts_with($cleaned, '+223')) {
            $cleaned = substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '00223')) {
            $cleaned = substr($cleaned, 5);
        }

        // Must be exactly 8 digits
        if (!preg_match('/^[0-9]{8}$/', $cleaned)) {
            $fail('Le numéro de téléphone malien doit contenir 8 chiffres (ex : 76 12 34 56 ou +223 76 12 34 56).');
            return;
        }

        // First digit must be 2–9 (valid Mali prefix range)
        if ((int) $cleaned[0] < 2) {
            $fail('Le numéro de téléphone malien est invalide (le préfixe ne correspond à aucun opérateur connu).');
        }
    }

    /** Normalize a raw input to the local 8-digit format. */
    public static function normalize(string $value): string
    {
        $cleaned = preg_replace('/[\s\-\.]/', '', $value);
        if (str_starts_with($cleaned, '+223'))  return substr($cleaned, 4);
        if (str_starts_with($cleaned, '00223')) return substr($cleaned, 5);
        return $cleaned;
    }
}
