<?php

namespace Tests\Feature;

use App\Enums\Civility;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
            ->assertSessionHas('invitation_url');

        $status = session('status');
        $this->assertIsString($status);
        $this->assertStringNotContainsString('envoyée', $status);
        $this->assertStringContainsString('Invitation créée', $status);

        Mail::assertNothingSent();

        $invitation = Invitation::query()->first();
        $this->assertNotNull($invitation);
        $this->assertSame($boss->organization_id, $invitation->organization_id);
        $this->assertSame(Role::Employe, $invitation->role);
        $this->assertSame(Civility::Madame, $invitation->civility);
        $this->assertSame('amina@tkmotors.test', $invitation->email);
        $this->assertSame(64, strlen($invitation->token_hash));
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

        $url = session('invitation_url');
        $this->assertIsString($url);
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $this->post('/logout');

        $this->get($url)->assertOk();

        $this->post(route('invitations.accept.store', ['token' => $token]), [
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

        $this->post('/logout');

        $this->post(route('invitations.accept.store', ['token' => $token]), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('token');

        $this->assertSame(2, User::query()->count());
    }

    public function test_a_revoked_invitation_cannot_be_accepted(): void
    {
        $boss = User::factory()->bossPrincipal()->create();

        $this->actingAs($boss)
            ->post('/utilisateurs/invitations', $this->payload())
            ->assertRedirect();

        $invitation = Invitation::query()->firstOrFail();
        $url = session('invitation_url');
        $token = basename((string) parse_url((string) $url, PHP_URL_PATH));

        $this->actingAs($boss)
            ->delete('/utilisateurs/invitations/'.$invitation->id)
            ->assertRedirect();

        $this->post('/logout');

        $this->post(route('invitations.accept.store', ['token' => $token]), [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('token');

        $this->assertSame(1, User::query()->count());
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
