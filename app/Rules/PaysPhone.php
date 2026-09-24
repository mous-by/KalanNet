<?php

namespace App\Rules;

use App\Models\Ecole;
use App\Models\Pays;
use App\Support\Telephone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Remplace App\Rules\MaliPhone : valide un numero selon le format du pays
 * de l'ecole donnee (repli sur le Mali si aucune ecole n'est fournie, pour
 * ne rien casser la ou le contexte n'est pas encore disponible).
 */
class PaysPhone implements ValidationRule
{
    public function __construct(protected Ecole|Pays|int|null $ecole = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        Telephone::validate($attribute, $value, $fail, $this->ecole);
    }
}
