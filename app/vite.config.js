import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { TS_VERSION } from './resources/js/lib/js/ts-version.js';

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

/**
 * The JavaScript sandbox type-checks TypeScript with the real compiler, loaded on demand by a
 * worker in the browser and by the mobile app. Publish it, with lib.es2023.d.ts and everything that
 * references, at a stable URL: /vendor/typescript/<version>/typescript.js and libs.js.
 */
function typescriptCompiler() {
    return {
        name: 'learnp:typescript-compiler',
        buildStart() {
            const require = createRequire(import.meta.url);
            const compiler = require.resolve('typescript/lib/typescript.js');
            const installed = JSON.parse(readFileSync(require.resolve('typescript/package.json'), 'utf8')).version;
            if (installed !== TS_VERSION) {
                throw new Error(`TS_VERSION (${TS_VERSION}) does not match the installed typescript package (${installed}).`);
            }

            const libs = {};
            const queue = ['lib.es2023.d.ts'];
            while (queue.length) {
                const name = queue.pop();
                if (name in libs) continue;
                libs[name] = readFileSync(join(dirname(compiler), name), 'utf8');
                for (const [, lib] of libs[name].matchAll(/<reference lib="([^"]+)"/g)) {
                    queue.push(`lib.${lib.toLowerCase()}.d.ts`);
                }
            }

            const target = fileURLToPath(new URL(`./public/vendor/typescript/${TS_VERSION}/`, import.meta.url));
            mkdirSync(target, { recursive: true });
            writeFileSync(join(target, 'typescript.js'), readFileSync(compiler));
            writeFileSync(join(target, 'libs.js'), `self.__learnpTsLibs = ${JSON.stringify(libs)};\n`);
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
        typescriptCompiler(),
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
