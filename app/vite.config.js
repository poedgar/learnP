import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

/**
 * php-wasm imports its WebAssembly binary and data files expecting file URLs
 * back (like Webpack's file-loader). Resolve them as static assets.
 */
function phpWasmAssets() {
    return {
        name: 'learnp:php-wasm-assets',
        enforce: 'pre',
        async resolveId(source, importer) {
            if (!importer?.includes('@php-wasm') || !/\.(wasm|dat|so)$/.test(source)) {
                return null;
            }
            const resolved = await this.resolve(source, importer, { skipSelf: true });
            return resolved ? `${resolved.id}?url` : null;
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Bricolage Grotesque', { weights: [500, 700, 800] }),
                bunny('Atkinson Hyperlegible', { weights: [400, 700] }),
                bunny('JetBrains Mono', { weights: [400, 600] }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),
        phpWasmAssets(),
    ],
    resolve: {
        alias: [
            // The PHP sandbox only ships PHP 8.4 (resources/js/lib/php/worker.js).
            {
                find: /^@php-wasm\/web-(5-2|7-4|8-0|8-1|8-2|8-3|8-5)$/,
                replacement: fileURLToPath(new URL('./resources/js/lib/php/unused-php-version.js', import.meta.url)),
            },
        ],
    },
    worker: {
        format: 'es',
        plugins: () => [phpWasmAssets()],
    },
    optimizeDeps: {
        exclude: ['@php-wasm/web', '@php-wasm/universal', '@php-wasm/web-8-4'],
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
