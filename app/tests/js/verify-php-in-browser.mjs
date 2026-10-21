/*
 * Runs every PHP lesson through the PHP that learners really use: the web app's built
 * WebAssembly worker (PHP 8.4) in headless Chromium. The PHPUnit tests use the PHP CLI,
 * which can be a different version and has different extensions, so this catches content
 * that only works on the developer's machine.
 *
 *   npm run build
 *   php artisan content:export lessons.json
 *   node tests/js/verify-php-in-browser.mjs lessons.json
 *
 * Checks, like PhpLessonContentTest and PhpChallengeHarnessTest: every ```php example runs
 * (examples starting with "// Error:" must fail), every starter runs cleanly, quiz snippets
 * run on their own, every solution passes all its checks and every starter fails one.
 * Exits with 1 if anything is wrong. Needs Chromium; see browser-engine.mjs.
 */
import { createServer } from 'node:http';
import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright-core';
import { findChromium } from './browser-engine.mjs';

const WORKERS = 4;
const app = fileURLToPath(new URL('../..', import.meta.url));
const publicDir = join(app, 'public');
const [file = '-'] = process.argv.slice(2);
const lessons = JSON.parse(readFileSync(file === '-' ? 0 : file, 'utf8'));
const harness = readFileSync(join(app, 'resources/php/check-harness.php'), 'utf8');

const assets = join(publicDir, 'build/assets');
const workerFile = existsSync(assets) ? readdirSync(assets).find((name) => name.startsWith('worker-')) : null;
if (!workerFile) {
    console.error('No built PHP worker in public/build/assets. Run npm run build first.');
    process.exit(1);
}

const executablePath = findChromium();
if (!executablePath) {
    console.error('No Chromium found. Install one with npx playwright install chromium-headless-shell, or set LEARNP_CHROMIUM.');
    process.exit(1);
}

