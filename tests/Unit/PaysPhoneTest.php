<?php

namespace Tests\Unit;

use App\Models\Pays;
use App\Rules\PaysPhone;
use App\Support\Telephone;
use Tests\TestCase;

/**
 * Validation des numéros selon le pays de l'école (App\Rules\PaysPhone, qui a
 * remplacé MaliPhone). Pays construit en mémoire : aucune base de données.
 */
class PaysPhoneTest extends TestCase
{
    private function mali(): Pays
    {
        // Mêmes règles que le repli Devise::mali().
        return new Pays([
            'nom' => 'Mali', 'code_iso' => 'ML', 'indicatif_telephone' => '+223',
            'telephone_longueur' => 8, 'telephone_premier_chiffre_min' => 2,
        ]);
    }

    private function refuse(string $numero): bool
    {
        $refuse = false;
        (new PaysPhone($this->mali()))->validate('telephone', $numero, function () use (&$refuse) {
            $refuse = true;
        });

        return $refuse;
    }

    public function test_accepte_les_formats_maliens_courants(): void
    {
        foreach (['67 20 57 36', '67205736', '+223 67 20 57 36', '00223 67 20 57 36', '67-20-57-36', '67.20.57.36'] as $numero) {
            $this->assertFalse($this->refuse($numero), "$numero devrait être accepté.");
        }
    }

    public function test_normalise_au_format_local_a_huit_chiffres(): void
    {
        $this->assertSame('67205736', Telephone::normalize('67 20 57 36', $this->mali()));
        $this->assertSame('67205736', Telephone::normalize('+223 67 20 57 36', $this->mali()));
        $this->assertSame('67205736', Telephone::normalize('00223 67-20-57-36', $this->mali()));
    }

    public function test_refuse_les_numeros_invalides(): void
    {
        foreach (['17205736', '6720573', '+224 67 20 57 36', '67 20 57 36 99'] as $numero) {
            $this->assertTrue($this->refuse($numero), "$numero devrait être refusé.");
        }
    }
}
