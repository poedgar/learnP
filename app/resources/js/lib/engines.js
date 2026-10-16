import { api } from '../api';
import { checkChallenge, runScript } from './php/sandbox';
import { checkCode, runCode } from './js/sandbox';

/** The verdict shown above a check report from a sandbox that runs checks itself. */
function withMessage(report) {
    const passedCount = report.checks.filter((c) => c.passed).length;
    const message = report.passed
        ? `Correct! All ${report.checks.length} checks pass.`
        : !report.loaded
          ? 'Your code has an error, so the checks could not run yet.'
          : `${passedCount} of ${report.checks.length} checks pass. See what's missing below.`;
    return { ...report, message };
}

/**
 * How each language runs code and checks challenge answers. SQL runs on the server; PHP runs in
 * the browser (see ./php); JavaScript, TypeScript and HTML run in a sandboxed frame (see ./js). Used by
 * the lesson challenges and the review page.
 *
 * run(code, { host }) resolves to { sql }, { php } or { js }. For JavaScript, a host element shows
 * the page the code builds (see runCode() in ./js/sandbox).
 */
export function engineFor(language, lessonId) {
    if (language === 'sql') {
        return {
            run: async (code) => ({ sql: await api.run(lessonId, code) }),
            check: (challenge, code) => api.check(challenge.id, code),
        };
    }

    if (runsInFrame(language)) {
        return {
            run: async (code, { host = null } = {}) => ({ js: await runCode(code, { language, host }) }),
            check: async (challenge, code) => withMessage(await checkCode(code, challenge.checks, { language })),
        };
    }

    return {
        run: async (code) => ({ php: await runScript(code) }),
        check: async (challenge, code) => withMessage(await checkChallenge(code, challenge.checks)),
    };
}

/** True for languages whose code runs in a sandbox frame on the device: JavaScript, TypeScript and HTML. */
export function runsInFrame(language) {
    return ['javascript', 'typescript', 'html'].includes(language);
}
