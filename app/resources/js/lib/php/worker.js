/*
 * Runs learner PHP in WebAssembly, inside a Web Worker. Learner code never
 * reaches the server, and a stuck script can be stopped by terminating the
 * worker (see runner.js).
 */
import { PHP, setPhpIniEntries } from '@php-wasm/universal';
import { loadWebRuntime } from '@php-wasm/web';
// Only this version is bundled; vite.config.js stubs out the others.
import { PHP_VERSION } from './version';

let ready;

function boot() {
    ready ??= (async () => {
        const php = new PHP(await loadWebRuntime(PHP_VERSION));
        await setPhpIniEntries(php, {
            display_errors: '1',
            html_errors: '0',
            error_reporting: 'E_ALL',
            log_errors: '0',
            memory_limit: '64M',
            allow_url_fopen: '0',
        });
        php.mkdir('/sandbox');
        return php;
    })();
    return ready;
}

self.onmessage = async ({ data }) => {
    const { id, type, files = {}, entry } = data;

    try {
        const php = await boot();
        if (type === 'boot') {
            self.postMessage({ id, ok: true });
            return;
        }

        for (const [path, contents] of Object.entries(files)) {
            php.writeFile(path, contents);
        }

        let response;
        try {
            response = await php.run({ scriptPath: entry });
        } catch (error) {
            // Fatal errors reject, but the response still holds PHP's output.
            if (!error?.response) throw error;
            response = error.response;
        }

        self.postMessage({ id, ok: true, stdout: response.text, exitCode: response.exitCode });
    } catch (error) {
        self.postMessage({ id, ok: false, error: String(error?.message ?? error) });
    }
};
