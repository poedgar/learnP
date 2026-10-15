import { api } from '../api';
import { checkChallenge, runScript } from './php/sandbox';

/**
 * How each language runs code and checks challenge answers. SQL runs on the server;
 * PHP runs in the browser (see ./php). Used by the lesson challenges and the review page.
 */
export function engineFor(language, lessonId) {
    if (language === 'sql') {
        return {
            run: async (code) => ({ sql: await api.run(lessonId, code) }),
            check: (challenge, code) => api.check(challenge.id, code),
        };
    }

    return {
        run: async (code) => ({ php: await runScript(code) }),
        async check(challenge, code) {
            const report = await checkChallenge(code, challenge.checks);
            const passedCount = report.checks.filter((c) => c.passed).length;
            const message = report.passed
                ? `Correct! All ${report.checks.length} checks pass.`
                : !report.loaded
                  ? 'Your code has an error, so the checks could not run yet.'
                  : `${passedCount} of ${report.checks.length} checks pass. See what's missing below.`;
            return { ...report, message };
        },
    };
}