// A tiny static server for public/, so the worker and its WebAssembly load from a real origin.
const types = { '.js': 'text/javascript', '.mjs': 'text/javascript', '.wasm': 'application/wasm', '.json': 'application/json', '.html': 'text/html', '.dat': 'application/octet-stream', '.so': 'application/octet-stream' };
const server = createServer((request, response) => {
    const path = normalize(decodeURIComponent(new URL(request.url, 'http://localhost').pathname));
    const target = join(publicDir, path);
    if (path === '/' || !target.startsWith(publicDir) || !existsSync(target)) {
        response.writeHead(path === '/' ? 200 : 404, { 'Content-Type': 'text/html' });
        response.end('<!doctype html><title>verify</title>');
        return;
    }
    response.writeHead(200, { 'Content-Type': types[extname(target)] ?? 'application/octet-stream' });
    response.end(readFileSync(target));
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const origin = `http://127.0.0.1:${server.address().port}`;

const browser = await chromium.launch({ executablePath, headless: true });
try {
    const page = await browser.newPage();
    await page.goto(origin);

    const problems = await page.evaluate(
        async ({ lessons, harness, workerUrl, workerCount }) => {
            const REPORT = '\n@@LEARNP_REPORT@@\n';
            const makeWorker = async () => {
                const worker = new Worker(workerUrl, { type: 'module' });
                const pending = new Map();
                let nextId = 0;
                worker.onmessage = ({ data }) => {
                    const request = pending.get(data.id);
                    pending.delete(data.id);
                    data.ok ? request.resolve(data) : request.reject(new Error(data.error));
                };
                const send = (message) =>
                    new Promise((resolve, reject) => {
                        const id = ++nextId;
                        pending.set(id, { resolve, reject });
                        worker.postMessage({ id, ...message });
                    });
                await send({ type: 'boot' });
                return send;
            };
            const withTag = (code) => (code.trimStart().startsWith('<?php') ? code : `<?php\n\n${code}`);

            const jobs = [];
            for (const lesson of lessons) {
                const examples = [...lesson.content.matchAll(/```php\n([\s\S]*?)```/g)].map((match) => match[1]);
                for (const code of examples) jobs.push({ kind: 'example', lesson: lesson.name, code });
                jobs.push({ kind: 'starter', lesson: lesson.name, code: lesson.starter });
                for (const code of lesson.quiz) jobs.push({ kind: 'quiz', lesson: lesson.name, code });
                for (const challenge of lesson.challenges) {
                    jobs.push({ kind: 'solution', lesson: lesson.name, challenge });
                    jobs.push({ kind: 'challenge-starter', lesson: lesson.name, challenge });
                }
            }

            const run = async (send, code) => {
                const { stdout, exitCode } = await send({ type: 'run', files: { '/sandbox/main.php': code }, entry: '/sandbox/main.php' });
                return { output: stdout, fatal: exitCode !== 0 };
            };
            const check = async (send, code, checks) => {
                const { stdout } = await send({
                    type: 'run',
                    files: { '/sandbox/main.php': code, '/sandbox/checks.json': JSON.stringify(checks), '/sandbox/harness.php': harness },
                    entry: '/sandbox/harness.php',
                });
                const [, report] = stdout.split(REPORT);
                if (report === undefined) return { passed: false, error: stdout.slice(0, 300), checks: [] };
                const result = JSON.parse(report);
                return { ...result, passed: result.loaded && result.checks.every((c) => c.passed) };
            };

            const problems = [];
            const handle = async (send, job) => {
                if (job.kind === 'example') {
                    const result = await run(send, withTag(job.code));
                    const failed = result.fatal || /Fatal error|Parse error/.test(result.output);
                    if (failed !== job.code.startsWith('// Error:')) {
                        problems.push(`${job.lesson}: this example ${failed ? 'fails' : 'runs'}:\n${job.code}\n${result.output.slice(0, 400)}`);
                    } else if (/Warning|Deprecated/.test(result.output)) {
                        problems.push(`${job.lesson}: this example warns:\n${result.output.slice(0, 400)}`);
                    }
                } else if (job.kind === 'starter') {
                    const result = await run(send, job.code);
                    if (result.fatal || /Fatal error|Parse error|Warning|Deprecated/.test(result.output)) {
                        problems.push(`${job.lesson}: the lesson's starter code fails:\n${result.output.slice(0, 400)}`);
                    }
                } else if (job.kind === 'quiz') {
                    const result = await run(send, withTag(job.code));
                    if (/not found|Undefined (variable|function)/.test(result.output)) {
                        problems.push(`${job.lesson}: a quiz snippet depends on something missing:\n${result.output.slice(0, 400)}`);
                    }
                } else if (job.kind === 'solution') {
                    const result = await check(send, job.challenge.solution, job.challenge.checks);
                    if (!result.passed) {
                        const failed = result.checks.filter((c) => !c.passed).map((c) => `${c.label}: ${c.message}`);
                        problems.push(`${job.lesson} / ${job.challenge.title}: the solution fails: ${result.error ?? failed.join('; ')}`);
                    }
                } else {
                    const result = await check(send, job.challenge.starter, job.challenge.checks);
                    if (result.passed) problems.push(`${job.lesson} / ${job.challenge.title}: the starter passes every check`);
                }
            };

            const senders = await Promise.all(Array.from({ length: workerCount }, makeWorker));
            let next = 0;
            await Promise.all(
                senders.map(async (send) => {
                    while (next < jobs.length) await handle(send, jobs[next++]);
                }),
            );

            return { problems, jobs: jobs.length };
        },
        { lessons, harness, workerUrl: `/build/assets/${workerFile}`, workerCount: WORKERS },
    );

    if (problems.problems.length) {
        console.log(problems.problems.join('\n\n'));
        console.log(`\n${problems.problems.length} problem(s) in ${problems.jobs} runs.`);
        process.exitCode = 1;
    } else {
        console.log(`All ${problems.jobs} runs behave as expected in the browser's PHP.`);
    }
} finally {
    await browser.close();
    server.close();
}
