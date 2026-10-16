# learnP

A small platform for learning programming by running real code. Built with Laravel, SQLite and Vue.

Courses so far:

| Course | Lesson 1 | Code runs |
|--------|----------|-----------|
| **SQL** | *Your first queries* | on the server, in SQLite |
| **PHP basics** | *Values and variables* | in the browser, in PHP 8.4 compiled to WebAssembly |
| **OOP in PHP** | *Classes and objects* | like PHP basics |
| **Advanced SQL** | *Stacking and pairing* | on the server, in SQLite |
| **Advanced OOP in PHP** | *Magic methods* | like PHP basics |
| **Design patterns in PHP** | *Patterns and factories* | like PHP basics |
| **Algorithms and data structures** | *Big-O and searching* | like PHP basics |
| **Functional PHP** | *Pure functions and immutability* | like PHP basics |
| **Testing without frameworks** | *Writing assertions* | like PHP basics |
| **Clean code and refactoring** | *Names and functions* | like PHP basics |
| **HTML & CSS** | *Structure and meaning* | in the browser, as a live preview of the page |
| **JavaScript basics** | *Values and variables* | in the browser, in a sandboxed frame |
| **Async JavaScript** | *Callbacks and the event loop* | like JavaScript basics |
| **JavaScript and the DOM** | *Finding things on the page* | like JavaScript basics, showing the page it builds |
| **TypeScript** | *Types and inference* | like JavaScript basics, after the TypeScript compiler has checked it |
| **Regular expressions** | *Patterns and character classes* | like JavaScript basics |

There is also a mobile app for iOS and Android in `../mobile` (Expo). See `../mobile/README.md`.

Every lesson has four steps:

- **Learn**: theory in Markdown, with a "Try it in the sandbox" button under every code example.
- **Sandbox**: a code editor that runs your code and shows real results.
- **Quiz**: multiple-choice questions, checked on the server, each with an explanation.
- **Challenges**: tasks checked automatically. SQL answers are compared with a reference
  solution's result; PHP, JavaScript, TypeScript and HTML answers are run against a list of checks.

Progress is saved in the browser's `localStorage`. There are no user accounts yet.

## Running it

