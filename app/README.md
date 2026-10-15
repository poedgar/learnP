# learnP

A small platform for learning programming by running real code. Built with Laravel, SQLite and Vue.

Courses so far:

| Course | Lesson 1 | Code runs |
|--------|----------|-----------|
| **SQL** | *Your first queries* | on the server, in SQLite |
| **OOP in PHP** | *Classes and objects* | in the browser, in PHP 8.4 compiled to WebAssembly |
| **Advanced SQL** | *Stacking and pairing* | on the server, in SQLite |

There is also a mobile app for iOS and Android in `../mobile` (Expo). See `../mobile/README.md`.

Every lesson has four steps:

- **Learn**: theory in Markdown, with a "Try it in the sandbox" button under every code example.
- **Sandbox**: a code editor that runs your code and shows real results.
- **Quiz**: multiple-choice questions, checked on the server, each with an explanation.
- **Challenges**: tasks checked automatically. SQL answers are compared with a reference
  solution's result; PHP answers are run against a list of checks.

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

## Adding content

- Lesson theory: `database/content/<course>/*.md`
- SQL datasets: `database/datasets/*.sql`
- Courses, lessons, quiz questions and challenges: `database/seeders/SqlCourseSeeder.php` and
  `database/seeders/PhpOopCourseSeeder.php` (safe to re-run; IDs stay stable)

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
