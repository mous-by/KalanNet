<?php

namespace Tests\Unit;

use App\Support\ClasseSuivante;
use PHPUnit\Framework\TestCase;

class ClasseSuivanteGroupeTest extends TestCase
{
    public function test_le_groupe_ignore_le_numero_et_sa_forme(): void
    {
        foreach (['7ème année B', '8e B', '8eme annee  b', '10ème B', '1ère année B', '8 B'] as $nom) {
            $this->assertSame('b', ClasseSuivante::groupe($nom), $nom);
        }
        $this->assertSame('', ClasseSuivante::groupe('7ème année'));
        $this->assertSame('ll', ClasseSuivante::groupe('10e LL'));
    }
}
