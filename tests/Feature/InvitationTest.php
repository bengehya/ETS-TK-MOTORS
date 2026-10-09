<?php

namespace Tests\Feature;

use App\Enums\Civility;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_cannot_invite_users(): void
    {
        $employee = User::factory()->employe()->create();

        $this->actingAs($employee)->get('/utilisateurs')->assertForbidden();
        $this->actingAs($employee)->post('/utilisateurs/invitations', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_a_principal_boss_creates_a_real_invitation_without_sending_email(): void
    {
        Mail::fake();

        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload())
            ->assertRedirect(route('users.invitations.index'))
            ->assertSessionHas('invitation_code');

        $status = session('status');
        $code = session('invitation_code');
        $this->assertIsString($status);
        $this->assertStringNotContainsString('envoyée', $status);
        $this->assertStringContainsString('Invitation créée', $status);
        $this->assertMatchesRegularExpression('/\A\d{5}\z/', (string) $code);

        Mail::assertNothingSent();

        $invitation = Invitation::query()->first();
        $this->assertNotNull($invitation);
        $this->assertSame($boss->organization_id, $invitation->organization_id);
        $this->assertSame(Role::Employe, $invitation->role);
        $this->assertSame(Civility::Madame, $invitation->civility);
        $this->assertSame('amina@tkmotors.test', $invitation->email);
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertSame(hash('sha256', (string) $code), $invitation->token_hash);
        $this->assertNotSame($code, $invitation->token_hash);
        $this->assertTrue($invitation->expires_at?->greaterThan(now()->addDays(InvitationService::CODE_TTL_DAYS)->subMinute()) ?? false);

        $audit = AuditLog::query()->where('action', 'invitation.created')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString((string) $code, (string) json_encode($audit->getAttributes()));

        $response = $this->actingAs($boss)->get(route('users.invitations.index'));
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('activationCode', $code)
            ->where('emailDeliveryAvailable', false)
        );

        $this->actingAs($boss)
            ->get(route('users.invitations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('activationCode', null));
    }

    public function test_a_secondary_boss_cannot_invite_another_boss_or_the_principal(): void
    {
        $boss = User::factory()->bossSecondaire()->create();

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload(['role' => Role::BossSecondaire->value]))
            ->assertSessionHasErrors('role');

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload(['role' => Role::BossPrincipal->value]))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('invitations', 0);

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload())
            ->assertRedirect();

        $this->assertSame(Role::Employe, Invitation::query()->first()?->role);
    }

    public function test_an_invitation_can_be_accepted_once_and_records_the_chosen_civility(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload([
                'role' => Role::BossSecondaire->value,
                'civility' => Civility::Monsieur->value,
                'first_name' => 'David',
                'last_name' => 'Ilunga',
                'email' => 'david@tkmotors.test',
            ]))
            ->assertRedirect();

        $code = session('invitation_code');
        $this->assertIsString($code);

        $this->post('/logout');

        $this->get(route('invitations.accept'))->assertOk();

        $this->post(route('invitations.accept.store'), [
            'code' => $code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'david@tkmotors.test')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::BossSecondaire, $user->role);
        $this->assertSame(Civility::Monsieur, $user->civility);
        $this->assertSame('David', $user->first_name);
        $this->assertSame('Ilunga', $user->last_name);
        $this->assertSame('David Ilunga', $user->name);
        $this->assertSame($boss->organization_id, $user->organization_id);
        $this->assertNotSame(Role::BossPrincipal, $user->role);

        $acceptedAudit = AuditLog::query()->where('action', 'invitation.accepted')->first();
        $this->assertNotNull($acceptedAudit);
        $this->assertStringNotContainsString((string) $code, (string) json_encode($acceptedAudit->getAttributes()));

        $this->post('/logout');

        $this->post(route('invitations.accept.store'), [
            'code' => $code,
            'password' => 'autre-mot-de-passe',
            'password_confirmation' => 'autre-mot-de-passe',
        ])->assertSessionHasErrors('code');

        $this->assertSame(2, User::query()->count());
    }

    public function test_a_revoked_expired_or_unknown_code_is_refused(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload())
            ->assertRedirect();

        $invitation = Invitation::query()->firstOrFail();
        $code = (string) session('invitation_code');

        $this->actingAs($boss)
            ->delete('/utilisateurs/invitations/'.$invitation->id)
            ->assertRedirect();

        $this->post('/logout');

        $this->post(route('invitations.accept.store'), [
            'code' => $code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, User::query()->count());

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload(['email' => 'autre@tkmotors.test']))
            ->assertRedirect();

        $expiredCode = (string) session('invitation_code');
        Invitation::query()->where('email', 'autre@tkmotors.test')->firstOrFail()
            ->forceFill(['expires_at' => now()->subMinute()])
            ->save();

        $this->post('/logout');

        $this->post(route('invitations.accept.store'), [
            'code' => $expiredCode,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('code');

        $this->post(route('invitations.accept.store'), [
            'code' => '00000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('code');

        $this->assertSame(1, User::query()->count());
    }

    public function test_two_pending_invitations_keep_distinct_codes(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)->post('/utilisateurs/invitations', $this->payload())->assertRedirect();
        $first = (string) session('invitation_code');

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload([
                'email' => 'second@tkmotors.test',
                'first_name' => 'Paul',
            ]))
            ->assertRedirect();
        $second = (string) session('invitation_code');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, Invitation::query()->count());

        $this->post('/logout');

        $this->post(route('invitations.accept.store'), [
            'code' => $second,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        $this->post(route('invitations.accept.store'), [
            'code' => $first,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(3, User::query()->count());
    }

    public function test_repeated_invalid_codes_are_rate_limited(): void
    {
        $payload = [
            'code' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('invitations.accept.store'), $payload)->assertSessionHasErrors('code');
        }

        $this->post(route('invitations.accept.store'), $payload)->assertStatus(429);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amina',
            'last_name' => 'Kabila',
            'email' => 'amina@tkmotors.test',
            'civility' => Civility::Madame->value,
            'role' => Role::Employe->value,
        ], $overrides);
    }
}
