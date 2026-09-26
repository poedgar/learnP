# learnP runner

Runs learner PHP for the learnP API. It has one endpoint, `POST /run`, which only the
main API may call (requests are signed with `RUNNER_SECRET`). It holds no database,
no sessions and no other secrets, on purpose: if learner code ever escaped the
restrictions below, it would find nothing worth taking.

## What a run looks like

`POST /run` with `{ "files": { "main.php": "<?php echo 1;" }, "entry": "main.php" }`,
signed with `X-Timestamp` and `X-Signature: hex(hmac_sha256(timestamp + "." + body, RUNNER_SECRET))`.
It returns `{ "stdout", "exitCode", "timedOut", "truncated" }`.

Each run (`App\Runner\PhpRunner`):

- gets a fresh temp folder, deleted afterwards;
- writes the learner's files into it, then makes the folder and every file in it read-only
  (`chmod 0500`/`0400`) before learner code runs, so it can create, modify or delete nothing —
  including via `tmpfile()`/`tempnam()`, which are pointed at the same folder;
- runs `php -n` with an empty environment, `open_basedir` set to that folder, URL wrappers
  and FFI off, the `php://` stream wrapper removed before learner code runs, and the
  functions in `App\Runner\DisabledFunctions` removed;
- is limited to 64 MB, 5 seconds of PHP time (6 seconds of real time) and 64 KB of output.

The read-only folder only confines learner code if the runner process itself is an ordinary,
unprivileged OS user: permission bits don't bind root. `PhpRunner::run()` checks this
(`posix_geteuid()`) and refuses to run at all if it's root.

## Running it locally

```sh
composer install
cp .env.example .env && php artisan key:generate
# Pick any long random string and put the same value in app/.env as RUNNER_SECRET
php artisan serve --port=8001
```

Put the same `RUNNER_SECRET` value in both `runner/.env` and `app/.env`, and in `app/.env` also
set `RUNNER_URL=http://127.0.0.1:8001`.

## Deploying to Laravel Cloud

Deploy this folder as its **own** Cloud application (`--root-directory=runner`), PHP 8.4:

- Attach **no** database, cache, bucket or other resource.
- Set `RUNNER_SECRET` (the same value as the main API's `RUNNER_SECRET`); nothing else is needed
  beyond `APP_KEY`, which must not be reused from any other app.
- Build command: `composer install --no-dev --optimize-autoloader`. No deploy command (there is nothing to migrate).

Before launch, open the environment's variables and confirm that only `RUNNER_SECRET`, `APP_KEY` and
Cloud's defaults are present: no database, cache or storage credentials. Also confirm the runner
process does not run as root — `PhpRunner` refuses to run at all if it does, since the read-only
run folder (see "Known limits") only confines learner code under ordinary permission bits.

## Known limits

`disable_functions` can't remove classes. If the PHP binary includes `pdo_mysql`, `mysqli`
or `soap`, learner code can still open network connections through `PDO`, `mysqli` or
`SoapClient`. There is nothing on the runner to steal, but it could be used to reach
other hosts. Keeping the runner as a separate app with no resources is the mitigation;
moving to a network-isolated sandbox (for example Judge0) would remove this risk.

Learner code can't use `php://` streams (`php://memory`, `php://temp`, `php://output`,
`php://stdin`, `SplTempFileObject`), because the `php` stream wrapper is unregistered before
learner code runs, to stop `php://fd/N` from reaching file descriptors inherited from the
runner process. `echo`, `STDOUT` and `STDERR` still work. If a lesson needs in-memory
streams, add a replacement wrapper that serves only memory/temp/output.

Learner code can't create, modify or delete any file, anywhere: its run folder and every file in
it are read-only (`chmod`) before it runs, and `chmod` is itself in `disable_functions`, so it
can't undo this. This also means `tmpfile()`/`tempnam()` fail (they're pointed at the same
read-only folder) rather than leaking a file into the system temp dir.

`SQLite3::loadExtension()` is blocked, but only because `sqlite3.extension_dir` is never set: PHP
treats "never configured" as "extensions disabled outright", which is different from — and safer
than — configuring it to an empty string (that instead enables loading with an empty path prefix,
which a learner-supplied absolute path sails through). `Pdo\Sqlite::loadExtension()` (PHP 8.4+)
and `PDO`'s own sqlite driver have no equivalent gate at all: `ext/pdo_sqlite` registers no ini
setting for it, and open_basedir, disable_functions and `setAuthorizer()` all have no effect on it
either, because it calls libsqlite3's `sqlite3_load_extension()` (a `dlopen`) directly — no PHP
setting closes this. What closes it instead is that there is no writable location left anywhere in
the sandbox for learner code to put a library for it to load: the read-only run folder above
covers this too. This relies on the runner process not running as root (see "Deploying to Laravel
Cloud"); `PhpRunner` checks and refuses to run if it is, rather than silently losing this
protection.