Requirements: PHP 8.3+ with `pdo_sqlite` (PHP 8.5 recommended, see below), Composer, and Node 20+.

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build   # or `npm run dev` while working on the frontend
php artisan serve
```

Then open http://localhost:8000.

## How the SQL sandbox works

`POST /api/lessons/{lesson}/run` → `App\Sandbox\SqlSandbox`:

1. It starts a child PHP process (`bin/sql-runner.php`) with a 3-second timeout, so a runaway
   query, such as an endless recursive CTE, is killed without taking the web server down.
2. The child builds a fresh **in-memory** SQLite database from the lesson's dataset
   (`database/datasets/<name>.sql`), splits the learner's script into statements and runs them
   (`App\Sandbox\SqlExecutor`). Learners can `INSERT`, `UPDATE` or `DROP` freely, because nothing persists.
3. Commands that could touch the file system (`ATTACH`, `VACUUM INTO`, most `PRAGMA`s,
   `load_extension`) are refused. On PHP 8.5+ a SQLite authorizer enforces this inside the engine too.
   It lets through only the read-only `PRAGMA`s learners may type, plus `data_version`, which FTS5
   full-text tables read internally when they're written to.
4. Results are capped at 500 rows per statement.

Challenge checks (`POST /api/challenges/{challenge}/check`) run the learner's SQL and the hidden
solution, then compare the last result table of each: column count, row count and values
(and row order when the challenge sets `order_matters`).

## How the PHP sandbox works

Learner PHP never runs on the server. `resources/js/lib/php/` loads
[php-wasm](https://github.com/WordPress/wordpress-playground) (PHP 8.4 compiled to WebAssembly)
in a Web Worker:

- Each run starts with a clean PHP state. Network access is off (`allow_url_fopen=0`).
- A run that takes longer than 5 seconds is stopped by terminating the worker, which is then
  restarted, so an endless loop can't freeze the tab.
- The runtime is about 20 MB and downloads the first time a PHP lesson is opened, then comes
  from the browser cache. `vite.config.js` bundles only PHP 8.4 and serves php-wasm's binaries
  as static files.

Challenges are checked by `resources/php/check-harness.php`. It runs the learner's code, then each
of the challenge's `checks` (PHP snippets that `return true`, or a message explaining the failure;
`expect_same()` and `expect_throws()` help with that), and prints a JSON report. The same file
runs in the browser and in `tests/Feature/PhpChallengeHarnessTest.php`, which proves every
reference solution passes its checks.

The mobile app can't run WebAssembly PHP, so for PHP lessons `POST /api/lessons/{lesson}/run`
and `POST /api/challenges/{challenge}/check` (with `{ "code": "..." }`) run the code on the
**runner**, a separate Laravel app in `../runner` that holds no data or secrets
(`App\Sandbox\HttpPhpSandbox`). Checks use the same `check-harness.php`. To use these
endpoints locally, start the runner (see `../runner/README.md`) and set `RUNNER_URL` and
`RUNNER_SECRET` in `.env`. Without it they return 503.

## How the JavaScript, TypeScript and HTML sandbox works

JavaScript (and HTML) never runs on the server either (`POST /api/lessons/{lesson}/run` and `/check` answer
422 for these courses). The engine is in `resources/js/lib/js/`:

- `prepare.js` reports syntax errors with the learner's line (via acorn), adds a time guard to every
  loop (`loop-guard.js`), and wraps the learner's code and the challenge's checks in one async
  function, so top-level `await` works and checks see the learner's `let`/`const` declarations.
- Each run gets a fresh sandboxed `<iframe>` (`allow-scripts allow-forms`, no same-origin), so code
  can't reach the page, its cookies or storage. `frame-runtime.js` runs inside it: `console`
  capture with Node-style formatting, the loop guard (a loop running more than 2 seconds without a
  break throws), timer tracking, uncaught errors, and the check helpers (`expectSame()`,
  `expectThrows()`, `learnerCode()`, `learnerOutput()`, `runVariant()`). It's injected as source
  text, so it must stay self-contained.
- A run that leaves something on the page keeps its frame, shown live under the output
  (`PagePreview.vue`); what it logs afterwards, such as on a click, is added to the output.
- TypeScript is type-checked by the real compiler (`typescript@6`, the last version written in
  JavaScript) in a worker (`ts-service.js`). `vite.config.js` publishes it with its lib files at
  `/vendor/typescript/<version>/`, where the mobile app loads it too. Code with type errors doesn't
  run. Checks can also be `{ label, compiles }` or `{ label, rejects }`: TypeScript added after the
  learner's code that must type-check, or must be a type error.
- In HTML & CSS (`language: 'html'`) the learner's code is the page: the frame shows it as written,
  with its `<style>`, and its `<script>` tags don't run. The sandbox re-runs it as you type. Checks
  are JavaScript run in that page, with `styleOf(selector, property, pseudo?)` (computed values,
  colours as `rgb(...)`) and `boxOf(selector)` (its rounded position and size) besides the usual
  helpers. Checks run in an 800×600 frame; a check with `width` gets a frame that wide, to test
  media queries.

The mobile app runs the same engine in an Expo DOM component (`../mobile/src/js-runtime`); its
copies of the shared files are kept in sync by `npm run sync-js-runtime` there, and a test fails
when they drift.

## Adding content

- Lesson theory: `database/content/<course>/*.md`
- SQL datasets: `database/datasets/*.sql`
- Courses, lessons, quiz questions and challenges: `database/seeders/SqlCourseSeeder.php`,
  `database/seeders/PhpOopCourseSeeder.php`, and one seeder per later course. Those extend
  `ContentCourseSeeder` and read each lesson from `database/content/<course>/NN-slug.php` next to
  its `.md` theory (safe to re-run; IDs stay stable)
- PHP content must run on PHP 8.4 (the browser and the runner) with only core extensions: no
  `mbstring` or `ctype`. Checks can read the learner's variables through `$GLOBALS`, their output
  with `learnp_output()`, their code with `learnp_code()`, and run a variant with `learnp_run()`.
  Guard checks against slow or endless learner code (time a small case first, or read from a
  source that throws after a limit), because a hung check freezes the browser tab.
- JavaScript, TypeScript and HTML content goes in ` ```javascript `, ` ```typescript ` or ` ```html `
  fences.
  Checks run in the same scope as the learner's code. DOM lessons build their page in code
  (`document.body.innerHTML = ...`), and checks submit forms with a cancelable `submit` event,
  since a real submission reloads the frame. `tests/js/verify-content.mjs` checks lessons with
  the same engine in Node and jsdom; HTML lessons need real layout, so it checks them in a headless
  Chromium (`playwright-core`; install one with `npx playwright install chromium-headless-shell`,
  or point `LEARNP_CHROMIUM` at a Chrome). `JsLessonContentTest` runs it: it's skipped without Node,
  and marked incomplete when there's no Chromium for the HTML lessons. Put JS checks that contain
  `${...}` in nowdocs (`<<<'JS'`), or PHP interpolates them.

After changing content, run `php artisan db:seed`.

## Tests

```sh
php artisan test
```

To also run the end-to-end test against a real runner, start the runner (see
`../runner/README.md`), then:

```sh
RUNNER_E2E_URL=http://127.0.0.1:8001 RUNNER_E2E_SECRET=<secret> php artisan test --filter=RunnerEndToEndTest
```

`<secret>` must match the `RUNNER_SECRET` the runner was started with. Without these two
environment variables, `RunnerEndToEndTest` is skipped.
