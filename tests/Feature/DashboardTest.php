<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_the_dashboard(): void
    {
        $user = User::factory()->bossPrincipal()->create([
            'name' => 'Patron Principal',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Patron Principal', false);
        $response->assertSee('BOSS_PRINCIPAL', false);
        $response->assertSee($user->organization->name, false);
        $response->assertDontSee('bénéfice', false);
        $response->assertDontSee('chiffre d’affaires', false);
    }

    public function test_authenticated_users_see_the_splash_on_the_home_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->component('Welcome')
            );
    }
}
