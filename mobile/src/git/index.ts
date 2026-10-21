/**
 * Types for the simulated Git (engine.js and git-sim.js are copies of the web app's
 * app/resources/js/lib/git, kept in step by scripts/sync-js-runtime.mjs). It's plain
 * JavaScript, so Git lessons run right here in the app, with no server and no WebView.
 */
import type { GitCheck, GitRun } from '../api/types';
import { checkGit as check, runGit as run } from './engine';

export function runGit(script: string): GitRun {
  return run(script) as GitRun;
}

export function checkGit(script: string, checks: { label: string; code?: string }[]): GitCheck {
  return check(script, checks.map((item) => ({ label: item.label, code: item.code ?? '' }))) as GitCheck;
}
