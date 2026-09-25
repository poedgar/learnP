/*
 * Main-thread side of the PHP sandbox. One worker is kept warm; if a script
 * runs longer than the timeout, the worker is terminated and replaced.
 */
const TIMEOUT_MS = 5000;

let worker = null;
let booting = null;
let nextId = 1;
const pending = new Map();

function spawn() {
    worker = new Worker(new URL('./worker.js', import.meta.url), { type: 'module' });
    worker.onmessage = ({ data }) => {
        const request = pending.get(data.id);
        if (!request) return;
        pending.delete(data.id);
        clearTimeout(request.timer);
        data.ok ? request.resolve(data) : request.reject(new Error(data.error));
    };
    worker.onerror = (event) => {
        for (const request of pending.values()) {
            clearTimeout(request.timer);
            request.reject(new Error(event.message || 'The PHP sandbox crashed.'));
        }
        pending.clear();
        reset();
    };
    booting = send({ type: 'boot' }, null);
    return booting;
}

function reset() {
    worker?.terminate();
    worker = null;
    booting = null;
}

function send(message, timeoutMs) {
    return new Promise((resolve, reject) => {
        const id = nextId++;
        const timer = timeoutMs
            ? setTimeout(() => {
                  pending.delete(id);
                  reset();
                  const error = new Error(
                      `Your code ran for more than ${timeoutMs / 1000} seconds and was stopped. Look for a loop that never ends.`,
                  );
                  error.timedOut = true;
                  reject(error);
              }, timeoutMs)
            : null;
        pending.set(id, { resolve, reject, timer });
        worker.postMessage({ id, ...message });
    });
}

/** Loads PHP in the background. Safe to call repeatedly. */
export function warmUp() {
    return booting ?? spawn();
}

/**
 * Runs `entry` after writing `files` into the sandbox's virtual file system.
 * @returns {Promise<{ stdout: string, exitCode: number }>}
 */
export async function runPhp(files, entry) {
    await warmUp();
    const { stdout, exitCode } = await send({ type: 'run', files, entry }, TIMEOUT_MS);
    return { stdout, exitCode };
}
