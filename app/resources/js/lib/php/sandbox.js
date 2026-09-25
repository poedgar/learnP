import harness from '../../../php/check-harness.php?raw';
import { runPhp, warmUp } from './runner';

export { warmUp };

const REPORT_MARKER = '\n@@LEARNP_REPORT@@\n';

/** Snippets from the theory may leave out the opening tag; the sandbox needs it. */
export function withOpeningTag(code) {
    return code.trimStart().startsWith('<?php') ? code : `<?php\n\n${code}`;
}

/**
 * Runs a learner's script.
 * @returns {Promise<{ output: string, fatal: boolean, durationMs: number }>}
 */
export async function runScript(code) {
    await warmUp();
    const started = performance.now();
    const { stdout, exitCode } = await runPhp({ '/sandbox/main.php': code }, '/sandbox/main.php');

    return {
        output: stdout.replace(/^\n+/, ''),
        fatal: exitCode !== 0,
        durationMs: Math.round(performance.now() - started),
    };
}

/**
 * Runs a learner's answer to a PHP challenge, then the challenge's checks,
 * using resources/php/check-harness.php.
 * @returns {Promise<{ passed: boolean, error: ?string, output: string, checks: Array<{ label: string, passed: boolean, message: ?string }> }>}
 */
export async function checkChallenge(code, checks) {
    const { stdout } = await runPhp(
        {
            '/sandbox/main.php': code,
            '/sandbox/checks.json': JSON.stringify(checks),
            '/sandbox/harness.php': harness,
        },
        '/sandbox/harness.php',
    );

    const [, report] = stdout.split(REPORT_MARKER);
    if (report === undefined) {
        throw new Error('The checker could not read your code. Run it first to look for errors.');
    }

    const result = JSON.parse(report);
    return {
        ...result,
        output: result.output.replace(/^\n+/, ''),
        passed: result.loaded && result.checks.every((check) => check.passed),
    };
}
