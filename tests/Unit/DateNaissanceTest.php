<?php

namespace Tests\Unit;

use App\Support\DateNaissance;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DateNaissanceTest extends TestCase
{
    public function test_accepted_formats(): void
    {
        foreach ([
            '22/04/2009' => '2009-04-22',
            '2/4/2009' => '2009-04-02',
            '22-04-2009' => '2009-04-22',
            '22.04.2009' => '2009-04-22',
            '2009-04-22' => '2009-04-22',
            '22/avr/2009' => '2009-04-22',
            '22 avril 2009' => '2009-04-22',
            '22-Apr-2009' => '2009-04-22',
            '1er mai 2009' => '2009-05-01',
            '15/févr./2010' => '2010-02-15',
            'vers 2009' => 'vers 2009',
            'Né vers 2009' => 'vers 2009',
            'née vers 2009' => 'vers 2009',
            'v. 2009' => 'vers 2009',
            '2009' => 'vers 2009',
            '39925' => '2009-04-22', // date Excel
        ] as $saisie => $attendu) {
            $this->assertSame($attendu, DateNaissance::normaliser($saisie), $saisie);
        }

        $this->assertNull(DateNaissance::normaliser('  '));
    }

    public function test_invalid_dates_are_rejected(): void
    {
        foreach (['31/02/2009', 'vers 1850', '22/13/2009', 'demain', '01/01/' . (date('Y') + 1)] as $saisie) {
            try {
                DateNaissance::normaliser($saisie);
                $this->fail("« {$saisie} » aurait dû être refusée.");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_display(): void
    {
        $this->assertSame('22/04/2009', DateNaissance::afficher('2009-04-22'));
        $this->assertSame('Vers 2009', DateNaissance::afficher('vers 2009'));
        $this->assertSame('—', DateNaissance::afficher(null, '—'));
        $this->assertNull(DateNaissance::afficher('', null));
    }
}
