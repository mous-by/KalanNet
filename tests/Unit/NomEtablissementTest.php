<?php

namespace Tests\Unit;

use App\Support\NomEtablissement;
use PHPUnit\Framework\TestCase;

class NomEtablissementTest extends TestCase
{
    public function test_le_prefixe_n_est_ajoute_que_si_le_nom_ne_dit_pas_deja_ce_qu_est_l_ecole(): void
    {
        $this->assertSame('ECOLE PRIVÉE AMINATA FOFANA', NomEtablissement::prefixer('École fondamentale : ', 'ECOLE PRIVÉE AMINATA FOFANA'));
        $this->assertSame('École fondamentale : Aminata Fofana', NomEtablissement::prefixer('École fondamentale : ', 'Aminata Fofana'));
        $this->assertSame('Lycée Askia Mohamed', NomEtablissement::prefixer('Lycée : ', 'Lycée Askia Mohamed'));
        $this->assertSame('Collège Sainte-Marie', NomEtablissement::prefixer('École fondamentale : ', 'Collège Sainte-Marie'));
        $this->assertSame('Complexe scolaire Les Pionniers', NomEtablissement::prefixer('Complexe scolaire ', 'Complexe scolaire Les Pionniers'));
        $this->assertSame('Lycée : Ecolemania', NomEtablissement::prefixer('Lycée : ', 'Ecolemania'));
    }
}
