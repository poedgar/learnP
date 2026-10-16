/*
 * The JavaScript and TypeScript sandbox in the browser. Each run gets a fresh sandboxed iframe
 * (scripts allowed, but no access to this page or its storage) that runs frame-runtime.js; see
 * engine.js for the rest. TypeScript is type-checked by the real compiler in a worker.
 */
import { reactive } from 'vue';
import runtimeSource from './frame-runtime.js?raw';
import loopGuardSource from './loop-guard.js?raw';
// acorn's UMD build, which defines a global acorn in the frame (its package exports don't name it).
import acornSource from '/node_modules/acorn/dist/acorn.js?raw';
import serviceSource from './ts-service.js?raw';
import { createJsEngine, frameHtml, typescriptWorkerScript } from './engine';
import { TS_VERSION } from './ts-version';

const html = frameHtml({ runtime: runtimeSource, loopGuard: loopGuardSource, acorn: acornSource });

/** Off-screen frames still run timers and layout, unlike display: none ones. */
let pool;
function hiddenPool() {
    if (!pool) {
        pool = document.createElement('div');
        pool.setAttribute('aria-hidden', 'true');
        pool.style.cssText = 'position:fixed;left:-10000px;top:0;width:1600px;height:600px;overflow:hidden;';
        document.body.append(pool);
    }
    return pool;
}

/**
 * Opens a sandbox frame. With a host element, the frame is created inside it, so a page the code
 * builds can be shown there without reloading the frame. Hidden frames are 800 × 600 CSS pixels
 * (layout checks depend on it), or `width` wide when a check asks for that.
 */
function openFrame(host = null, width = null) {
    return new Promise((resolve) => {
        const iframe = document.createElement('iframe');
        // allow-forms so that submit events fire; nothing else.
        iframe.setAttribute('sandbox', 'allow-scripts allow-forms');
        iframe.title = 'The page your code built';
        iframe.className = 'js-frame';
        iframe.srcdoc = html;
        if (!host) {
            iframe.style.cssText = `width:${width ?? 800}px;height:600px;border:0;`;
        }

        let callback = () => {};
        const listener = (event) => {
            if (event.source === iframe.contentWindow) callback(event.data);
        };
        window.addEventListener('message', listener);

        iframe.addEventListener(
            'load',
            () =>
                resolve({
                    element: iframe,
                    send: (message) => iframe.contentWindow.postMessage(message, '*'),
                    onMessage: (next) => (callback = next),
                    close: () => {
                        window.removeEventListener('message', listener);
                        iframe.remove();
                    },
                }),
            { once: true },
        );
        (host ?? hiddenPool()).append(iframe);
    });
}

// ---- TypeScript ----

const typescriptState = reactive({ status: 'idle', message: '' });
let worker = null;
let nextId = 1;
const pending = new Map();

function startTypescript() {
    if (worker) return worker;
    const base = new URL(`/vendor/typescript/${TS_VERSION}/`, window.location.origin);
    const script = typescriptWorkerScript(serviceSource, [new URL('typescript.js', base).href, new URL('libs.js', base).href]);
    worker = new Worker(URL.createObjectURL(new Blob([script], { type: 'text/javascript' })));
    worker.onmessage = ({ data }) => {
        const request = pending.get(data.id);
        if (!request) return;
        pending.delete(data.id);
        if (data.ok) {
            typescriptState.status = 'ready';
            typescriptState.message = `TypeScript ${TS_VERSION} is ready.`;
            request.resolve(data.result);
        } else {
            request.reject(new Error(data.error));
        }
    };
    worker.onerror = (event) => {
        typescriptState.status = 'error';
        typescriptState.message = `TypeScript could not start in this browser: ${event.message || 'the compiler did not load'}.`;
        for (const request of pending.values()) request.reject(new Error(typescriptState.message));
        pending.clear();
        worker = null;
    };
    return worker;
}

const typescript = {
    compile(code, snippets = []) {
        const id = nextId++;
        return new Promise((resolve, reject) => {
            pending.set(id, { resolve, reject });
            startTypescript().postMessage({ id, code, snippets });
        });
    },
};

/**
 * Starts loading TypeScript in the background and exposes its status for the UI. The first load
 * downloads the compiler (a few MB); later loads come from the browser cache.
 */
export function useTypescriptRuntime() {
    if (typescriptState.status === 'idle' || typescriptState.status === 'error') {
        typescriptState.status = 'loading';
        typescriptState.message = `Starting TypeScript ${TS_VERSION}. The first time, this downloads a few MB.`;
        typescript.compile('').catch(() => {});
    }
    return typescriptState;
}

const engine = createJsEngine({ openFrame: ({ width }) => openFrame(null, width), typescript });
const engineWithHost = (host) => createJsEngine({ openFrame: () => openFrame(host), typescript });

/**
 * Runs code. Pass a host element to show the page the code builds there: if the run leaves
 * something on the page, the result's `frame` stays open inside the host until you close() it.
 */
export function runCode(code, { language, host = null } = {}) {
    return (host ? engineWithHost(host) : engine).run(code, { language, keepFrame: Boolean(host) });
}

export function checkCode(code, checks, { language }) {
    return engine.check(code, checks, { language });
}
