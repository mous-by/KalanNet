<?php

namespace Tests\Unit;

use App\Support\NomAcademie;
use PHPUnit\Framework\TestCase;

/** Préfixes retirés des noms d'académie et de CAP sur les documents (« CAP de Kayes » → « Kayes »). */
class NomAcademieTest extends TestCase
{
    public function test_cap_sans_prefixe(): void
    {
        $this->assertSame('Kayes Rives-Droite', NomAcademie::capSansPrefixe('CAP de Kayes Rives-Droite'));
        $this->assertSame('Ansongo', NomAcademie::capSansPrefixe('CAP d’Ansongo'));
        $this->assertSame('Ansongo', NomAcademie::capSansPrefixe("CAP d'Ansongo"));
        // Un nom qui commence par D ne perd pas sa première lettre.
        $this->assertSame('Diré', NomAcademie::capSansPrefixe('CAP Diré'));
        $this->assertSame('Douentza', NomAcademie::capSansPrefixe('CAP Douentza'));
        $this->assertSame('Kayes', NomAcademie::capSansPrefixe('Kayes'));
    }

    public function test_academie_sans_prefixe(): void
    {
        $this->assertSame('Ségou', NomAcademie::sansPrefixe('AE de Ségou'));
        $this->assertSame('Kayes', NomAcademie::sansPrefixe("Académie d'Enseignement de Kayes"));
    }
}
