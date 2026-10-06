import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

function devServerFromEnv(raw) {
    if (!raw) {
        return undefined;
    }

    let url;

    try {
        url = new URL(raw);
    } catch {
        return undefined;
    }

    const https = url.protocol === 'https:';

    return {
        origin: url.origin,
        cors: true,
        hmr: {
            host: url.hostname,
            protocol: https ? 'wss' : 'ws',
            clientPort: url.port ? Number(url.port) : (https ? 443 : 80),
        },
    };
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const server = devServerFromEnv(env.VITE_DEV_SERVER_URL);

    return {
        plugins: [
            laravel({
                input: 'resources/js/app.ts',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        ...(server ? { server } : {}),
    };
});
