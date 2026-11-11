<?php

namespace Tests\Unit;

use App\Support\RapportSuccinctDonnees as Donnees;
use PHPUnit\Framework\TestCase;

/** Série d'une classe du général : le niveau écrit dans le nom l'emporte sur la série choisie (rapports du secondaire, T9). */
class SerieClasseTest extends TestCase
{
    public function test_une_11e_n_est_jamais_prise_pour_une_terminale(): void
    {
        // « LL (Langues et Littérature) » est la terminale TLL ; choisie pour une 11e Lettres, la classe reste une 11e.
        $this->assertSame('11L', Donnees::serie('11ème Lettres', 'tll'));
        $this->assertSame('11L', Donnees::serie('11ème Lettres', 'lat'));
        $this->assertSame('11L', Donnees::serie('11ème LL'));
        $this->assertSame('11L', Donnees::serie('11ème Lettres', 'll'));
        $this->assertSame('11S', Donnees::serie('11ème Sciences', 'tse'));
        $this->assertSame('11SES', Donnees::serie('11ème SES', 'tseco'));
        $this->assertSame('11SES', Donnees::serie('11ème SES'));
    }

    public function test_terminales_et_10e(): void
    {
        foreach (['10eme année' => '10CG', '12ème année TSE' => 'TSE', 'TSexp' => 'TSEXP', 'Terminale TAL' => 'TAL', 'TSECO 2' => 'TSECO', 'TLL' => 'TLL', '12ème LL' => 'TLL', '12ème LAT' => 'TAL'] as $nom => $serie) {
            $this->assertSame($serie, Donnees::serie($nom), $nom);
        }
        $this->assertSame('TLL', Donnees::serie('Terminale', 'tll'));
        $this->assertSame('TSEXP', Donnees::serie('12ème Sciences', 'texp'));
        $this->assertNull(Donnees::serie('12ème année'));
    }
}
