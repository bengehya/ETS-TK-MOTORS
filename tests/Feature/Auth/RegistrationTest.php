<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered_when_no_user_exists(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_the_first_user_becomes_boss_principal_of_ets_tk_motors(): void
    {
        $response = $this->post('/register', [
            'name' => 'Patron Principal',
            'email' => 'patron@tkmotors.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->first();

        $this->assertNotNull($user);
        $this->assertSame(Role::BossPrincipal, $user->role);
        $this->assertSame(config('tkmotors.company'), $user->organization->name);
        $this->assertSame(config('tkmotors.organization_slug'), $user->organization->slug);
        $this->assertSame(1, Organization::query()->count());
    }

    public function test_public_registration_is_closed_after_the_first_user(): void
    {
        User::factory()->bossPrincipal()->create();

        $this->get('/register')->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'Intrus',
            'email' => 'intrus@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
    }
}
