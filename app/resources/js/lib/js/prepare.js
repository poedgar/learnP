/*
 * Turns a learner's JavaScript into a program the sandbox frame can run (see frame-runtime.js).
 * Shared by the web app, the mobile app (a copy in mobile/src/js-runtime) and the content tests.
 *
 * - Syntax errors are reported with the learner's line and column, before anything runs.
 * - Every loop gets a call to __learnpGuard(), which stops a loop that runs too long, so an
 *   endless loop can't freeze the page (sandbox frames share the page's thread).
 * - The learner's code and the challenge's checks become one async function, so top-level
 *   `await` works and each check can see the learner's top-level let, const, class and function
 *   declarations. Checks are hoisted function declarations, so they exist even if the learner's
 *   code throws part-way through.
 */
import { parse } from 'acorn';
import { guardLoops } from './loop-guard.js';

/** The lines that come before the learner's code in the program; error lines are shifted back by this much. */
export const LEARNER_LINE_OFFSET = 3;

function parseScript(code) {
    return parse(code, {
        ecmaVersion: 'latest',
        sourceType: 'script',
        allowAwaitOutsideFunction: true,
        locations: true,
    });
}

/**
 * Checks the learner's code for syntax errors.
 * @returns {{ ok: true } | { ok: false, error: string, line: number, column: number }}
 */
export function syntaxCheck(code) {
    try {
        // The program runs in strict mode, so check the code that way too (one extra line first).
        parseScript(`'use strict';\n${code}`);
        return { ok: true };
    } catch (error) {
        const line = Math.max((error.loc?.line ?? 2) - 1, 1);
        const column = (error.loc?.column ?? 0) + 1;
        const message = String(error.message).replace(/\s*\(\d+:\d+\)$/, '');
        return { ok: false, error: `SyntaxError: ${message} (line ${line})`, line, column };
    }
}

/**
 * Builds the program for the frame: an async function expression taking the frame's state object.
 * Throws an Error if the learner's code or a check has a syntax error.
 * @param {string} code the learner's code
 * @param {string[]} checks the bodies of the challenge's checks (each returns true or a message)
 */
export function buildProgram(code, checks = []) {
    const syntax = syntaxCheck(code);
    if (!syntax.ok) {
        throw Object.assign(new Error(syntax.error), { syntax });
    }

    const names = checks.map((_, i) => `__learnpCheck${i}`);
    const program = [
        'async function __learnpMain(__learnp) {',
        "'use strict';",
        `__learnp.checks = [${names.join(', ')}];`,
        code,
        ...checks.map((body, i) => `async function ${names[i]}() {\n${body}\n}`),
        '}',
    ].join('\n');

    try {
        return guardLoops(parseScript, program);
    } catch (error) {
        throw new Error(`A check for this challenge has a syntax error: ${error.message}`);
    }
}
