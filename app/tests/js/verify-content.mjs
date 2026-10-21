/*
 * Checks JavaScript and TypeScript lessons with the real sandbox engine (in jsdom). Used by
 * tests/Feature/JsLessonContentTest.php, and by hand while writing lessons:
 *
 *   node tests/js/verify-content.mjs lessons.json [--verbose]
 *
 * The file holds [{ name, language, content, quiz: [{ question, code }], challenges: [{ title,
 * starter_code, solution, checks }] }] ("-" reads it from stdin). Prints a JSON report:
 * { problems: [...], snippets: [{ lesson, question, output }], skipped: [...] }, or readable text
 * with --verbose.
 *
 * JavaScript and TypeScript run in jsdom. HTML lessons need real layout, so they run in Chromium
 * (see browser-engine.mjs); without one, they're listed in `skipped`.
 *
 * - Every theory example in the lesson's language runs without an error, except those starting
 *   with "// Error:", which must fail (an error, or a type error in TypeScript).
 * - Quiz snippets run on their own: none may refer to something that isn't defined.
 * - Every challenge's solution passes all its checks, and every starter fails at least one.
 */
import { readFileSync } from 'node:fs';
import { closeBrowser, createBrowserEngine, findChromium } from './browser-engine.mjs';
import { engine as nodeEngine } from './node-engine.mjs';
import { checkGit, runGit } from '../../resources/js/lib/git/engine.js';

const [file = '-', ...flags] = process.argv.slice(2);
const verbose = flags.includes('--verbose');
const lessons = JSON.parse(readFileSync(file === '-' ? 0 : file, 'utf8'));

const problems = [];
const snippets = [];
const skipped = [];
const chromium = lessons.some((lesson) => lesson.language === 'html') ? findChromium() : null;
const browserEngine = chromium ? await createBrowserEngine(chromium) : null;
const log = (...lines) => verbose && console.log(...lines);

function examples(content, language) {
    const fence = language;
    return [...content.matchAll(new RegExp('```' + fence + '\\n([\\s\\S]*?)```', 'g'))].map((m) => m[1]);
}

/** Git lessons run the simulated Git directly; a command that fails or isn't supported is an error. */
const gitEngine = {
    async run(code) {
        const result = runGit(code);
        const output = result.entries.filter((entry) => entry.level !== 'command').map((entry) => entry.text).join('\n');
        const error = result.unsupported.length
            ? `this Git doesn't support: ${result.unsupported.join('; ')}`
            : result.failed.length
              ? `a command failed: ${result.failed.join('; ')}`
              : null;
        return { output, error, typeErrors: [], entries: [], stillRunning: false };
    },
    async check(code, checks) {
        return checkGit(code, checks);
    },
};

function failure(result) {
    if (result.typeErrors.length) return result.typeErrors.map((e) => `type error on line ${e.line}: ${e.message}`).join('; ');
    if (result.error) return result.error;
    const uncaught = result.entries.find((entry) => entry.text.startsWith('Uncaught'));
    return uncaught ? uncaught.text : null;
}

for (const lesson of lessons) {
    const { name, language } = lesson;
    log(`\n== ${name}`);
    const engine = language === 'git' ? gitEngine : language === 'html' ? browserEngine : nodeEngine;
    if (!engine) {
        skipped.push(name);
        log('  skipped: no Chromium for HTML lessons');
        continue;
    }

    const blocks = examples(lesson.content, language);
    if (blocks.length === 0) problems.push(`${name}: no ${language} examples in the theory`);
    for (const [i, code] of blocks.entries()) {
        const result = await engine.run(code, { language });
        const failed = failure(result);
        const shouldFail = code.startsWith(language === 'git' ? '# Error:' : '// Error:');
        if (Boolean(failed) !== shouldFail) {
            problems.push(`${name}: theory example ${i + 1} ${failed ? `fails: ${failed}` : 'runs, but starts with // Error:'}\n${code}`);
        }
        if (result.stillRunning) problems.push(`${name}: theory example ${i + 1} leaves a timer running`);
        log(`  example ${i + 1}: ${failed ?? 'ok'}`);
    }

    for (const question of lesson.quiz) {
        if (!question.code) continue;
        const result = await engine.run(question.code, { language });
        const output = [result.output, failure(result)].filter(Boolean).join('\n');
        if (/is not defined/.test(output)) {
            problems.push(`${name}: a quiz snippet refers to something it doesn't define: ${question.question}\n${output}`);
        }
        // An answer that is a single code span is the exact output (lines joined by spaces).
        const exact = /^`([^`]*)`$/.exec(question.answer ?? '');
        if (exact && exact[1] !== output.replace(/\n/g, ' ')) {
            problems.push(`${name}: the answer to "${question.question}" is ${question.answer}, but the snippet prints ${JSON.stringify(output)}`);
        }
        snippets.push({ lesson: name, question: question.question, output });
        log(`  QUIZ ${question.question}\n     OUTPUT: ${output.replace(/\n/g, '⏎')}\n     ANSWER: ${question.answer ?? ''}`);
    }

    for (const challenge of lesson.challenges) {
        let solved;
        try {
            solved = await engine.check(challenge.solution, challenge.checks, { language });
        } catch (error) {
            problems.push(`${name} / ${challenge.title}: the checks could not run: ${error.message}`);
            continue;
        }
        if (!solved.passed) {
            const failing = solved.checks.filter((c) => !c.passed).map((c) => `${c.label}: ${c.message}`);
            problems.push(`${name} / ${challenge.title}: the solution fails${solved.error ? ` (${solved.error})` : ''}: ${failing.join(' | ')}`);
        }
        const started = await engine.check(challenge.starter_code ?? '', challenge.checks, { language });
        if (started.passed) problems.push(`${name} / ${challenge.title}: the starter code passes every check`);
        log(`  challenge ${challenge.title}: solution ${solved.passed ? 'passes' : 'FAILS'}, starter ${started.passed ? 'PASSES' : 'fails'} (${challenge.checks.length} checks)`);
    }
}

await closeBrowser();

if (verbose) {
    if (skipped.length) console.log(`\nSKIPPED (no Chromium): ${skipped.join(', ')}`);
    console.log(problems.length ? `\nPROBLEMS: ${problems.length}\n${problems.join('\n\n')}` : '\nALL GOOD');
} else {
    console.log(JSON.stringify({ problems, snippets, skipped }));
}
process.exit(0);
