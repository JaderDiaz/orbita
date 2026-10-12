import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

const VITE_HOST_PORT = Number(process.env.VITE_HOST_PORT ?? 5274);

// The page is served by the app container on a different port, so the dev
// server has to allow it explicitly: setting server.origin makes Vite answer
// with its own origin in Access-Control-Allow-Origin, which the browser then
// rejects. Only visible in a browser — the production build serves assets
// from the app itself.
const APP_ORIGIN = process.env.APP_URL ?? 'http://localhost:8100';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Geist', {
                    weights: [400, 500, 600, 700],
                }),
                bunny('Geist Mono', {
                    weights: [400, 500],
                }),
            ],
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        host: '0.0.0.0',
        // Vite always binds 5173 inside the container; Docker publishes it on
        // ORBITA_VITE_PORT. The browser only ever sees the published port, so
        // origin and the HMR client must point there or assets 404.
        port: 5173,
        origin: `http://localhost:${VITE_HOST_PORT}`,
        cors: {
            origin: [APP_ORIGIN],
            credentials: true,
        },
        hmr: {
            host: 'localhost',
            clientPort: VITE_HOST_PORT,
        },
        watch: {
            // Filesystem events do not cross Colima's virtiofs mount
            // reliably, so the dev server misses edits and HMR goes stale.
            // Polling is the cost of running Vite inside the container.
            usePolling: true,
            interval: 300,
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
