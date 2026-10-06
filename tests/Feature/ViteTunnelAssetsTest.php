<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class ViteTunnelAssetsTest extends TestCase
{
    use RefreshDatabase;

    private string $hotFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withVite();
        $this->hotFile = storage_path('framework/testing-vite.hot');
        File::put($this->hotFile, "http://127.0.0.1:5173\n");
        Vite::useHotFile($this->hotFile);
        config(['trustedproxy.proxies' => '*']);
    }

    protected function tearDown(): void
    {
        File::delete($this->hotFile);
        Vite::useHotFile(public_path('hot'));

        parent::tearDown();
    }

    public function test_https_tunnel_uses_built_assets_while_vite_dev_server_is_running(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tunnel.example.test',
            'X-Forwarded-Port' => '443',
        ])->get('/login');

        $response->assertOk();
        $response->assertSee('/build/assets/', false);
        $response->assertDontSee('127.0.0.1:5173', false);
        $response->assertDontSee('@vite/client', false);
        $response->assertSee('tunnel.example.test', false);
    }

    public function test_localhost_keeps_the_vite_dev_server_after_a_tunnel_request(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tunnel.example.test',
            'X-Forwarded-Port' => '443',
        ])->get('/login')->assertOk();

        $this->flushHeaders();
        app('url')->setRequest(Request::create('http://127.0.0.1:8000', 'GET'));

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('http://127.0.0.1:5173/@vite/client', false);
        $response->assertSee('http://127.0.0.1:5173/resources/js/app.ts', false);
        $response->assertDontSee('/build/assets/', false);
    }
}
