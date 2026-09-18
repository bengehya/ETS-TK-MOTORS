<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('TK MOTORS', false);
        $response->assertSee('Votre Moto, Notre Passion', false);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('canLogin', true)
        );
    }
}
