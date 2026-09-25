import { reactive } from 'vue';
import { PHP_VERSION } from './version';
import { warmUp } from './runner';

const state = reactive({ status: 'idle', message: '' });

/**
 * Starts loading PHP in the background and exposes its status for the UI.
 * The first load downloads the WebAssembly runtime; later loads come from the browser cache.
 */
export function usePhpRuntime() {
    if (state.status === 'idle' || state.status === 'error') {
        state.status = 'loading';
        state.message = `Starting PHP ${PHP_VERSION}. The first time, this downloads about 20 MB.`;
        warmUp().then(
            () => {
                state.status = 'ready';
                state.message = `PHP ${PHP_VERSION} is ready.`;
            },
            (error) => {
                state.status = 'error';
                state.message = `PHP could not start in this browser: ${error.message}`;
            },
        );
    }
    return state;
}
