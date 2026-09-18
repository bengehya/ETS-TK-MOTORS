<?php

namespace Tests\Unit;

use App\Authorization\RolePermissions;
use App\Enums\Permission;
use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RolePermissionsTest extends TestCase
{
    public function test_employees_never_receive_restricted_boss_permissions(): void
    {
        $this->assertFalse(RolePermissions::allows(Role::Employe, Permission::UpdatePrices));
        $this->assertFalse(RolePermissions::allows(Role::Employe, Permission::CancelSales));
        $this->assertFalse(RolePermissions::allows(Role::Employe, Permission::ValidateStockReceipts));
        $this->assertFalse(RolePermissions::allows(Role::Employe, Permission::ViewAudit));
        $this->assertTrue(RolePermissions::allows(Role::Employe, Permission::CreateSales));
    }

    public function test_the_principal_boss_can_invite_another_boss(): void
    {
        $this->assertTrue(RolePermissions::allows(Role::BossPrincipal, Permission::InviteBoss));
        $this->assertFalse(RolePermissions::allows(Role::BossSecondaire, Permission::InviteBoss));
    }
}
