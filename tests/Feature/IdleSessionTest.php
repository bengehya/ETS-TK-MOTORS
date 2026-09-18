<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdleSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_idle_session_is_invalidated_and_protected_pages_are_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();

        $this->travel(6)->minutes();

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_idle_session_is_rejected_by_a_protected_json_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->travel(6)->minutes();

        $this->getJson('/api/me')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_activity_keeps_the_session_alive_within_five_minutes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $this->travel(4)->minutes();

        $this->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_guests_cannot_access_the_protected_json_endpoint(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }
}
