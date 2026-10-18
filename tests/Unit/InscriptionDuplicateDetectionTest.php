<?php

namespace Tests\Unit;

use App\Http\Controllers\InscriptionController;
use Tests\TestCase;

class InscriptionDuplicateDetectionTest extends TestCase
{
    private function appeler(string $methode, array &$connus, ...$arguments)
    {
        $controller = app(InscriptionController::class);
        $method = new \ReflectionMethod($controller, $methode);

        return $methode === 'memoriserEleve'
            ? $method->invokeArgs($controller, [&$connus, ...$arguments])
            : $method->invoke($controller, $connus, ...$arguments);
    }

    private function identite(...$arguments): ?string
    {
        $controller = app(InscriptionController::class);

        return (new \ReflectionMethod($controller, 'cleIdentiteEleve'))->invoke($controller, ...$arguments);
    }

    public function test_identity_key_ignores_case_and_accents(): void
    {
        $this->assertSame('diallo|aminata|date:2012-02-03', $this->identite('Aminata', 'DIALLO', '2012-02-03', 'Féminin'));
        $this->assertSame('traore|amadou|genre:masculin', $this->identite('Amadou', 'Traoré', null, 'Masculin'));
    }

    public function test_duplicate_requires_same_identity_and_same_matricule(): void
    {
        $connus = [];
        $identite = $this->identite('Aminata', 'Diallo', '2012-02-03', 'Féminin');
        $this->appeler('memoriserEleve', $connus, $identite, ' AB-123 ');

        $this->assertTrue($this->appeler('estDoublon', $connus, $identite, 'ab-123'));
        $this->assertFalse($this->appeler('estDoublon', $connus, $identite, 'AB-999'));
        $this->assertFalse($this->appeler('estDoublon', $connus, $this->identite('Awa', 'Diallo', '2012-02-03', 'Féminin'), 'AB-123'));
    }

    public function test_row_without_matricule_is_compared_on_identity_only(): void
    {
        $connus = [];
        $identite = $this->identite('Aminata', 'Diallo', '2012-02-03', 'Féminin');
        $this->appeler('memoriserEleve', $connus, $identite, 'AB-123');

        $this->assertTrue($this->appeler('estDoublon', $connus, $identite, null));
    }
}
