<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'permission:update_prices'])
            ->get('/_authorization/prices', fn () => response('authorized', 200));

        Route::middleware(['auth', 'permission:cancel_sales'])
            ->get('/_authorization/sales/cancel', fn () => response('authorized', 200));
    }

    public function test_an_employee_cannot_update_prices_or_cancel_sales(): void
    {
        $employee = User::factory()->employe()->create();

        $this->assertFalse($employee->can(Permission::UpdatePrices->value));
        $this->assertFalse($employee->can(Permission::CancelSales->value));
        $this->assertFalse($employee->can(Permission::ValidateStockReceipts->value));
        $this->assertTrue($employee->can(Permission::CreateSales->value));

        $this->actingAs($employee)->get('/_authorization/prices')->assertForbidden();
        $this->actingAs($employee)->get('/_authorization/sales/cancel')->assertForbidden();
        $this->actingAs($employee)->getJson('/_authorization/prices')->assertForbidden();
    }

    public function test_a_principal_boss_can_update_prices_and_cancel_sales(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->assertTrue($boss->can(Permission::UpdatePrices->value));
        $this->assertTrue($boss->can(Permission::CancelSales->value));
        $this->assertTrue($boss->can(Permission::InviteBoss->value));

        $this->actingAs($boss)->get('/_authorization/prices')->assertOk();
        $this->actingAs($boss)->get('/_authorization/sales/cancel')->assertOk();
    }

    public function test_a_secondary_boss_cannot_invite_another_boss(): void
    {
        $boss = User::factory()->bossSecondaire()->create();

        $this->assertFalse($boss->can(Permission::InviteBoss->value));
        $this->assertTrue($boss->can(Permission::UpdatePrices->value));
    }
}
