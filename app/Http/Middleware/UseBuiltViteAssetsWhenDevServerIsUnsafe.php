<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class UseBuiltViteAssetsWhenDevServerIsUnsafe
{
    /**
     * `npm run dev` écrit public/hot. Sur localhost, ces balises chargent le
     * serveur Vite. Sur une page HTTPS ou un autre hôte, le navigateur ne peut
     * pas utiliser une adresse de boucle locale : la page reste blanche.
     * Cette requête utilise alors les assets compilés, sans retirer public/hot.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hotFile = Vite::hotFile();
        $ignored = false;

        if (is_string($hotFile) && is_file($hotFile) && $this->hotFileIsUnsafe($request, $hotFile)) {
            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
            $ignored = true;
        }

        try {
            return $next($request);
        } finally {
            if ($ignored) {
                Vite::useHotFile($hotFile);
            }
        }
    }

    private function hotFileIsUnsafe(Request $request, string $hotFile): bool
    {
        $hotUrl = trim((string) file_get_contents($hotFile));
        $parts = parse_url($hotUrl);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return true;
        }

        $hotScheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $hotHost = $this->normalizeHost((string) $parts['host']);
        $requestHost = $this->normalizeHost($request->getHost());

        if ($request->isSecure() && $hotScheme !== 'https') {
            return true;
        }

        return $this->isLoopback($hotHost) && ! $this->isLoopback($requestHost);
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        return trim($host, '[]');
    }

    private function isLoopback(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }
}
