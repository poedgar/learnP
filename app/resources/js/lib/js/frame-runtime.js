/*
 * The JavaScript sandbox's runtime. It runs inside the sandbox frame: a sandboxed iframe on the
 * web, the same inside the mobile app's WebView, and a jsdom window in the content tests.
 *
 * This file is injected as source text (see runtimeSource() in engine.js), so installRuntime()
 * must stay self-contained: no imports, and nothing from outside the function.
 *
 * The parent sends one job: { type: 'job', token, mode: 'run' | 'check', program, sourceLines,
 * checkCount, guardMs, waitMs }. The frame answers with { token, type: 'log', entry },
 * { token, type: 'check', index, result } and finally { token, type: 'done', report }.
 */
export function installRuntime(window, send) {
    const nativeSetTimeout = window.setTimeout.bind(window);
    const nativeClearTimeout = window.clearTimeout.bind(window);
    const nativeSetInterval = window.setInterval.bind(window);
    const nativeClearInterval = window.clearInterval.bind(window);
    const now = () => window.performance.now();
    const post = send ?? ((message) => window.parent.postMessage(message, '*'));

    let token = null;
    let sourceLines = 0;
    const entries = [];
    let groupDepth = 0;

    // ---- Describing values, much like Node's console.log ----

    const IDENTIFIER = /^[A-Za-z_$][\w$]*$/;

    function quote(text) {
        return `'${text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\n/g, '\\n')}'`;
    }

    function isElement(value) {
        return typeof window.Node === 'function' && value instanceof window.Node;
    }

    function describeNode(node) {
        if (node.nodeType === 3) return `#text ${quote(node.textContent)}`;
        if (node.nodeType === 9) return '#document';
        if (node.nodeType !== 1) return `#${node.nodeName.toLowerCase()}`;
        const html = node.outerHTML;
        if (html.length <= 80) return html;
        const open = html.slice(0, html.indexOf('>') + 1);
        return `${open}…</${node.tagName.toLowerCase()}>`;
    }

    function describeKey(key) {
        if (typeof key === 'symbol') return `[${key.toString()}]`;
        return IDENTIFIER.test(key) ? key : quote(key);
    }

    function join(open, parts, close, indent) {
        if (parts.length === 0) return `${open}${close}`;
        const single = `${open} ${parts.join(', ')} ${close}`;
        if (single.length <= 72 && !parts.some((p) => p.includes('\n'))) return single;
        const pad = '  '.repeat(indent + 1);
        return `${open}\n${parts.map((p) => pad + p).join(',\n')}\n${'  '.repeat(indent)}${close}`;
    }

    function className(value) {
        const proto = Object.getPrototypeOf(value);
        if (proto === null) return '[Object: null prototype]';
        const name = proto.constructor?.name;
        return name && name !== 'Object' ? name : '';
    }

    /** Describes a value. Top-level strings print as they are, as console.log does, unless asValue is set. */
    function inspect(value, depth = 0, seen = [], asValue = false) {
        switch (typeof value) {
            case 'string':
                return depth === 0 && !asValue ? value : quote(value);
            case 'number':
                return Object.is(value, -0) ? '-0' : String(value);
            case 'bigint':
                return `${value}n`;
            case 'boolean':
            case 'undefined':
                return String(value);
            case 'symbol':
                return value.toString();
            case 'function': {
                const source = Function.prototype.toString.call(value);
                if (/^class\b/.test(source)) return `[class ${value.name || '(anonymous)'}]`;
                return value.name ? `[Function: ${value.name}]` : '[Function (anonymous)]';
            }
        }
        if (value === null) return 'null';
        if (seen.includes(value)) return '[Circular]';
        if (isElement(value)) return describeNode(value);
        if (value instanceof window.Error || value instanceof Error) {
            return depth === 0 && !asValue ? `${value.name}: ${value.message}` : `[${value.name}: ${value.message}]`;
        }
        if (value instanceof window.Date) return Number.isNaN(value.getTime()) ? 'Invalid Date' : value.toISOString();
        if (value instanceof window.RegExp) return String(value);
        if (value instanceof window.Promise) return 'Promise {}';
        if (value instanceof window.WeakMap) return 'WeakMap { <items unknown> }';
        if (value instanceof window.WeakSet) return 'WeakSet { <items unknown> }';

        const inner = [...seen, value];
        const next = (item) => inspect(item, depth + 1, inner);

        if (Array.isArray(value) || ArrayBuffer.isView(value)) {
            if (depth > 2) return '[Array]';
            const parts = [];
            let holes = 0;
            const flush = () => {
                if (holes) parts.push(`<${holes} empty item${holes === 1 ? '' : 's'}>`);
                holes = 0;
            };
            const shown = Math.min(value.length, 100);
            for (let i = 0; i < shown; i++) {
                if (Array.isArray(value) && !(i in value)) {
                    holes++;
                    continue;
                }
                flush();
                parts.push(next(value[i]));
            }
            flush();
            if (value.length > shown) parts.push(`... ${value.length - shown} more item${value.length - shown === 1 ? '' : 's'}`);
            const prefix = Array.isArray(value) ? '' : `${value.constructor.name}(${value.length}) `;
            return prefix + join('[', parts, ']', depth);
        }
        if (value instanceof window.Map) {
            if (depth > 2) return '[Map]';
            return `Map(${value.size}) ` + join('{', [...value].map(([k, v]) => `${next(k)} => ${next(v)}`), '}', depth);
        }
        if (value instanceof window.Set) {
            if (depth > 2) return '[Set]';
            return `Set(${value.size}) ` + join('{', [...value].map(next), '}', depth);
        }
        if (typeof window.NodeList === 'function' && (value instanceof window.NodeList || value instanceof window.HTMLCollection)) {
            return `${value.constructor.name}(${value.length}) ` + join('[', [...value].map(next), ']', depth);
        }

        const name = className(value);
        if (depth > 2) return name ? `[${name}]` : '[Object]';
        const keys = [...Object.keys(value), ...Object.getOwnPropertySymbols(value).filter((s) => Object.prototype.propertyIsEnumerable.call(value, s))];
        const parts = keys.map((key) => {
            const descriptor = Object.getOwnPropertyDescriptor(value, key);
            const shown = descriptor && 'value' in descriptor ? next(descriptor.value) : '[Getter/Setter]';
            return `${describeKey(key)}: ${shown}`;
        });
        return (name ? `${name} ` : '') + join('{', parts, '}', depth);
    }

    function format(args) {
        if (typeof args[0] === 'string' && /%[sdifoOjc%]/.test(args[0]) && args.length > 1) {
            let rest = args.slice(1);
            const first = args[0].replace(/%([sdifoOjc%])/g, (match, spec) => {
                if (spec === '%') return '%';
                if (rest.length === 0) return match;
                const value = rest.shift();
                switch (spec) {
                    case 's':
                        return typeof value === 'string' ? value : inspect(value, 1);
                    case 'd':
                    case 'i':
                        return String(spec === 'i' ? Math.trunc(Number(value)) : Number(value));
                    case 'f':
                        return String(Number(value));
                    case 'c':
                        return '';
                    default:
                        return inspect(value, 1);
                }
            });
            args = [first, ...rest];
        }
        return args.map((arg) => inspect(arg)).join(' ');
    }

    function record(level, text) {
        const entry = { level, text: '  '.repeat(groupDepth) + text };
        entries.push(entry);
        if (token) post({ token, type: 'log', entry });
    }

    const counts = new Map();
    const timers = new Map();
    window.console = {
        log: (...args) => record('log', format(args)),
        info: (...args) => record('log', format(args)),
        debug: (...args) => record('log', format(args)),
        dir: (value) => record('log', inspect(value)),
        table: (value) => record('log', inspect(value)),
        warn: (...args) => record('warn', format(args)),
        error: (...args) => record('error', format(args)),
        assert: (condition, ...args) => {
            if (!condition) record('error', `Assertion failed${args.length ? ': ' + format(args) : ''}`);
        },
        count: (label = 'default') => {
            counts.set(label, (counts.get(label) ?? 0) + 1);
            record('log', `${label}: ${counts.get(label)}`);
        },
        countReset: (label = 'default') => counts.delete(label),
        time: (label = 'default') => timers.set(label, now()),
        timeEnd: (label = 'default') => {
            if (timers.has(label)) record('log', `${label}: ${(now() - timers.get(label)).toFixed(3)}ms`);
            timers.delete(label);
        },
        group: (...args) => {
            if (args.length) record('log', format(args));
            groupDepth++;
        },
        groupEnd: () => {
            groupDepth = Math.max(groupDepth - 1, 0);
        },
    };

    // Dialogs would block the page; say what would have happened instead.
    window.alert = (message = '') => record('warn', `alert() is turned off in the sandbox: ${message}`);
    window.confirm = (message = '') => {
        record('warn', `confirm() is turned off in the sandbox, so it returned false: ${message}`);
        return false;
    };
    window.prompt = (message = '') => {
        record('warn', `prompt() is turned off in the sandbox, so it returned null: ${message}`);
        return null;
    };

    // ---- Errors ----

    function errorLine(error) {
        const stack = String(error?.stack ?? '');
        const match = stack.match(/<anonymous>:(\d+):\d+/) ?? stack.match(/> eval:(\d+):\d+/);
        const line = match ? Number(match[1]) : typeof error?.line === 'number' ? error.line : null;
        if (line === null) return null;
        const learnerLine = line - 3;
        return learnerLine >= 1 && learnerLine <= sourceLines ? learnerLine : null;
    }

    function describeError(error) {
        if (error instanceof window.Error || error instanceof Error || (error && typeof error === 'object' && 'message' in error && 'name' in error)) {
            const line = errorLine(error);
            return `${error.name}: ${error.message}${line ? ` (line ${line})` : ''}`;
        }
        return `Uncaught ${inspect(error, 0, [], true)}`;
    }

    window.addEventListener('error', (event) => {
        event.preventDefault();
        record('error', `Uncaught ${describeError(event.error ?? { name: 'Error', message: event.message })}`);
    });
    window.addEventListener('unhandledrejection', (event) => {
        event.preventDefault();
        record('error', `Uncaught (in promise) ${describeError(event.reason)}`);
    });

    // ---- Stopping endless loops ----

    let guardMs = 2000;
    let taskStart = 0;
    let calls = 0;
    window.__learnpGuard = () => {
        if (++calls % 512 !== 0) return;
        const time = now();
        if (taskStart === 0) {
            taskStart = time;
            nativeSetTimeout(() => (taskStart = 0), 0);
        } else if (time - taskStart > guardMs) {
            taskStart = time;
            throw new window.RangeError(
                `A loop ran for more than ${guardMs / 1000} seconds, so it was stopped. Check that the loop's condition eventually becomes false.`,
            );
        }
    };

    // ---- Timers, so a run can wait for them ----

    const pending = new Set();
    window.setTimeout = (callback, ms, ...args) => {
        const id = nativeSetTimeout(() => {
            pending.delete(id);
            if (typeof callback === 'function') callback(...args);
        }, ms);
        pending.add(id);
        return id;
    };
    window.clearTimeout = (id) => {
        pending.delete(id);
        nativeClearTimeout(id);
    };
    window.setInterval = (callback, ms, ...args) => {
        const id = nativeSetInterval(callback, ms, ...args);
        pending.add(id);
        return id;
    };
    window.clearInterval = (id) => {
        pending.delete(id);
        nativeClearInterval(id);
    };
    const nextTask = () => new Promise((resolve) => nativeSetTimeout(resolve, 0));
    const sleep = (ms) => new Promise((resolve) => nativeSetTimeout(resolve, ms));

    // ---- Helpers for challenge checks ----

    function same(a, b, seen = []) {
        if (Object.is(a, b)) return true;
        if (typeof a !== 'object' || typeof b !== 'object' || a === null || b === null) return false;
        if (Array.isArray(a) !== Array.isArray(b)) return false;
        // Arrays and plain objects may come from another realm (a browser API, or jsdom's); compare those by shape.
        const plain = (value) => Array.isArray(value) || [null, 'Object'].includes(Object.getPrototypeOf(value)?.constructor?.name ?? null);
        if (Object.getPrototypeOf(a) !== Object.getPrototypeOf(b) && !(plain(a) && plain(b))) return false;
        if (seen.some(([x, y]) => x === a && y === b)) return true;
        const pairs = [...seen, [a, b]];
        if (a instanceof window.Date) return a.getTime() === b.getTime();
        if (a instanceof window.RegExp) return String(a) === String(b);
        if (isElement(a)) return false;
        if (a instanceof window.Error) return a.name === b.name && a.message === b.message;
        if (a instanceof window.Map) {
            if (a.size !== b.size) return false;
            return [...a].every(([key, value]) => b.has(key) && same(value, b.get(key), pairs));
        }
        if (a instanceof window.Set) {
            if (a.size !== b.size) return false;
            const rest = [...b];
            return [...a].every((item) => {
                const index = rest.findIndex((other) => same(item, other, pairs));
                if (index === -1) return false;
                rest.splice(index, 1);
                return true;
            });
        }
        if (Array.isArray(a)) {
            return a.length === b.length && a.every((item, i) => same(item, b[i], pairs));
        }
        const keysA = Object.keys(a);
        const keysB = Object.keys(b);
        return keysA.length === keysB.length && keysA.every((key) => Object.prototype.hasOwnProperty.call(b, key) && same(a[key], b[key], pairs));
    }

    const describeValue = (value) => inspect(value, 0, [], true);

    window.expectSame = (actual, expected) =>
        same(actual, expected) ? true : `Expected ${describeValue(expected)}, got ${describeValue(actual)}.`;

    window.expectThrows = async (callback, ErrorClass, messagePart) => {
        try {
            await callback();
        } catch (error) {
            if (ErrorClass && !(error instanceof ErrorClass)) {
                return `Expected ${ErrorClass.name} to be thrown, got ${describeError(error)}.`;
            }
            if (messagePart !== undefined && !String(error?.message).includes(messagePart)) {
                return `Expected the error message to include ${quote(String(messagePart))}, got ${quote(String(error?.message))}.`;
            }
            return true;
        }
        return `Expected ${ErrorClass ? ErrorClass.name : 'an error'} to be thrown, but nothing was thrown.`;
    };

    window.learnerOutput = () => entries.map((entry) => entry.text).join('\n');

    /**
     * Runs a changed copy of the learner's code (usually learnerCode() with one value replaced) and
     * returns the named top-level variables: `await runVariant(code, ['total'])` gives { total }.
     * What it prints is added to the output. JavaScript only.
     */
    window.runVariant = async (code, names) => {
        const program = `async function __learnpVariant() {\n'use strict';\n${code}\n;return { ${names.join(', ')} };\n}`;
        const parse = (text) => window.acorn.parse(text, { ecmaVersion: 'latest', sourceType: 'script' });
        (0, window.eval)(window.guardLoops(parse, program));
        return window.__learnpVariant();
    };
    window.inspect = describeValue;

    // ---- Pages written in HTML (the HTML & CSS course) ----

    /**
     * Makes the learner's HTML the page: a whole document or just some elements. Parsed with
     * DOMParser, so its scripts stay inert; its head (title, styles) joins the frame's head.
     */
    function installPage(html) {
        const doc = window.document;
        doc.getElementById('learnp-base')?.remove();
        const parsed = new window.DOMParser().parseFromString(html, 'text/html');
        for (const attribute of [...parsed.documentElement.attributes]) {
            doc.documentElement.setAttribute(attribute.name, attribute.value);
        }
        for (const node of [...parsed.head.childNodes]) {
            doc.head.append(doc.adoptNode(node));
        }
        for (const attribute of [...parsed.body.attributes]) {
            doc.body.setAttribute(attribute.name, attribute.value);
        }
        doc.body.replaceChildren(...[...parsed.body.childNodes].map((node) => doc.adoptNode(node)));
    }

    function find(selector) {
        const element = window.document.querySelector(selector);
        if (!element) {
            throw new window.Error(`Nothing on the page matches ${selector}`);
        }
        return element;
    }

    /** The computed value of a CSS property for the first element matching `selector`. */
    window.styleOf = (selector, property, pseudo = null) => window.getComputedStyle(find(selector), pseudo).getPropertyValue(property);

    /** The rendered size and position, in whole CSS pixels, of the first element matching `selector`. */
    window.boxOf = (selector) => {
        const rect = find(selector).getBoundingClientRect();
        const round = Math.round;
        return { width: round(rect.width), height: round(rect.height), top: round(rect.top), left: round(rect.left), right: round(rect.right), bottom: round(rect.bottom) };
    };

    // ---- Running a job ----

    function hasPage() {
        const body = window.document?.body;
        return Boolean(body && [...body.childNodes].some((node) => node.nodeType !== 3 || node.textContent.trim() !== ''));
    }

    async function run(job) {
        token = job.token;
        sourceLines = job.sourceLines;
        guardMs = job.guardMs ?? guardMs;
        window.learnerCode = () => job.source;

        const state = { checks: [] };
        const started = now();
        let error = null;

        try {
            if (typeof job.html === 'string') {
                installPage(job.html);
            }
            // Indirect eval runs the program in the frame's global scope.
            (0, window.eval)(job.program);
            await window.__learnpMain(state);
        } catch (thrown) {
            error = describeError(thrown);
        }

        if (job.mode === 'run') {
            const deadline = started + (job.waitMs ?? 5000);
            while (pending.size > 0 && now() < deadline) {
                await sleep(15);
            }
            post({
                token,
                type: 'done',
                report: {
                    entries,
                    error,
                    stillRunning: pending.size > 0,
                    durationMs: Math.round(now() - started),
                    hasPage: hasPage(),
                },
            });
            return;
        }

        const results = [];
        for (let index = 0; index < job.checkCount; index++) {
            let result;
            if (error !== null) {
                result = { passed: false, message: 'Fix the error in your code first.' };
            } else {
                await nextTask();
                try {
                    const value = await state.checks[index]();
                    result =
                        value === true
                            ? { passed: true, message: null }
                            : { passed: false, message: typeof value === 'string' ? value : 'This check did not pass.' };
                } catch (thrown) {
                    result = { passed: false, message: describeError(thrown) };
                }
            }
            results.push(result);
            post({ token, type: 'check', index, result });
        }

        post({
            token,
            type: 'done',
            report: { loaded: error === null, error, entries, checks: results, hasPage: hasPage() },
        });
    }

    window.__learnpReceive = (message) => {
        if (message?.type === 'job' && token === null) run(message);
    };
    window.addEventListener('message', (event) => {
        if (event.source === window.parent) window.__learnpReceive(event.data);
    });

    return window.__learnpReceive;
}
