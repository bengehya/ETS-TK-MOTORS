<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class TunnelUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_https_host_drives_urls_and_secure_cookies(): void
    {
        config([
            'trustedproxy.proxies' => '*',
            'session.driver' => 'file',
            'session.secure' => null,
        ]);

        $response = $this->withHeaders($this->tunnelHeaders())->get('/login');

        $response->assertOk();
        $response->assertSee('tunnel.example.test', false);
        $response->assertSee('/images/logo.jpg', false);
        $response->assertDontSee('localhost', false);
        $response->assertDontSee('127.0.0.1', false);
        $response->assertDontSee('ngrok', false);

        $sessionCookie = $this->sessionCookie($response);
        $this->assertTrue($sessionCookie->isSecure());
        $this->assertNull($sessionCookie->getDomain());

        $csrf = $this->cookieNamed($response, 'XSRF-TOKEN');
        $this->assertTrue($csrf->isSecure());
        $this->assertNull($csrf->getDomain());
    }

    public function test_localhost_request_keeps_http_urls_and_a_non_secure_cookie(): void
    {
        config([
            'trustedproxy.proxies' => '*',
            'session.driver' => 'file',
            'session.secure' => null,
        ]);

        $this->withHeaders($this->tunnelHeaders())->get('/login')->assertOk();
        $this->resetToLocalRequest();

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('localhost', false);
        $response->assertDontSee('tunnel.example.test', false);
        $this->assertFalse($this->sessionCookie($response)->isSecure());
    }

    public function test_guest_redirect_follows_the_forwarded_host(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->withHeaders($this->tunnelHeaders())
            ->get('/dashboard')
            ->assertRedirect('https://tunnel.example.test/login');

        $this->resetToLocalRequest();

        $this->get('/dashboard')
            ->assertRedirect('http://localhost/login');
    }

    public function test_explicit_session_secure_cookie_is_respected(): void
    {
        config([
            'trustedproxy.proxies' => '*',
            'session.driver' => 'file',
            'session.secure' => false,
        ]);

        $secureOverride = $this->withHeaders($this->tunnelHeaders())->get('/login');
        $secureOverride->assertOk();
        $this->assertFalse($this->sessionCookie($secureOverride)->isSecure());

        config(['session.secure' => true]);
        $this->resetToLocalRequest();

        $plain = $this->get('/login');
        $plain->assertOk();
        $this->assertTrue($this->sessionCookie($plain)->isSecure());
    }

    public function test_built_assets_and_public_files_are_root_relative(): void
    {
        $method = new ReflectionMethod(Vite::class, 'assetPath');
        $path = $method->invoke($this->originalVite, 'build/assets/app.js');

        $this->assertSame('/build/assets/app.js', $path);
        $this->assertSame('/storage/logo.jpg', Storage::disk('public')->url('logo.jpg'));
    }

    public function test_project_configuration_stays_on_mysql_without_a_tunnel_host(): void
    {
        $example = file_get_contents(base_path('.env.example'));
        $database = file_get_contents(config_path('database.php'));

        $this->assertStringContainsString('DB_CONNECTION=mysql', $example);
        $this->assertStringNotContainsString('DB_CONNECTION=sqlite', $example);
        $this->assertMatchesRegularExpression(
            "/'default'\\s*=>\\s*env\\('DB_CONNECTION',\\s*'mysql'\\)/",
            $database,
        );

        $roots = [
            app_path(),
            resource_path(),
            base_path('bootstrap'),
            base_path('config'),
            base_path('routes'),
            base_path('vite.config.js'),
            base_path('.env.example'),
            base_path('README.md'),
        ];

        foreach ($roots as $root) {
            foreach ($this->files($root) as $file) {
                $contents = file_get_contents($file->getPathname());

                $this->assertDoesNotMatchRegularExpression(
                    '/ngrok(-free)?\.(app|io|dev)/i',
                    $contents,
                    $file->getPathname(),
                );
                $this->assertStringNotContainsString('forceRootUrl', $contents, $file->getPathname());
                $this->assertStringNotContainsString('forceScheme', $contents, $file->getPathname());
            }
        }
    }

    private function resetToLocalRequest(): void
    {
        $this->flushHeaders();

        app('url')->setRequest(Request::create('http://localhost', 'GET'));
    }

    /**
     * @return array<string, string>
     */
    private function tunnelHeaders(): array
    {
        return [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'tunnel.example.test',
            'X-Forwarded-Port' => '443',
        ];
    }

    private function sessionCookie($response): Cookie
    {
        return $this->cookieNamed($response, (string) config('session.cookie'));
    }

    private function cookieNamed($response, string $name): Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        $this->fail("Cookie [{$name}] absent de la réponse.");
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function files(string $root): iterable
    {
        if (is_file($root)) {
            yield new SplFileInfo($root);

            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                yield $file;
            }
        }
    }
}
