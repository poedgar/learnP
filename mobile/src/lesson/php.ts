import type { PhpCheck } from '../api/types';

/** Snippets from the theory may leave out the opening tag; the sandbox needs it. */
export function withOpeningTag(code: string): string {
  return code.trimStart().startsWith('<?php') ? code : `<?php\n\n${code}`;
}

/** The verdict line for a PHP challenge, worded as in the web app's ChallengesStep. */
export function phpCheckMessage(check: PhpCheck): string {
  const passedCount = check.checks.filter((c) => c.passed).length;

  if (check.passed) {
    return `Correct! All ${check.checks.length} checks pass.`;
  }

  return check.loaded
    ? `${passedCount} of ${check.checks.length} checks pass. See what's missing below.`
    : 'Your code has an error, so the checks could not run yet.';
}
