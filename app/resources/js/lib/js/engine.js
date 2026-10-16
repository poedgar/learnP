/*
 * Runs learner JavaScript and TypeScript, and checks challenge answers, in a sandbox frame.
 * Environment-independent: the web app, the mobile app (a copy in mobile/src/js-runtime) and the
 * content tests each supply how to open a frame and how to reach the TypeScript compiler.
 *
 *   const engine = createJsEngine({ openFrame, typescript });
 *   await engine.run(code, { language: 'javascript' });
 *   await engine.check(code, challenge.checks, { language: 'typescript' });
 *
 * openFrame() resolves to { send(message), onMessage(callback), close() } for a fresh frame that
 * runs frame-runtime.js. typescript.compile(code, snippets) resolves to { diagnostics, js, snippets }
 * (see ts-service.js).
 */
import { buildProgram, syntaxCheck } from './prepare.js';

/** A single loop may run this long without a break before it's stopped (see frame-runtime.js). */
const GUARD_MS = 2000;
/** How long a run waits for timers (setTimeout and friends) to finish. */
const WAIT_MS = 5000;
/** Nothing may take longer than this; the frame is thrown away. */
const HARD_LIMIT_MS = 10000;

let nextToken = 1;

/**
 * The script a sandbox frame runs: acorn and the loop guard (so runVariant() can guard code too),
 * then frame-runtime.js, installed. It talks to the parent with postMessage, unless the frame's
 * window has a __learnpSend function.
 *
 * `sources` holds the source text of frame-runtime.js (runtime), loop-guard.js (loopGuard) and
 * acorn's dist/acorn.js (acorn).
 */
export function runtimeScript({ runtime, loopGuard, acorn }) {
    return [
        acorn,
        loopGuard.replace(/^export function guardLoops/m, 'function guardLoops'),
        runtime.replace(/^export function installRuntime/m, 'function installRuntime'),
        'installRuntime(window, window.__learnpSend);',
    ].join('\n;\n');
}

/** The ts-service.js source with the service installed as `service`, for a classic worker that has loaded `ts` and the libs. */
export function typescriptWorkerScript(rawServiceSource, scriptUrls) {
    return [
        `importScripts(${scriptUrls.map((url) => JSON.stringify(url)).join(', ')});`,
        rawServiceSource.replace(/^export function installTsService/m, 'function installTsService'),
        'const service = installTsService(self.ts, self.__learnpTsLibs);',
        'self.onmessage = ({ data }) => {',
        '    try {',
        '        self.postMessage({ id: data.id, ok: true, result: service.compile(data.code, data.snippets) });',
        '    } catch (error) {',
        '        self.postMessage({ id: data.id, ok: false, error: String(error && error.message ? error.message : error) });',
        '    }',
        '};',
    ].join('\n');
}

/** A complete HTML document for a sandbox frame (see runtimeScript() for `sources`). */
export function frameHtml(sources) {
    const script = runtimeScript(sources).replace(/<\/(script)/gi, '<\\/$1');
    return [
        '<!doctype html><html lang="en"><head><meta charset="utf-8">',
        '<meta name="viewport" content="width=device-width, initial-scale=1">',
        '<style id="learnp-base">body{font-family:system-ui,sans-serif;line-height:1.5;color:#18233f;margin:1rem}',
        'button,input,select,textarea{font:inherit}</style>',
        `<script>${script}</script></head><body></body></html>`,
    ].join('');
}

function outputOf(entries) {
    return entries.map((entry) => entry.text).join('\n');
}

function describeTypeErrors(diagnostics) {
    return diagnostics.map((d) => `Type error on line ${d.line}: ${d.message}`).join('\n');
}

/**
 * Sends one job to a fresh frame and waits for its report. Resolves to
 * { report, timedOut, checkResults, entries, frame } where frame is still open if keepFrame is set.
 * `width` asks for a frame of that many CSS pixels, for checks of responsive layouts.
 */
async function runJob(openFrame, job, { keepFrame = false, width = null } = {}) {
    const frame = await openFrame({ width });
    const token = `t${nextToken++}-${Math.random().toString(36).slice(2)}`;
    const entries = [];
    const checkResults = [];

    const outcome = await new Promise((resolve) => {
        const timer = setTimeout(() => resolve({ report: null, timedOut: true }), HARD_LIMIT_MS);
        frame.onMessage((message) => {
            if (message?.token !== token) return;
            if (message.type === 'log') entries.push(message.entry);
            if (message.type === 'check') checkResults[message.index] = message.result;
            if (message.type === 'done') {
                clearTimeout(timer);
                resolve({ report: message.report, timedOut: false });
            }
        });
        frame.send({ type: 'job', token, ...job });
    });

    if (!keepFrame || outcome.timedOut) {
        frame.close();
    }

    return { ...outcome, entries: outcome.report?.entries ?? entries, checkResults, frame: keepFrame && !outcome.timedOut ? frame : null };
}

/**
 * @param {{ openFrame: (options: { width: ?number }) => Promise<{ send(message: object): void, onMessage(callback: (message: any) => void): void, close(): void }>,
 *   typescript?: { compile(code: string, snippets?: object[]): Promise<any> } | null }} options
 */
