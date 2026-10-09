<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_cannot_change_maintenance_mode(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);

        $this->actingAs($employee)->get(route('maintenance.edit'))->assertForbidden();
        $this->actingAs($employee)->post(route('maintenance.store'))->assertForbidden();
        $this->actingAs($employee)->delete(route('maintenance.destroy'))->assertForbidden();

        $this->assertFalse($boss->organization->fresh()->maintenance_enabled);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'maintenance.enabled']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'maintenance.disabled']);
    }

    public function test_an_authorized_boss_can_enable_and_disable_maintenance(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $secondary = User::factory()->bossSecondaire()->create([
            'organization_id' => $boss->organization_id,
        ]);
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);

        $this->actingAs($employee)->get('/dashboard')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Dashboard')
        );

        $this->actingAs($boss)
            ->get(route('maintenance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Maintenance/Edit')
                ->where('enabled', false)
            );

        $this->actingAs($boss)
            ->post(route('maintenance.store'))
            ->assertRedirect(route('maintenance.edit'))
            ->assertSessionHas('status', 'Mode maintenance activé.');

        $this->actingAs($boss)->post(route('maintenance.store'))->assertRedirect();
        $this->assertSame(1, AuditLog::query()->where('action', 'maintenance.enabled')->count());

        $enabled = AuditLog::query()->where('action', 'maintenance.enabled')->first();
        $this->assertNotNull($enabled);
        $this->assertSame($boss->id, $enabled->user_id);
        $this->assertSame('success', $enabled->result);
        $this->assertNotNull($enabled->created_at);
        $this->assertTrue($boss->organization->fresh()->maintenance_enabled);

        $this->actingAs($employee)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Maintenance/Blocked'));

        $this->actingAs($employee)
            ->getJson('/api/me')
            ->assertStatus(503)
            ->assertJson(['message' => 'L’application est en maintenance.']);

        $this->actingAs($employee)
            ->delete(route('maintenance.destroy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Maintenance/Blocked'));
        $this->assertTrue($boss->organization->fresh()->maintenance_enabled);

        $this->get('/login')->assertOk();

        $this->actingAs($employee)->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->actingAs($boss)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));

        $this->actingAs($boss)
            ->get(route('maintenance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('enabled', true));

        $this->actingAs($secondary)
            ->delete(route('maintenance.destroy'))
            ->assertRedirect(route('maintenance.edit'))
            ->assertSessionHas('status', 'Mode maintenance désactivé.');

        $this->assertFalse($boss->organization->fresh()->maintenance_enabled);

        $disabled = AuditLog::query()->where('action', 'maintenance.disabled')->first();
        $this->assertNotNull($disabled);
        $this->assertSame($secondary->id, $disabled->user_id);
        $this->assertSame('success', $disabled->result);

        $this->actingAs($employee)->get('/dashboard')->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Dashboard')
        );

        $this->assertSame(0, AuditLog::query()->where('action', 'user.suspended')->count());
    }
}
