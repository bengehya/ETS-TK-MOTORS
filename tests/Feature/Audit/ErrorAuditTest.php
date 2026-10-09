<?php

namespace Tests\Feature\Audit;

use App\Exceptions\UnexpectedErrorAuditor;
use App\Exceptions\UserFacingErrorResponse;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ErrorAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unexpected_error_stays_generic_and_is_audited_once(): void
    {
        Route::middleware('web')->get('/_correction/boom', function (): void {
            throw new RuntimeException('SQLSTATE[HY000] select password from users');
        });

        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->get('/_correction/boom')
            ->assertServerError()
            ->assertSee(UserFacingErrorResponse::FAILURE, false)
            ->assertDontSee('SQLSTATE', false)
            ->assertDontSee('password', false)
            ->assertDontSee('RuntimeException', false);

        $this->assertSame(1, AuditLog::query()->where('action', 'system.error')->count());

        $log = AuditLog::query()->where('action', 'system.error')->firstOrFail();
        $this->assertSame('failure', $log->result);
        $this->assertSame('BOSS_PRINCIPAL', $log->actor_role);
        $this->assertSame($boss->id, $log->user_id);
        $this->assertNotNull($log->correlation_id);
        $this->assertSame('RuntimeException', $log->new_values['exception']);

        $again = new RuntimeException('SQLSTATE secret');
        app(UnexpectedErrorAuditor::class)->record($again);
        app(UnexpectedErrorAuditor::class)->record($again);

        $this->assertSame(2, AuditLog::query()->where('action', 'system.error')->count());
    }

    public function test_sensitive_values_are_redacted_and_employees_cannot_read_the_audit(): void
    {
        $boss = User::factory()->bossPrincipal()->create();
        $employee = User::factory()->employe()->create(['organization_id' => $boss->organization_id]);

        app(AuditLogger::class)->safeRecord($boss, 'system.error', null, null, [
            'password' => 'secret-password',
            'invitation_code' => '12345',
            'note' => 'solde',
        ], 'Contrôle', 'failure');

        $log = AuditLog::query()->where('action', 'system.error')->firstOrFail();
        $this->assertSame('[retiré]', $log->new_values['password']);
        $this->assertSame('[retiré]', $log->new_values['invitation_code']);
        $this->assertSame('solde', $log->new_values['note']);
        $this->assertStringNotContainsString('secret-password', json_encode($log->new_values));

        $this->actingAs($employee)
            ->get(route('audit.index'))
            ->assertForbidden()
            ->assertSee(UserFacingErrorResponse::DENIED, false)
            ->assertDontSee('secret-password', false)
            ->assertDontSee('SQLSTATE', false);

        $denied = AuditLog::query()->where('action', 'permission.denied')->get();
        $this->assertCount(1, $denied);
        $this->assertSame('denied', $denied->first()->result);
        $this->assertSame('EMPLOYE', $denied->first()->actor_role);
        $this->assertSame($employee->id, $denied->first()->user_id);

        $this->actingAs($boss)
            ->get(route('audit.index'))
            ->assertOk()
            ->assertDontSee('secret-password', false)
            ->assertInertia(fn (Assert $page) => $page
                ->where('logs.data.0.action', 'permission.denied')
                ->where('logs.data.0.result', 'denied')
                ->where('logs.data.0.actor_role', 'EMPLOYE')
            );

        $this->assertStringContainsString("return 'Refusé'", (string) file_get_contents(resource_path('js/Pages/Audit/Index.vue')));
    }

    public function test_a_missing_organization_does_not_break_safe_audit(): void
    {
        $written = app(AuditLogger::class)->safeRecord(null, 'system.error', null, null, [
            'note' => 'invité',
        ], 'Sans organisation', 'failure');

        $this->assertNull($written);
        $this->assertSame(0, AuditLog::query()->count());
    }
}
