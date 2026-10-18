<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Support\SubventionEtat;
use PHPUnit\Framework\TestCase;

class ClasseResetPermissionTest extends TestCase
{
    public function test_class_reset_permission_is_in_catalog_but_never_default_granted(): void
    {
        $permission = 'classes_reinitialiser_eleves';

        $this->assertContains($permission, Permission::catalogNames());
        $this->assertContains($permission, Permission::JAMAIS_PAR_DEFAUT);
        $this->assertSame('classes_cours', Permission::catalogEntry($permission)['module']);
        $this->assertTrue(Permission::accessiblePourRole($permission, 'Admin'));
        $this->assertFalse(Permission::accessiblePourRole($permission, 'Gestionnaire'));
    }

    public function test_state_grant_is_split_evenly_with_only_cent_precision_difference(): void
    {
        $parts = SubventionEtat::repartirEgalement(100, 3);

        $this->assertSame([3334, 3333, 3333], $parts);
        $this->assertSame(10000, array_sum($parts));

        $nextParts = SubventionEtat::repartirEgalement(100, 3, $parts);
        $this->assertSame([3333, 3334, 3333], $nextParts);
        $this->assertSame(10000, array_sum($nextParts));
    }
}