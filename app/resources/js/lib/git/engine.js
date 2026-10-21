/*
 * Runs Git lessons: a learner's shell script goes through the simulated Git in ./git-sim.js,
 * a fresh machine each time. It's plain JavaScript with no DOM, so the web app runs it in the
 * page, the mobile app in its own JavaScript engine (a copy kept in step by
 * scripts/sync-js-runtime.mjs), and the content tests in Node.
 *
 * A challenge check is the body of a function that returns true, or a message saying what's
 * missing. It is given:
 *   repo(dir?)    a snapshot of the repository (see inspect() in git-sim.js): the one the script
 *                 ended in, or the one at dir. Throws a readable error if there is none.
 *   git(command)  what one more command prints, run after the learner's script.
 *   output        everything the script printed, one string.
 */
import { createGitShell } from './git-sim.js';

/** Runs a script; entries are what a terminal shows: { level: 'command' | 'log' | 'error', text }. */
export function runGit(script) {
    const shell = createGitShell();
    const entries = [];
    const records = shell.run(script, (entry) => entries.push(entry));

    return {
        entries,
        failed: records.filter((record) => record.exitCode !== 0).map((record) => record.text),
        unsupported: records.filter((record) => record.unsupported).map((record) => record.text),
    };
}

/** Runs a script, then each check against the result. Same report shape as the PHP and JS checks. */
export function checkGit(script, checks) {
    const shell = createGitShell();
    const entries = [];
    let error = null;
    try {
        shell.run(script, (entry) => entries.push(entry));
    } catch (e) {
        error = e.message;
    }
    const output = entries.filter((entry) => entry.level !== 'command').map((entry) => entry.text).join('\n');

    const results = checks.map(({ label, code }) => {
        try {
            const check = new Function('repo', 'git', 'output', code);
            const result = check((dir = null) => shell.inspect(dir), (command) => shell.capture(command), output);
            return result === true
                ? { label, passed: true, message: null }
                : { label, passed: false, message: typeof result === 'string' ? result : 'This check did not pass.' };
        } catch (e) {
            return { label, passed: false, message: e.message };
        }
    });

    return {
        loaded: error === null,
        error,
        output,
        entries,
        checks: results,
        passed: error === null && results.every((check) => check.passed),
    };
}