export function createJsEngine({ openFrame, typescript = null }) {
    async function compile(code, language, snippets = []) {
        // HTML is the page itself; only the checks run as JavaScript.
        if (language === 'html') return { js: '', html: code, diagnostics: [], snippets: [] };
        if (language !== 'typescript') return { js: code, diagnostics: [], snippets: [] };
        if (!typescript) throw new Error('TypeScript is not available here.');
        return typescript.compile(code, snippets);
    }

    return {
        /**
         * Runs code and reports what it printed.
         * @returns {Promise<{ entries: {level: string, text: string}[], output: string, error: ?string,
         *   typeErrors: {line: number, message: string}[], stillRunning: boolean, durationMs: number,
         *   hasPage: boolean, frame: ?object }>}
         * With keepFrame, the frame of a run whose page has content stays open for the caller to show and close.
         */
        async run(code, { language = 'javascript', keepFrame = false } = {}) {
            const started = Date.now();
            const empty = { entries: [], output: '', error: null, typeErrors: [], stillRunning: false, hasPage: false, frame: null };

            const compiled = await compile(code, language);
            if (compiled.diagnostics.length) {
                return { ...empty, typeErrors: compiled.diagnostics, durationMs: Date.now() - started };
            }

            const syntax = syntaxCheck(compiled.js);
            if (!syntax.ok) {
                return { ...empty, error: syntax.error, durationMs: Date.now() - started };
            }

            const { report, timedOut, entries, frame } = await runJob(
                openFrame,
                { mode: 'run', html: compiled.html, program: buildProgram(compiled.js), source: code, sourceLines: compiled.js.split('\n').length, guardMs: GUARD_MS, waitMs: WAIT_MS },
                { keepFrame },
            );

            if (timedOut) {
                return {
                    ...empty,
                    entries,
                    output: outputOf(entries),
                    error: `Your code ran for more than ${HARD_LIMIT_MS / 1000} seconds and was stopped.`,
                    durationMs: Date.now() - started,
                };
            }

            const keep = report.hasPage ? frame : null;
            if (frame && !keep) frame.close();

            return {
                entries: report.entries,
                output: outputOf(report.entries),
                error: report.error,
                typeErrors: [],
                stillRunning: report.stillRunning,
                durationMs: report.durationMs,
                hasPage: report.hasPage,
                frame: keep,
            };
        },

        /**
         * Runs code, then the challenge's checks.
         * Checks are { label, code } (JavaScript returning true or a message), and for TypeScript also
         * { label, compiles } or { label, rejects }: TypeScript appended to the code that must
         * type-check, or must be a type error. A check with a `width` runs in a frame of that width
         * (for media queries); checks are grouped by width, one frame each.
         * @returns {Promise<{ passed: boolean, loaded: boolean, error: ?string, output: string,
         *   entries: object[], checks: {label: string, passed: boolean, message: ?string}[] }>}
         */
        async check(code, checks, { language = 'javascript' } = {}) {
            const snippets = checks
                .map((check, index) => ({ index, check }))
                .filter(({ check }) => check.compiles !== undefined || check.rejects !== undefined);
            const runtime = checks.map((check, index) => ({ index, check })).filter(({ check }) => check.code !== undefined);

            const notLoaded = (error, entries = []) => ({
                passed: false,
                loaded: false,
                error,
                entries,
                output: outputOf(entries),
                checks: checks.map((check) => ({ label: check.label, passed: false, message: 'Fix the error in your code first.' })),
            });

            const compiled = await compile(
                code,
                language,
                snippets.map(({ check }) => (check.compiles !== undefined ? { kind: 'compiles', code: check.compiles } : { kind: 'rejects', code: check.rejects })),
            );
            if (compiled.diagnostics.length) {
                return notLoaded(describeTypeErrors(compiled.diagnostics));
            }

            const syntax = syntaxCheck(compiled.js);
            if (!syntax.ok) return notLoaded(syntax.error);

            const results = checks.map((check) => ({ label: check.label, passed: false, message: null }));
            snippets.forEach(({ index }, i) => Object.assign(results[index], compiled.snippets[i]));

            // Even with only type-level checks, the code runs, so a runtime error still counts.
            const widths = [...new Set([null, ...runtime.map(({ check }) => check.width ?? null)])];
            let entries = null;

            for (const width of widths) {
                const group = runtime.filter(({ check }) => (check.width ?? null) === width);
                if (group.length === 0 && width !== null) continue;

                const job = await runJob(
                    openFrame,
                    {
                        mode: 'check',
                        html: compiled.html,
                        program: buildProgram(compiled.js, group.map(({ check }) => check.code)),
                        source: code,
                        sourceLines: compiled.js.split('\n').length,
                        checkCount: group.length,
                        guardMs: GUARD_MS,
                    },
                    { width },
                );
                entries ??= job.entries;

                if (job.timedOut) {
                    const finished = job.checkResults.length;
                    group.forEach(({ index }, i) => {
                        if (i < finished) Object.assign(results[index], job.checkResults[i]);
                        else if (i === finished) results[index].message = `This check didn't finish within ${HARD_LIMIT_MS / 1000} seconds.`;
                        else results[index].message = "Not run, because an earlier check didn't finish.";
                    });
                    continue;
                }

                if (!job.report.loaded) {
                    return notLoaded(job.report.error, job.report.entries);
                }

                group.forEach(({ index }, i) => Object.assign(results[index], job.report.checks[i]));
            }

            return {
                passed: results.every((result) => result.passed),
                loaded: true,
                error: null,
                entries,
                output: outputOf(entries),
                checks: results,
            };
        },
    };
}
