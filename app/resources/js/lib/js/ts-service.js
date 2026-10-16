/*
 * Type-checks and compiles learner TypeScript with the real compiler (typescript@6, the last
 * version written in JavaScript). Used by engine.js through a worker on the web and in the
 * mobile app (see typescriptWorkerSource()), and directly in the content tests.
 *
 * This file is injected as source text into a worker, so installTsService() must stay
 * self-contained: no imports, and nothing from outside the function.
 *
 * `ts` is the compiler module; `libs` maps lib file names (lib.es2023.d.ts and everything it
 * references) to their text.
 */
export function installTsService(ts, libs) {
    const ROOT_LIB = 'lib.es2023.d.ts';
    const GLOBALS = 'learnp-globals.d.ts';
    const MAIN = 'main.ts';
    const target = ts.ScriptTarget.ES2023;

    // What the sandbox frame provides besides the language itself.
    const globals = `
declare var console: {
    log(...data: any[]): void;
    info(...data: any[]): void;
    debug(...data: any[]): void;
    warn(...data: any[]): void;
    error(...data: any[]): void;
    table(data: any): void;
    dir(item: any): void;
    assert(condition?: boolean, ...data: any[]): void;
    count(label?: string): void;
    countReset(label?: string): void;
    time(label?: string): void;
    timeEnd(label?: string): void;
    group(...data: any[]): void;
    groupEnd(): void;
};
declare function setTimeout(handler: (...args: any[]) => void, timeout?: number, ...args: any[]): number;
declare function clearTimeout(id: number | undefined): void;
declare function setInterval(handler: (...args: any[]) => void, timeout?: number, ...args: any[]): number;
declare function clearInterval(id: number | undefined): void;
declare function queueMicrotask(callback: () => void): void;
declare function structuredClone<T>(value: T): T;
declare var performance: { now(): number };
`;

    const options = {
        target,
        lib: [ROOT_LIB],
        strict: true,
        skipLibCheck: true,
        types: [],
        noEmitOnError: false,
        module: ts.ModuleKind.ESNext,
        newLine: ts.NewLineKind.LineFeed,
    };

    const cache = new Map();
    function sharedFile(name, text) {
        if (!cache.has(name)) cache.set(name, ts.createSourceFile(name, text, target, true));
        return cache.get(name);
    }

    function createHost(mainText, emitted) {
        return {
            getSourceFile(name) {
                if (name === MAIN) return ts.createSourceFile(MAIN, mainText, target, true);
                if (name === GLOBALS) return sharedFile(GLOBALS, globals);
                if (name in libs) return sharedFile(name, libs[name]);
                return undefined;
            },
            getDefaultLibFileName: () => ROOT_LIB,
            getDefaultLibLocation: () => '',
            writeFile: (name, text) => {
                if (name.endsWith('.js')) emitted.js = text;
            },
            getCurrentDirectory: () => '',
            getDirectories: () => [],
            directoryExists: () => true,
            fileExists: (name) => name === MAIN || name === GLOBALS || name in libs,
            readFile: (name) => (name === MAIN ? mainText : name === GLOBALS ? globals : libs[name]),
            getCanonicalFileName: (name) => name,
            useCaseSensitiveFileNames: () => true,
            getNewLine: () => '\n',
        };
    }

    let previous;
    function program(code, emitted = {}) {
        // A trailing export makes the code a module, so it may use top-level await, as JavaScript in
        // the sandbox can. It comes after the code, so line numbers don't change.
        const mainText = `${code}\nexport {};\n`;
        previous = ts.createProgram({ rootNames: [GLOBALS, MAIN], options, host: createHost(mainText, emitted), oldProgram: previous });
        return previous;
    }

    function diagnosticsOf(built) {
        const main = built.getSourceFile(MAIN);
        return ts
            .getPreEmitDiagnostics(built, main)
            .filter((d) => d.file?.fileName === MAIN && d.category === ts.DiagnosticCategory.Error)
            .map((d) => {
                const { line, character } = ts.getLineAndCharacterOfPosition(d.file, d.start ?? 0);
                return {
                    start: d.start ?? 0,
                    line: line + 1,
                    column: character + 1,
                    code: d.code,
                    message: ts.flattenDiagnosticMessageText(d.messageText, '\n'),
                };
            });
    }

    function compile(code, snippets = []) {
        const emitted = {};
        const built = program(code, emitted);
        const diagnostics = diagnosticsOf(built).map(({ start, ...rest }) => rest);

        if (diagnostics.length) {
            return { diagnostics, js: '', snippets: [] };
        }

        built.emit(built.getSourceFile(MAIN));
        const js = (emitted.js ?? '').replace(/^"use strict";\n/, '').replace(/\nexport \{\};\s*$/, '\n');

        const results = snippets.map((snippet) => {
            const found = diagnosticsOf(program(`${code}\n${snippet.code}`));
            if (snippet.kind === 'rejects') {
                return found.length
                    ? { passed: true, message: null }
                    : { passed: false, message: `TypeScript accepts this, but it should be a type error:\n${snippet.code.trim()}` };
            }
            return found.length
                ? { passed: false, message: `TypeScript rejects this: ${found[0].message}\n${snippet.code.trim()}` }
                : { passed: true, message: null };
        });

        return { diagnostics: [], js, snippets: results };
    }

    return { compile };
}
