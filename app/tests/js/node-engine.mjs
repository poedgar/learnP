/*
 * The JavaScript sandbox engine for Node: each frame is a fresh jsdom window running
 * frame-runtime.js, and TypeScript comes from node_modules. Used by the content tests.
 */
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';
import { JSDOM, VirtualConsole } from 'jsdom';
import { createJsEngine, runtimeScript } from '../../resources/js/lib/js/engine.js';
import { installTsService } from '../../resources/js/lib/js/ts-service.js';

const require = createRequire(import.meta.url);
const source = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const runtime = runtimeScript({
    runtime: source('../../resources/js/lib/js/frame-runtime.js'),
    loopGuard: source('../../resources/js/lib/js/loop-guard.js'),
    // The UMD build (dist/acorn.js), which defines a global acorn in the frame.
    acorn: readFileSync(require.resolve('acorn'), 'utf8'),
});

// A learner's unhandled rejection inside jsdom reaches Node; the frame has already reported it.
process.on('unhandledRejection', () => {});

/** lib.es2023.d.ts and every lib file it references, read from the installed compiler. */
export function typescriptLibs(ts = require('typescript')) {
    const directory = dirname(require.resolve('typescript/lib/typescript.js'));
    const libs = {};
    const queue = ['lib.es2023.d.ts'];
    while (queue.length) {
        const name = queue.pop();
        if (name in libs) continue;
        libs[name] = readFileSync(join(directory, name), 'utf8');
        for (const [, lib] of libs[name].matchAll(/<reference lib="([^"]+)"/g)) {
            queue.push(`lib.${lib.toLowerCase()}.d.ts`);
        }
    }
    return libs;
}

/** Browsers have structuredClone(); jsdom doesn't. A version for plain data, defined inside the frame's realm. */
const STRUCTURED_CLONE = `
window.structuredClone = function clone(value, seen = new Map()) {
    if (typeof value !== 'object' || value === null) return value;
    if (seen.has(value)) return seen.get(value);
    if (value instanceof Date) return new Date(value.getTime());
    if (value instanceof RegExp) return new RegExp(value.source, value.flags);
    if (value instanceof Map) {
        const copy = new Map();
        seen.set(value, copy);
        value.forEach((v, k) => copy.set(clone(k, seen), clone(v, seen)));
        return copy;
    }
    if (value instanceof Set) {
        const copy = new Set();
        seen.set(value, copy);
        value.forEach((v) => copy.add(clone(v, seen)));
        return copy;
    }
    const copy = Array.isArray(value) ? [] : {};
    seen.set(value, copy);
    for (const key of Object.keys(value)) copy[key] = clone(value[key], seen);
    return copy;
};`;

/** jsdom has no layout, so the width a check asks for makes no difference here. */
export function openFrame() {
    const dom = new JSDOM('<!doctype html><html lang="en"><head></head><body></body></html>', {
        runScripts: 'outside-only',
        pretendToBeVisual: true,
        virtualConsole: new VirtualConsole(),
    });
    const { window } = dom;
    let listener = () => {};
    window.__learnpSend = (message) => setTimeout(() => listener(JSON.parse(JSON.stringify(message))), 0);
    window.eval(STRUCTURED_CLONE);
    window.eval(runtime);

    return Promise.resolve({
        window,
        send: (message) => window.__learnpReceive(message),
        onMessage: (callback) => (listener = callback),
        close: () => window.close(),
    });
}

let service;
export const typescript = {
    compile(code, snippets) {
        service ??= installTsService(require('typescript'), typescriptLibs());
        return Promise.resolve(service.compile(code, snippets));
    },
};

export const engine = createJsEngine({ openFrame, typescript });
