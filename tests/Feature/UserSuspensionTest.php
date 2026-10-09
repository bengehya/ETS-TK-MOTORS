<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserSuspensionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suspended_user_cannot_continue_an_open_session_or_log_in(): void
    {
        config(['session.driver' => 'database']);

        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
            'remember_token' => 'remember-me',
        ]);
        $colleague = User::factory()->employe()->create([
            'organization_id' => $boss->organization_id,
        ]);

        AuditLog::query()->create([
            'organization_id' => $employee->organization_id,
            'user_id' => $employee->id,
            'actor_role' => Role::Employe->value,
            'action' => 'sale.recorded',
            'result' => 'success',
            'subject_type' => User::class,
            'subject_id' => $employee->id,
        ]);

        $this->actingAs($employee)->get('/dashboard')->assertOk();

        DB::table('sessions')->insert([
            'id' => 'session-employee',
            'user_id' => $employee->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        $this->actingAs($boss)
            ->from(route('users.invitations.index'))
            ->post(route('users.suspend', $employee))
            ->assertRedirect(route('users.invitations.index'))
            ->assertSessionHas('status', 'Compte suspendu.');

        $employee->refresh();
        $this->assertNotNull($employee->suspended_at);
        $this->assertSame($boss->id, $employee->suspended_by);
        $this->assertNull($employee->remember_token);
        $this->assertDatabaseMissing('sessions', ['user_id' => $employee->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale.recorded',
            'user_id' => $employee->id,
        ]);

        $suspension = AuditLog::query()->where('action', 'user.suspended')->first();
        $this->assertNotNull($suspension);
        $this->assertSame($boss->id, $suspension->user_id);
        $this->assertSame($employee->id, $suspension->subject_id);
        $this->assertSame('success', $suspension->result);
        $this->assertNotNull($suspension->created_at);

        $this->actingAs($employee)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Ce compte est suspendu.');
        $this->assertGuest();

        $this->actingAs($employee)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Ce compte est suspendu.']);

        $this->post('/login', [
            'email' => $employee->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Ce compte est suspendu.',
        ]);
        $this->assertGuest();

        $this->actingAs($colleague)->get('/dashboard')->assertOk();
    }

    public function test_suspension_follows_existing_permissions_and_keeps_history(): void
    {
        $principal = User::factory()->bossPrincipal()->create();
        $secondary = User::factory()->bossSecondaire()->create([
            'organization_id' => $principal->organization_id,
        ]);
        $otherSecondary = User::factory()->bossSecondaire()->create([
            'organization_id' => $principal->organization_id,
        ]);
        $employee = User::factory()->employe()->create([
            'organization_id' => $principal->organization_id,
        ]);
        $outsider = User::factory()->employe()->create();

        $this->actingAs($employee)
            ->post(route('users.suspend', $employee))
            ->assertForbidden();
        $this->assertNull($employee->fresh()->suspended_at);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.suspended']);

        $this->actingAs($principal)
            ->post(route('users.suspend', $principal))
            ->assertForbidden();
        $this->assertNull($principal->fresh()->suspended_at);

        $this->actingAs($secondary)
            ->post(route('users.suspend', $otherSecondary))
            ->assertForbidden();
        $this->assertNull($otherSecondary->fresh()->suspended_at);

        $this->actingAs($secondary)
            ->post(route('users.suspend', $principal))
            ->assertForbidden();
        $this->assertNull($principal->fresh()->suspended_at);

        $this->assertSame(3, AuditLog::query()->where('action', 'user.suspension_refused')->count());
        $this->assertSame(
            0,
            AuditLog::query()->where('action', 'user.suspended')->count(),
        );

        $this->actingAs($principal)
            ->post(route('users.suspend', $outsider))
            ->assertNotFound();
        $this->assertNull($outsider->fresh()->suspended_at);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'user.suspension_refused',
            'subject_id' => $outsider->id,
        ]);

        $this->actingAs($secondary)
            ->from(route('users.invitations.index'))
            ->post(route('users.suspend', $employee))
            ->assertRedirect();
        $this->assertNotNull($employee->fresh()->suspended_at);

        $this->actingAs($secondary)
            ->from(route('users.invitations.index'))
            ->post(route('users.suspend', $employee))
            ->assertSessionHasErrors('member');
        $this->assertSame(1, AuditLog::query()->where('action', 'user.suspended')->count());

        $this->actingAs($principal)
            ->from(route('users.invitations.index'))
            ->post(route('users.suspend', $secondary))
            ->assertRedirect();
        $this->assertNotNull($secondary->fresh()->suspended_at);

        $this->actingAs($principal)
            ->from(route('users.invitations.index'))
            ->delete(route('users.reactivate', $employee))
            ->assertRedirect()
            ->assertSessionHas('status', 'Compte réactivé.');
        $this->assertNull($employee->fresh()->suspended_at);

        $reactivation = AuditLog::query()->where('action', 'user.reactivated')->first();
        $this->assertNotNull($reactivation);
        $this->assertSame($principal->id, $reactivation->user_id);
        $this->assertSame($employee->id, $reactivation->subject_id);
        $this->assertSame('success', $reactivation->result);

        $this->actingAs($employee->fresh())->get('/dashboard')->assertOk();

        $this->actingAs($principal)
            ->get(route('users.invitations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('members')
                ->where('members', function ($members) use ($employee, $secondary) {
                    $rows = collect($members)->keyBy('id');

                    return $rows[$employee->id]['status_label'] === 'Actif'
                        && $rows[$employee->id]['can_suspend'] === true
                        && $rows[$secondary->id]['status_label'] === 'Suspendu'
                        && $rows[$secondary->id]['can_reactivate'] === true;
                })
            );
    }
}
