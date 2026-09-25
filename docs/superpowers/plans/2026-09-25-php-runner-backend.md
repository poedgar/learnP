# PHP Runner Backend Implementation Plan (Plan 1 of 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the learnP API run learner PHP on the server, through a separate "runner" Laravel app that holds no secrets or data, so the Expo app (Plan 2) can run PHP and check PHP challenges.

**Architecture:** A new minimal Laravel app in `runner/` exposes one signed endpoint, `POST /run`. It runs the files it receives in a restricted `php -n` child process, with an empty environment, `open_basedir`, blocked functions, and memory, time and output limits. The main API in `app/` gets a `PhpSandbox` interface whose `HttpPhpSandbox` implementation signs requests to the runner. The existing `POST /api/lessons/{lesson}/run` and `POST /api/challenges/{challenge}/check` endpoints send PHP lessons to it and keep sending SQL to `SqlSandbox`.

**Tech Stack:** Laravel 13, PHP 8.4+ (8.5 locally), PHPUnit 12, Symfony Process, the Laravel HTTP client, Mockery.

**Spec:** `docs/superpowers/specs/2026-09-25-expo-mobile-app-design.md` (Parts 1 and 2, "Errors", "Testing")

## Global Constraints

- Tests are PHPUnit classes (`class XTest extends TestCase`, `test_snake_case` methods), like `app/tests`. Do not introduce Pest.
- The runner request is signed as `X-Signature: hex(hmac_sha256(timestamp + "." + rawBody, RUNNER_SECRET))` with `X-Timestamp: <unix seconds>`. Timestamps more than 60 s from the runner's clock are rejected.
- Runner body: `{ "files": { "<name>": "<contents>" }, "entry": "<name>" }`. Names match `^[a-z0-9_-]+\.(php|json)$`. At most 5 files, 100,000 bytes in total, and `entry` must be one of the names.
- Runner response: `{ "stdout": string, "exitCode": int, "timedOut": bool, "truncated": bool }`.
- Child limits: time limit of 5 s (PHP `max_execution_time=5`, plus a 6 s real-time limit), `memory_limit=64M`, and 65,536 bytes of stdout.
- API limits: `code` required, a string of at most 20,000 characters; HTTP timeout to the runner 10 s.
- API response to `run`: `{ output: string, fatal: bool, durationMs: int }`. API response to `check`: `{ passed, loaded, error, output, checks: [{ label, passed, message }] }`.
- Messages, verbatim:
  - 503 body: `The PHP sandbox is unavailable. Try again shortly.`
  - Timeout suffix: `\n\nStopped after 5 seconds.`
  - Truncation suffix: `\n…output truncated`
  - Missing report: `The checker could not read your code. Run it first to look for errors.`
  - Validation: `Write some PHP before running it.` and `Keep your code under :max characters.`
- The web app (`app/resources/js`) is not changed.
- Code comments follow `app/`'s style: a short docblock explaining *why* on each class, and few inline comments.

## Review Focus

These are the input and failure cases most likely to hurt real users that the spec doesn't spell out. Each has a test in the task named.

1. **Output that isn't valid UTF-8** (`echo "\xff";`, or a multi-byte character cut by truncation) must return 200 with U+FFFD replacement characters, not a 500 from `json_encode`. Test in Task 4.
2. **Learner code that prints the report marker itself**, to fake a passing report, must not pass: the API reads the *last* marker, which the harness prints after all learner output. Test in Task 5.
3. **An endless `sleep()` or loop in a challenge check** leaves no harness report, and must explain the timeout rather than say "could not read your code". Test in Task 5.
4. **The runner answering with something other than its JSON contract** (a Laravel Cloud HTML error page with 200 or 502, a 422, or a 401 from a wrong secret) must become a 503 with the standard message, never a 500 or a leaked body. Tests in Task 5.
5. **Environment leaking into learner code:** the runner's `RUNNER_SECRET` (in `$_ENV`, `$_SERVER` and `putenv`) must not be visible through `getenv()` in the child. Test in Task 3.

**Known remaining risk (documented in Task 4's README, not fixable with `disable_functions`):** built-in classes such as `PDO`, `mysqli` and `SoapClient` can still open network connections if their extensions are compiled into the PHP binary. The runner has nothing to steal, but it could be used to reach other hosts. The mitigation is operational: keep the runner in its own Cloud app with no attached resources.

---

## File Structure

**New: `runner/`** (from `composer create-project laravel/laravel`, then trimmed)

| File | Responsibility |
|---|---|
| `runner/bootstrap/app.php` | Routing (API routes with no prefix, `/up`), JSON errors everywhere |
| `runner/config/runner.php` | `secret`, `timeout` |
| `runner/routes/api.php` | `POST /run` |
| `runner/app/Runner/PhpRunner.php` | Writes files to a temp folder, starts the restricted child, and enforces time and output limits |
| `runner/app/Runner/DisabledFunctions.php` | Builds the `disable_functions` list from the child binary's own function list |
| `runner/app/Http/Middleware/VerifyRunnerSignature.php` | HMAC and timestamp check |
| `runner/app/Http/Requests/RunRequest.php` | Validates `files` and `entry` |
| `runner/app/Http/Controllers/RunController.php` | Glues the request to `PhpRunner` |
| `runner/app/Providers/AppServiceProvider.php` | Binds `PhpRunner` |
| `runner/README.md` | What it is, local dev, deploy, known limits |
| `runner/tests/Feature/HealthTest.php`, `PhpRunnerTest.php`, `PhpRunnerRestrictionsTest.php`, `RunApiTest.php` | Tests |

**Modified / new in `app/`**

| File | Change |
|---|---|
| `app/app/Sandbox/PhpSandbox.php` | New interface |
| `app/app/Sandbox/HttpPhpSandbox.php` | New: signs and sends to the runner, shapes the results |
| `app/app/Sandbox/PhpSandboxUnavailable.php` | New exception that renders the 503 |
| `app/app/Http/Requests/RunPhpRequest.php` | New validation for `code` |
| `app/app/Http/Controllers/Api/SandboxController.php` | Choose SQL or PHP by course language |
| `app/app/Http/Controllers/Api/ChallengeCheckController.php` | Choose SQL or PHP by course language |
| `app/app/Http/Resources/ChallengeResource.php` | Doc comment only |
| `app/app/Providers/AppServiceProvider.php` | Bind `PhpSandbox` |
| `app/config/services.php`, `app/.env.example` | `runner.url`, `runner.secret` |
| `app/README.md` | The server-side PHP sandbox and running the runner locally |
| `app/tests/Feature/HttpPhpSandboxTest.php`, `PhpSandboxApiTest.php`, `RunnerEndToEndTest.php` | Tests |

---

### Task 1: Scaffold the runner app

**Files:**
- Create: `runner/` (skeleton), `runner/config/runner.php`, `runner/tests/Feature/HealthTest.php`
- Modify: `runner/bootstrap/app.php`, `runner/.env.example`, `runner/phpunit.xml`, `runner/composer.json`
- Delete: `runner/routes/web.php`, `runner/resources/`, `runner/database/`, `runner/app/Models/`, `runner/tests/Unit/`, `runner/tests/Feature/ExampleTest.php`, `runner/package.json`, `runner/vite.config.js`

**Interfaces:**
- Produces: an app whose API routes are served without a prefix (`routes/api.php` → `/run`), whose errors are always JSON, and whose `config('runner.secret')` and `config('runner.timeout')` are set.

- [ ] **Step 1: Create the skeleton**

```bash
cd /Users/macbook/Desktop/dev/learnP
composer create-project laravel/laravel runner --no-interaction
```

Expected: `runner/` exists, and `php runner/artisan --version` prints `Laravel Framework 13.x`.

- [ ] **Step 2: Write the failing test**

Delete `runner/tests/Feature/ExampleTest.php` and `runner/tests/Unit/`, then create `runner/tests/Feature/HealthTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_check_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_the_runner_serves_no_web_pages(): void
    {
        $this->getJson('/')->assertNotFound();
    }
}
```

In `runner/phpunit.xml`, remove the `<testsuite name="Unit">…</testsuite>` block, because that directory no longer exists.

- [ ] **Step 3: Run the test to verify it fails**

Run: `cd runner && php artisan test --filter=HealthTest`
Expected: `test_the_runner_serves_no_web_pages` FAILS, because the welcome route returns 200.

- [ ] **Step 4: Trim the app down to an API-only runner**

```bash
cd /Users/macbook/Desktop/dev/learnP/runner
rm -rf routes/web.php resources database app/Models package.json vite.config.js
```

Replace `runner/bootstrap/app.php` with:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Only the main API calls this app, so every error is JSON.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
```

Create `runner/routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

// POST /run is added with the signature middleware in a later task.
```

Create `runner/config/runner.php`:

```php
<?php

return [

    // Shared with the main API, which signs every request to POST /run.
    'secret' => env('RUNNER_SECRET'),

    // PHP's max_execution_time for learner code; the wall-clock limit is one second longer.
    'timeout' => (int) env('RUNNER_TIMEOUT', 5),

];
```

In `runner/.env.example`, set these values. Change the lines that exist and add the rest:

```dotenv
APP_NAME=learnP-runner
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
RUNNER_SECRET=
```

In `runner/composer.json`, set `"php": "^8.4"` under `require`, then run `composer update --lock`.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd runner && php artisan test`
Expected: 2 tests PASS.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add runner
git commit -m "Scaffold the PHP runner app"
```

---

### Task 2: PhpRunner runs files in a child process with time and output limits

**Files:**
- Create: `runner/app/Runner/PhpRunner.php`, `runner/tests/Feature/PhpRunnerTest.php`
- Modify: `runner/app/Providers/AppServiceProvider.php`

**Interfaces:**
- Produces: `App\Runner\PhpRunner::__construct(string $workRoot, int $timeLimitSeconds = 5, int $maxOutputBytes = 65_536)` and
  `PhpRunner::run(array $files, string $entry): array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}`.
  `exitCode` is `-1` when the runner killed the process (timeout or truncation). It is bound as a singleton in the container.

- [ ] **Step 1: Write the failing tests**

Create `runner/tests/Feature/PhpRunnerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Runner\PhpRunner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhpRunnerTest extends TestCase
{
    private string $workRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workRoot = sys_get_temp_dir().'/runner-test-'.uniqid();
        File::ensureDirectoryExists($this->workRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workRoot);

        parent::tearDown();
    }

    private function runner(int $timeLimitSeconds = 1, int $maxOutputBytes = 65_536): PhpRunner
    {
        return new PhpRunner($this->workRoot, $timeLimitSeconds, $maxOutputBytes);
    }

    private function runCode(string $code, ?PhpRunner $runner = null): array
    {
        return ($runner ?? $this->runner())->run(['main.php' => $code], 'main.php');
    }

    public function test_output_and_exit_code_are_returned(): void
    {
        $this->assertSame(
            ['stdout' => 'hello', 'exitCode' => 0, 'timedOut' => false, 'truncated' => false],
            $this->runCode('<?php echo "hello";'),
        );
    }

    public function test_fatal_errors_are_printed_and_exit_with_255(): void
    {
        $result = $this->runCode('<?php undefined_function();');

        $this->assertSame(255, $result['exitCode']);
        $this->assertStringContainsString('Call to undefined function undefined_function()', $result['stdout']);
    }

    public function test_the_entry_file_can_load_the_other_files(): void
    {
        $result = $this->runner()->run([
            'helper.php' => '<?php return 42;',
            'main.php' => '<?php echo require __DIR__."/helper.php";',
        ], 'main.php');

        $this->assertSame('42', $result['stdout']);
    }

    public function test_cpu_bound_code_is_stopped_by_php_with_a_readable_error(): void
    {
        $result = $this->runCode('<?php while (true) {}');

        $this->assertFalse($result['timedOut']);
        $this->assertSame(255, $result['exitCode']);
        $this->assertStringContainsString('Maximum execution time of 1 second exceeded', $result['stdout']);
    }

    public function test_code_that_waits_forever_is_killed(): void
    {
        $result = $this->runCode('<?php echo "before"; sleep(30);');

        $this->assertTrue($result['timedOut']);
        $this->assertSame(-1, $result['exitCode']);
        $this->assertSame('before', $result['stdout']);
    }

    public function test_output_is_capped(): void
    {
        $result = $this->runCode('<?php echo str_repeat("x", 5000);', $this->runner(maxOutputBytes: 1000));

        $this->assertTrue($result['truncated']);
        $this->assertSame(-1, $result['exitCode']);
        $this->assertSame(1000, strlen($result['stdout']));
    }

    public function test_endless_output_is_cut_off_without_waiting_for_the_timeout(): void
    {
        $started = microtime(true);
        $result = $this->runCode('<?php while (true) { echo "x"; }', $this->runner(timeLimitSeconds: 5, maxOutputBytes: 1000));

        $this->assertTrue($result['truncated']);
        $this->assertFalse($result['timedOut']);
        $this->assertLessThan(3, microtime(true) - $started);
    }

    public function test_temp_folders_are_deleted_after_every_kind_of_run(): void
    {
        $this->runCode('<?php echo 1;');
        $this->runCode('<?php undefined_function();');
        $this->runCode('<?php sleep(30);');

        $this->assertSame([], File::directories($this->workRoot));
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd runner && php artisan test --filter=PhpRunnerTest`
Expected: FAIL with `Class "App\Runner\PhpRunner" not found`.

- [ ] **Step 3: Write the implementation**

Create `runner/app/Runner/PhpRunner.php`:

```php
<?php

namespace App\Runner;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Runs untrusted PHP in a child process. Every run gets its own temp folder,
 * which is deleted afterwards whatever happens.
 */
class PhpRunner
{
    public function __construct(
        private readonly string $workRoot,
        private readonly int $timeLimitSeconds = 5,
        private readonly int $maxOutputBytes = 65_536,
    ) {}

    /**
     * @param  array<string, string>  $files  File name => contents, all written to the same folder.
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    public function run(array $files, string $entry): array
    {
        $directory = $this->workRoot.DIRECTORY_SEPARATOR.'run-'.Str::random(16);
        File::ensureDirectoryExists($directory, 0700);

        try {
            $directory = realpath($directory);

            foreach ($files as $name => $contents) {
                File::put($directory.DIRECTORY_SEPARATOR.$name, $contents);
            }

            return $this->execute($directory, $entry);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    private function execute(string $directory, string $entry): array
    {
        $process = new Process([$this->phpBinary(), '-n', ...$this->iniFlags($directory), $entry], $directory);
        // PHP's max_execution_time stops busy loops with a proper error; this catches code that sleeps or blocks.
        $process->setTimeout($this->timeLimitSeconds + 1);

        $stdout = '';
        $timedOut = false;
        $truncated = false;

        try {
            $process->start();
            $truncated = $process->waitUntil(function (string $type, string $buffer) use (&$stdout) {
                if ($type === Process::OUT) {
                    $stdout .= $buffer;
                }

                return strlen($stdout) > $this->maxOutputBytes;
            });
        } catch (ProcessTimedOutException) {
            $timedOut = true;
        }

        if ($truncated) {
            $process->stop(0);
        }

        return [
            'stdout' => substr($stdout, 0, $this->maxOutputBytes),
            'exitCode' => $timedOut || $truncated ? -1 : ($process->getExitCode() ?? -1),
            'timedOut' => $timedOut,
            'truncated' => $truncated,
        ];
    }

    /**
     * @return list<string>
     */
    private function iniFlags(string $directory): array
    {
        $settings = [
            'memory_limit' => '64M',
            'max_execution_time' => (string) $this->timeLimitSeconds,
            'display_errors' => 'stdout',
            'log_errors' => '0',
            'html_errors' => '0',
            'error_reporting' => (string) E_ALL,
            'date.timezone' => 'UTC',
        ];

        return collect($settings)->flatMap(fn (string $value, string $key) => ['-d', "$key=$value"])->values()->all();
    }

    private function phpBinary(): string
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}
```

In `runner/app/Providers/AppServiceProvider.php`, add `use App\Runner\PhpRunner;` and set `register()` to:

```php
    public function register(): void
    {
        $this->app->singleton(PhpRunner::class, fn () => new PhpRunner(sys_get_temp_dir(), config('runner.timeout')));
    }
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd runner && php artisan test --filter=PhpRunnerTest`
Expected: 8 tests PASS, in about 5 s (two of them wait out a 1–2 s limit).

If `test_cpu_bound_code_is_stopped_by_php_with_a_readable_error` reports `timedOut: true` on this machine, PHP's timer isn't firing before the 2 s real-time limit. Check with `php -n -d max_execution_time=1 -r 'while(true){}'`. It must print the "Maximum execution time" error within about 1 s. Don't loosen the test.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add runner
git commit -m "Run learner PHP in a child process with time and output limits"
```

---

### Task 3: Restrict what learner code can do

**Files:**
- Create: `runner/app/Runner/DisabledFunctions.php`, `runner/tests/Feature/PhpRunnerRestrictionsTest.php`
- Modify: `runner/app/Runner/PhpRunner.php` (`execute()` and `iniFlags()`)

**Interfaces:**
- Consumes: `PhpRunner` from Task 2.
- Produces: `App\Runner\DisabledFunctions::for(string $phpBinary): list<string>`. It's used only inside `PhpRunner`; `PhpRunner`'s public signature doesn't change.

- [ ] **Step 1: Write the failing tests**

Create `runner/tests/Feature/PhpRunnerRestrictionsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Runner\PhpRunner;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Proves that learner code can't reach outside its temp folder. Each case is
 * something a learner could try on purpose.
 */
class PhpRunnerRestrictionsTest extends TestCase
{
    private string $workRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workRoot = sys_get_temp_dir().'/runner-test-'.uniqid();
        File::ensureDirectoryExists($this->workRoot);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workRoot);

        parent::tearDown();
    }

    private function runCode(string $code): string
    {
        return (new PhpRunner($this->workRoot, 1))->run(['main.php' => $code], 'main.php')['stdout'];
    }

    #[DataProvider('blockedFunctionCalls')]
    public function test_functions_that_reach_outside_the_sandbox_are_disabled(string $code, string $function): void
    {
        $this->assertStringContainsString("Call to undefined function $function()", $this->runCode("<?php $code"));
    }

    public static function blockedFunctionCalls(): array
    {
        return [
            'exec' => ['exec("id");', 'exec'],
            'shell_exec' => ['shell_exec("id");', 'shell_exec'],
            'backticks' => ['echo `id`;', 'shell_exec'],
            'system' => ['system("id");', 'system'],
            'proc_open' => ['proc_open("id", [], $pipes);', 'proc_open'],
            'putenv' => ['putenv("A=1");', 'putenv'],
            'ini_set' => ['ini_set("open_basedir", "/");', 'ini_set'],
            'set_time_limit' => ['set_time_limit(0);', 'set_time_limit'],
            'mail' => ['mail("a@example.com", "s", "b");', 'mail'],
            'fsockopen' => ['fsockopen("example.com", 80);', 'fsockopen'],
            'stream_socket_client' => ['stream_socket_client("tcp://example.com:80");', 'stream_socket_client'],
            'gethostbyname' => ['gethostbyname("example.com");', 'gethostbyname'],
            'symlink' => ['symlink("/etc/passwd", "p");', 'symlink'],
        ];
    }

    public function test_prefixed_function_families_are_disabled_when_the_binary_has_them(): void
    {
        $output = $this->runCode('<?php echo json_encode(array_values(array_filter(get_defined_functions()["internal"], fn ($f) => preg_match("/^(pcntl_|posix_|socket_|curl_|ftp_|proc_)/", $f))));');

        $this->assertSame('[]', $output);
    }

    public function test_files_outside_the_run_folder_cannot_be_read(): void
    {
        $output = $this->runCode('<?php var_dump(file_get_contents("/etc/passwd"));');

        $this->assertStringContainsString('open_basedir restriction in effect', $output);
        $this->assertStringContainsString('bool(false)', $output);
    }

    public function test_files_in_the_run_folder_can_be_read(): void
    {
        $result = (new PhpRunner($this->workRoot, 1))->run([
            'checks.json' => '[1,2]',
            'main.php' => '<?php echo file_get_contents(__DIR__."/checks.json");',
        ], 'main.php');

        $this->assertSame('[1,2]', $result['stdout']);
    }

    public function test_url_wrappers_are_disabled(): void
    {
        $this->assertStringContainsString('wrapper is disabled', $this->runCode('<?php file_get_contents("http://example.com");'));
    }

    public function test_learner_code_sees_an_empty_environment(): void
    {
        $_ENV['RUNNER_SECRET'] = $_SERVER['RUNNER_SECRET'] = 'do-not-leak';
        putenv('RUNNER_SECRET=do-not-leak');

        try {
            $output = $this->runCode('<?php echo json_encode(getenv()), "|", json_encode($_ENV), "|", isset($_SERVER["RUNNER_SECRET"]) ? "leak" : "clean";');
        } finally {
            unset($_ENV['RUNNER_SECRET'], $_SERVER['RUNNER_SECRET']);
            putenv('RUNNER_SECRET');
        }

        $this->assertSame('[]|[]|clean', $output);
    }

    public function test_ffi_is_off(): void
    {
        $output = $this->runCode('<?php FFI::cdef("int getpid(void);");');

        $this->assertMatchesRegularExpression('/ffi\.enable|Class "FFI" not found/', $output);
    }

    public function test_memory_is_limited(): void
    {
        $output = $this->runCode('<?php $a = str_repeat("x", 128 * 1024 * 1024);');

        $this->assertStringContainsString('Allowed memory size', $output);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd runner && php artisan test --filter=PhpRunnerRestrictionsTest`
Expected: most cases FAIL. For example, `exec` runs, and `/etc/passwd` is readable. `test_memory_is_limited` may already pass.

- [ ] **Step 3: Write `DisabledFunctions`**

Create `runner/app/Runner/DisabledFunctions.php`:

```php
<?php

namespace App\Runner;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * The disable_functions list for learner code. It's built from the child
 * binary's own function list, because `php -n` still has every extension that
 * was compiled in, and this web process may have a different set (pcntl is often
 * CLI-only). disable_functions has no wildcards, so prefixed families are expanded here.
 */
final class DisabledFunctions
{
    /** Run programs, change the process or its settings, send mail, touch links or permissions, resolve hosts, open sockets. */
    private const NAMES = [
        'exec', 'shell_exec', 'system', 'passthru', 'popen',
        'putenv', 'ini_set', 'ini_alter', 'ini_restore', 'set_time_limit', 'ignore_user_abort', 'dl',
        'mail', 'mb_send_mail', 'error_log', 'syslog', 'openlog',
        'link', 'symlink', 'chmod', 'chown', 'chgrp', 'lchown', 'lchgrp', 'touch',
        'fsockopen', 'pfsockopen',
        'gethostbyname', 'gethostbynamel', 'gethostbyaddr', 'dns_get_record', 'dns_check_record', 'checkdnsrr', 'getmxrr', 'dns_get_mx',
        'mysqli_connect', 'mysqli_real_connect', 'pg_connect', 'pg_pconnect',
    ];

    private const PREFIXES = [
        'proc_', 'pcntl_', 'posix_', 'socket_', 'stream_socket_', 'curl_', 'ftp_',
        'shmop_', 'sem_', 'shm_', 'msg_', 'ldap_', 'snmp', 'odbc_', 'imap_',
    ];

    /** @var array<string, list<string>> */
    private static array $cache = [];

    /**
     * @return list<string>
     */
    public static function for(string $phpBinary): array
    {
        return self::$cache[$phpBinary] ??= self::build($phpBinary);
    }

    /**
     * @return list<string>
     */
    private static function build(string $phpBinary): array
    {
        $process = new Process([$phpBinary, '-n', '-r', 'echo json_encode(get_defined_functions()["internal"]);']);
        $process->mustRun();

        $prefixed = array_filter(
            json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR),
            fn (string $function) => Str::startsWith($function, self::PREFIXES),
        );

        return array_values(array_unique([...self::NAMES, ...$prefixed]));
    }
}
```

- [ ] **Step 4: Apply the restrictions in `PhpRunner`**

In `runner/app/Runner/PhpRunner.php`, create the process with an environment that removes every inherited variable. Replace the `$process = new Process(...)` line with:

```php
        // Symfony merges the parent environment into the child's; `false` removes a variable.
        $environment = array_fill_keys(array_keys(getenv() + $_ENV + $_SERVER), false);
        $process = new Process([$this->phpBinary(), '-n', ...$this->iniFlags($directory), $entry], $directory, $environment);
```

Then replace the `$settings` array in `iniFlags()` with:

```php
        $settings = [
            'open_basedir' => $directory,
            'allow_url_fopen' => '0',
            'allow_url_include' => '0',
            'ffi.enable' => '0',
            'disable_functions' => implode(',', DisabledFunctions::for($this->phpBinary())),
            'memory_limit' => '64M',
            'max_execution_time' => (string) $this->timeLimitSeconds,
            'display_errors' => 'stdout',
            'log_errors' => '0',
            'html_errors' => '0',
            'error_reporting' => (string) E_ALL,
            'date.timezone' => 'UTC',
        ];
```

- [ ] **Step 5: Run all runner tests to verify they pass**

Run: `cd runner && php artisan test`
Expected: all PASS, including Task 2's tests.

The environment array also includes `$_SERVER` keys that aren't environment variables (for example `argv`). Setting those to `false` is harmless: `false` only tells Symfony to leave that variable out.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add runner
git commit -m "Restrict learner PHP: open_basedir, disabled functions, empty environment"
```

---

### Task 4: Signed `POST /run` endpoint, and the runner README

**Files:**
- Create: `runner/app/Http/Middleware/VerifyRunnerSignature.php`, `runner/app/Http/Requests/RunRequest.php`, `runner/app/Http/Controllers/RunController.php`, `runner/tests/Feature/RunApiTest.php`, `runner/README.md`
- Modify: `runner/routes/api.php`

**Interfaces:**
- Consumes: `PhpRunner::run(array $files, string $entry)` from Task 2.
- Produces: `POST /run` as defined in Global Constraints. Task 5 signs requests to it.

- [ ] **Step 1: Write the failing tests**

Create `runner/tests/Feature/RunApiTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RunApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['runner.secret' => 'test-secret']);
    }

    private function signedPost(array $body, ?int $timestamp = null, string $secret = 'test-secret', ?string $sentBody = null): TestResponse
    {
        $json = json_encode($body);
        $timestamp ??= now()->getTimestamp();

        return $this->call('POST', '/run', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$json, $secret),
        ], content: $sentBody ?? $json);
    }

    private function runBody(string $code): array
    {
        return ['files' => ['main.php' => $code], 'entry' => 'main.php'];
    }

    public function test_signed_requests_run_the_code(): void
    {
        $this->signedPost($this->runBody('<?php echo "hi";'))
            ->assertOk()
            ->assertExactJson(['stdout' => 'hi', 'exitCode' => 0, 'timedOut' => false, 'truncated' => false]);
    }

    public function test_output_that_is_not_valid_utf8_is_replaced_instead_of_failing(): void
    {
        $this->signedPost($this->runBody('<?php echo "a\xffb";'))
            ->assertOk()
            ->assertJsonPath('stdout', "a\u{FFFD}b");
    }

    public function test_unsigned_requests_are_rejected(): void
    {
        $this->postJson('/run', $this->runBody('<?php echo 1;'))->assertUnauthorized();
    }

    public function test_requests_signed_with_another_secret_are_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), secret: 'wrong')->assertUnauthorized();
    }

    public function test_old_requests_are_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), timestamp: now()->getTimestamp() - 61)->assertUnauthorized();
    }

    public function test_a_body_changed_after_signing_is_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), sentBody: json_encode($this->runBody('<?php echo 2;')))->assertUnauthorized();
    }

    public function test_nothing_is_accepted_when_no_secret_is_configured(): void
    {
        config(['runner.secret' => null]);

        $this->signedPost($this->runBody('<?php echo 1;'), secret: '')->assertUnauthorized();
    }

    public function test_file_names_must_be_plain(): void
    {
        $this->signedPost(['files' => ['../main.php' => '<?php'], 'entry' => '../main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_the_entry_must_be_one_of_the_files(): void
    {
        $this->signedPost(['files' => ['main.php' => '<?php'], 'entry' => 'other.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entry');
    }

    public function test_the_number_of_files_is_limited(): void
    {
        $files = collect(range(1, 6))->mapWithKeys(fn (int $i) => ["f$i.php" => '<?php'])->all();

        $this->signedPost(['files' => $files, 'entry' => 'f1.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_file_contents_must_be_strings(): void
    {
        $this->signedPost(['files' => ['main.php' => ['nested']], 'entry' => 'main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_the_total_size_is_limited(): void
    {
        $this->signedPost(['files' => ['main.php' => '<?php '.str_repeat('x', 100_000)], 'entry' => 'main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd runner && php artisan test --filter=RunApiTest`
Expected: FAIL with 404s, because the route doesn't exist yet.

- [ ] **Step 3: Write the middleware, request, controller and route**

Create `runner/app/Http/Middleware/VerifyRunnerSignature.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the main learnP API may run code here. It signs "timestamp.body" with
 * the shared RUNNER_SECRET; old timestamps are refused so requests can't be replayed.
 */
class VerifyRunnerSignature
{
    private const MAX_AGE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('runner.secret');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        $valid = filled($secret)
            && is_string($timestamp) && ctype_digit($timestamp)
            && abs(now()->getTimestamp() - (int) $timestamp) <= self::MAX_AGE_SECONDS
            && is_string($signature)
            && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);

        abort_unless($valid, 401, 'Invalid signature.');

        return $next($request);
    }
}
```

Create `runner/app/Http/Requests/RunRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RunRequest extends FormRequest
{
    public const MAX_FILES = 5;

    public const MAX_TOTAL_BYTES = 100_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // No `files.*` rule: file names contain dots, which validation would read as nested keys.
        return [
            'files' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'entry' => ['required', 'string'],
        ];
    }

    /**
     * File names become paths, so they must be plain names.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $files = $this->input('files');

                if (! is_array($files)) {
                    return;
                }

                foreach ($files as $name => $contents) {
                    if (preg_match('/^[a-z0-9_-]+\.(php|json)$/', (string) $name) !== 1) {
                        $validator->errors()->add('files', "Invalid file name [$name].");
                    }

                    if (! is_string($contents) || $contents === '') {
                        $validator->errors()->add('files', "File [$name] must have contents.");
                    }
                }

                if (array_sum(array_map(fn ($contents) => strlen((string) $contents), $files)) > self::MAX_TOTAL_BYTES) {
                    $validator->errors()->add('files', 'Files may total at most '.self::MAX_TOTAL_BYTES.' bytes.');
                }

                if (! array_key_exists((string) $this->input('entry'), $files)) {
                    $validator->errors()->add('entry', 'The entry must be one of the files.');
                }
            },
        ];
    }
}
```

Create `runner/app/Http/Controllers/RunController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunRequest;
use App\Runner\PhpRunner;
use Illuminate\Http\JsonResponse;

class RunController extends Controller
{
    public function __invoke(RunRequest $request, PhpRunner $runner): JsonResponse
    {
        $result = $runner->run($request->validated('files'), $request->validated('entry'));

        // Learner output can be any bytes; don't let one bad byte turn into a 500.
        return response()->json($result, options: JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
```

Replace `runner/routes/api.php` with:

```php
<?php

use App\Http\Controllers\RunController;
use App\Http\Middleware\VerifyRunnerSignature;
use Illuminate\Support\Facades\Route;

Route::post('run', RunController::class)->middleware(VerifyRunnerSignature::class)->name('run');
```

- [ ] **Step 4: Run all runner tests to verify they pass**

Run: `cd runner && php artisan test`
Expected: all PASS.

- [ ] **Step 5: Write `runner/README.md`**

```markdown
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
- runs `php -n` with an empty environment, `open_basedir` set to that folder, URL wrappers
  and FFI off, and the functions in `App\Runner\DisabledFunctions` removed;
- is limited to 64 MB, 5 seconds of PHP time (6 seconds of real time) and 64 KB of output.

## Running it locally

```sh
composer install
cp .env.example .env && php artisan key:generate
# Pick any long random string and put the same value in app/.env as RUNNER_SECRET
php artisan serve --port=8001
```

In `app/.env`: `RUNNER_URL=http://127.0.0.1:8001` and the same `RUNNER_SECRET`.

## Deploying to Laravel Cloud

Deploy this folder as its **own** Cloud application (`--root-directory=runner`), PHP 8.4:

- Attach **no** database, cache, bucket or other resource.
- Set `RUNNER_SECRET` (the same value as the main API's `RUNNER_SECRET`); nothing else is needed
  beyond `APP_KEY`, which must not be reused from any other app.
- Build command: `composer install --no-dev --optimize-autoloader`. No deploy command (there is nothing to migrate).

Before launch, open the environment's variables and confirm that only `RUNNER_SECRET`, `APP_KEY` and
Cloud's defaults are present: no database, cache or storage credentials.

## Known limits

`disable_functions` can't remove classes. If the PHP binary includes `pdo_mysql`, `mysqli`
or `soap`, learner code can still open network connections through `PDO`, `mysqli` or
`SoapClient`. There is nothing on the runner to steal, but it could be used to reach
other hosts. Keeping the runner as a separate app with no resources is the mitigation;
moving to a network-isolated sandbox (for example Judge0) would remove this risk.
```

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add runner
git commit -m "Add signed POST /run endpoint and runner README"
```

---

### Task 5: `PhpSandbox` in the main API

**Files:**
- Create: `app/app/Sandbox/PhpSandbox.php`, `app/app/Sandbox/HttpPhpSandbox.php`, `app/app/Sandbox/PhpSandboxUnavailable.php`, `app/tests/Feature/HttpPhpSandboxTest.php`
- Modify: `app/config/services.php`, `app/.env.example`, `app/app/Providers/AppServiceProvider.php`

**Interfaces:**
- Consumes: the runner's `POST /run` contract (Global Constraints).
- Produces:
  - `interface App\Sandbox\PhpSandbox` with `const MAX_CODE_LENGTH = 20_000`,
    `run(string $code): array{output: string, fatal: bool, durationMs: int}` and
    `check(string $code, array $checks): array{passed: bool, loaded: bool, error: ?string, output: string, checks: list<array{label: string, passed: bool, message: ?string}>}`.
  - `App\Sandbox\HttpPhpSandbox::__construct(?string $url, ?string $secret, string $harnessPath, int $timeLimitSeconds = 5)`.
  - `App\Sandbox\PhpSandboxUnavailable extends RuntimeException`, rendered as a 503.
  - The container binds `PhpSandbox` to `HttpPhpSandbox` using `config('services.runner.*')`.

- [ ] **Step 1: Write the failing tests**

Create `app/tests/Feature/HttpPhpSandboxTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Sandbox\HttpPhpSandbox;
use App\Sandbox\PhpSandboxUnavailable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpPhpSandboxTest extends TestCase
{
    private const MARKER = "\n@@LEARNP_REPORT@@\n";

    private function sandbox(?string $url = 'https://runner.test', ?string $secret = 'secret'): HttpPhpSandbox
    {
        return new HttpPhpSandbox($url, $secret, resource_path('php/check-harness.php'));
    }

    private function runnerReturns(string $stdout, int $exitCode = 0, bool $timedOut = false, bool $truncated = false): void
    {
        Http::fake(['runner.test/run' => Http::response(compact('stdout', 'exitCode', 'timedOut', 'truncated'))]);
    }

    private function report(bool $loaded, array $passes, string $output = ''): string
    {
        return json_encode([
            'loaded' => $loaded,
            'error' => $loaded ? null : 'ParseError: syntax error on line 1',
            'output' => $output,
            'checks' => array_map(fn (bool $passed, int $i) => ['label' => "check $i", 'passed' => $passed, 'message' => $passed ? null : 'Nope.'], $passes, array_keys($passes)),
        ]);
    }

    public function test_run_sends_a_signed_request_with_the_code(): void
    {
        $this->freezeTime();
        $this->runnerReturns("\nhi");

        $result = $this->sandbox()->run('echo "hi";');

        $this->assertSame('hi', $result['output']);
        $this->assertFalse($result['fatal']);
        $this->assertIsInt($result['durationMs']);

        Http::assertSent(function (Request $request) {
            $timestamp = $request->header('X-Timestamp')[0];

            return $request->url() === 'https://runner.test/run'
                && $timestamp === (string) now()->getTimestamp()
                && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->body(), 'secret'), $request->header('X-Signature')[0])
                && $request['files'] === ['main.php' => "<?php\n\necho \"hi\";"]
                && $request['entry'] === 'main.php';
        });
    }

    public function test_code_with_an_opening_tag_is_sent_unchanged(): void
    {
        $this->runnerReturns('');

        $this->sandbox()->run("<?php\necho 1;");

        Http::assertSent(fn (Request $request) => $request['files'] === ['main.php' => "<?php\necho 1;"]);
    }

    public function test_fatal_errors_are_flagged(): void
    {
        $this->runnerReturns('PHP Fatal error: Uncaught Error', exitCode: 255);

        $this->assertTrue($this->sandbox()->run('nope();')['fatal']);
    }

    public function test_timeouts_are_flagged_and_explained(): void
    {
        $this->runnerReturns('before', exitCode: -1, timedOut: true);

        $result = $this->sandbox()->run('sleep(30);');

        $this->assertTrue($result['fatal']);
        $this->assertSame("before\n\nStopped after 5 seconds.", $result['output']);
    }

    public function test_truncated_output_is_marked(): void
    {
        $this->runnerReturns('xxxx', exitCode: -1, truncated: true);

        $this->assertSame("xxxx\n…output truncated", $this->sandbox()->run('while (true) echo "x";')['output']);
    }

    public function test_check_sends_the_harness_and_checks_and_reads_the_report(): void
    {
        $checks = [['label' => 'check 0', 'code' => 'return true;']];
        $this->runnerReturns('hello'.self::MARKER.$this->report(true, [true], "\nhello"));

        $result = $this->sandbox()->check('<?php echo "hello";', $checks);

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['loaded']);
        $this->assertSame('hello', $result['output']);
        $this->assertSame([['label' => 'check 0', 'passed' => true, 'message' => null]], $result['checks']);

        Http::assertSent(fn (Request $request) => $request['entry'] === 'harness.php'
            && $request['files']['main.php'] === '<?php echo "hello";'
            && $request['files']['checks.json'] === json_encode($checks)
            && $request['files']['harness.php'] === file_get_contents(resource_path('php/check-harness.php')));
    }

    public function test_one_failing_check_fails_the_challenge(): void
    {
        $this->runnerReturns(self::MARKER.$this->report(true, [true, false]));

        $this->assertFalse($this->sandbox()->check('<?php', [])['passed']);
    }

    public function test_code_that_does_not_load_fails_the_challenge(): void
    {
        $this->runnerReturns(self::MARKER.$this->report(false, []));

        $result = $this->sandbox()->check('<?php class {', []);

        $this->assertFalse($result['passed']);
        $this->assertSame('ParseError: syntax error on line 1', $result['error']);
    }

    public function test_a_fake_report_printed_by_learner_code_is_ignored(): void
    {
        $fake = self::MARKER.$this->report(true, [true]);
        $this->runnerReturns($fake.self::MARKER.$this->report(true, [false], $fake));

        $this->assertFalse($this->sandbox()->check('<?php echo "fake";', [['label' => 'check 0', 'code' => 'return false;']])['passed']);
    }

    public function test_a_missing_report_fails_every_check_with_an_explanation(): void
    {
        $this->runnerReturns('Segmentation fault', exitCode: 139);

        $result = $this->sandbox()->check('<?php', [['label' => 'a', 'code' => ''], ['label' => 'b', 'code' => '']]);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['loaded']);
        $this->assertSame('The checker could not read your code. Run it first to look for errors.', $result['error']);
        $this->assertSame(['a', 'b'], array_column($result['checks'], 'label'));
        $this->assertSame([false, false], array_column($result['checks'], 'passed'));
    }

    public function test_a_check_that_times_out_explains_the_timeout(): void
    {
        $this->runnerReturns('', exitCode: -1, timedOut: true);

        $result = $this->sandbox()->check('<?php sleep(30);', [['label' => 'a', 'code' => '']]);

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('ran for more than 5 seconds', $result['error']);
    }

    public function test_a_check_with_too_much_output_explains_it(): void
    {
        $this->runnerReturns('xxxx', exitCode: -1, truncated: true);

        $result = $this->sandbox()->check('<?php while (true) echo "x";', [['label' => 'a', 'code' => '']]);

        $this->assertStringContainsString('printed more output than the checker can read', $result['error']);
    }

    #[DataProvider('brokenRunnerResponses')]
    public function test_a_broken_runner_makes_the_sandbox_unavailable(callable $response): void
    {
        Http::fake(['runner.test/run' => $response()]);

        $this->expectException(PhpSandboxUnavailable::class);

        $this->sandbox()->run('echo 1;');
    }

    public static function brokenRunnerResponses(): array
    {
        return [
            'wrong secret' => [fn () => Http::response(['message' => 'Invalid signature.'], 401)],
            'rejected body' => [fn () => Http::response(['message' => 'Invalid.'], 422)],
            'server error' => [fn () => Http::response('Server Error', 500)],
            'html page with 200' => [fn () => Http::response('<html>Cloud is waking up</html>', 200)],
            'json missing fields' => [fn () => Http::response(['stdout' => 'x'], 200)],
            'connection refused' => [fn () => Http::failedConnection()],
        ];
    }

    public function test_an_unconfigured_runner_makes_the_sandbox_unavailable(): void
    {
        Http::fake();

        $this->expectException(PhpSandboxUnavailable::class);

        $this->sandbox(url: null, secret: null)->run('echo 1;');
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd app && php artisan test --filter=HttpPhpSandboxTest`
Expected: FAIL with `Class "App\Sandbox\HttpPhpSandbox" not found`.

If `Http::failedConnection()` doesn't exist in this Laravel version, replace that data-provider row with `fn () => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused')`.

- [ ] **Step 3: Write the interface, exception and implementation**

Create `app/app/Sandbox/PhpSandbox.php`:

```php
<?php

namespace App\Sandbox;

/**
 * Runs learner PHP on the server. The web app runs PHP in the browser instead
 * (resources/js/lib/php); these results use the same shapes as runScript()
 * and checkChallenge() there.
 */
interface PhpSandbox
{
    public const MAX_CODE_LENGTH = 20_000;

    /**
     * @return array{output: string, fatal: bool, durationMs: int}
     */
    public function run(string $code): array;

    /**
     * Runs the code, then the challenge's checks, with resources/php/check-harness.php.
     *
     * @param  list<array{label: string, code: string}>  $checks
     * @return array{passed: bool, loaded: bool, error: ?string, output: string, checks: list<array{label: string, passed: bool, message: ?string}>}
     */
    public function check(string $code, array $checks): array;
}
```

Create `app/app/Sandbox/PhpSandboxUnavailable.php`:

```php
<?php

namespace App\Sandbox;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The runner could not be reached or answered with something unexpected. The
 * details are logged; learners get a message they can act on.
 */
class PhpSandboxUnavailable extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'The PHP sandbox is unavailable. Try again shortly.'], 503);
    }
}
```

Create `app/app/Sandbox/HttpPhpSandbox.php`:

```php
<?php

namespace App\Sandbox;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Runs learner PHP on the runner app (runner/README.md). The runner holds no
 * data or secrets, so learner code never runs next to this app's database.
 */
class HttpPhpSandbox implements PhpSandbox
{
    private const REPORT_MARKER = "\n@@LEARNP_REPORT@@\n";

    public function __construct(
        private readonly ?string $url,
        private readonly ?string $secret,
        private readonly string $harnessPath,
        private readonly int $timeLimitSeconds = 5,
    ) {}

    public function run(string $code): array
    {
        $started = hrtime(true);
        $result = $this->send(['main.php' => $this->withOpeningTag($code)], 'main.php');

        return [
            'output' => $this->describeStop(ltrim($result['stdout'], "\n"), $result),
            'fatal' => $result['exitCode'] !== 0 || $result['timedOut'],
            'durationMs' => (int) round((hrtime(true) - $started) / 1_000_000),
        ];
    }

    public function check(string $code, array $checks): array
    {
        $result = $this->send([
            'main.php' => $this->withOpeningTag($code),
            'checks.json' => json_encode($checks, JSON_THROW_ON_ERROR),
            'harness.php' => file_get_contents($this->harnessPath),
        ], 'harness.php');

        // The harness prints its report last, so a marker echoed by learner code comes earlier.
        $position = strrpos($result['stdout'], self::REPORT_MARKER);
        $report = $position === false ? null : json_decode(substr($result['stdout'], $position + strlen(self::REPORT_MARKER)), true);

        if (! is_array($report)) {
            return [
                'passed' => false,
                'loaded' => false,
                'error' => match (true) {
                    $result['timedOut'] => "Your code ran for more than {$this->timeLimitSeconds} seconds and was stopped. Look for a loop that never ends.",
                    $result['truncated'] => 'Your code printed more output than the checker can read. Look for an echo inside a loop that never ends.',
                    default => 'The checker could not read your code. Run it first to look for errors.',
                },
                'output' => $this->describeStop(ltrim($result['stdout'], "\n"), $result),
                'checks' => array_map(
                    fn (array $check) => ['label' => $check['label'], 'passed' => false, 'message' => 'Fix the error in your code first.'],
                    $checks,
                ),
            ];
        }

        return [
            ...$report,
            'output' => ltrim($report['output'], "\n"),
            'passed' => $report['loaded'] && collect($report['checks'])->every(fn (array $check) => $check['passed'] === true),
        ];
    }

    /**
     * @param  array<string, string>  $files
     * @return array{stdout: string, exitCode: int, timedOut: bool, truncated: bool}
     */
    private function send(array $files, string $entry): array
    {
        if (blank($this->url) || blank($this->secret)) {
            throw new PhpSandboxUnavailable('RUNNER_URL and RUNNER_SECRET must be set.');
        }

        $body = json_encode(['files' => $files, 'entry' => $entry], JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->getTimestamp();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Timestamp' => $timestamp,
                'X-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, $this->secret),
            ])
                ->withBody($body, 'application/json')
                ->timeout($this->timeLimitSeconds * 2)
                ->post(rtrim($this->url, '/').'/run');
        } catch (ConnectionException $e) {
            throw new PhpSandboxUnavailable('Could not reach the runner: '.$e->getMessage(), previous: $e);
        }

        $result = $response->json();

        if (! $response->successful() || ! is_array($result) || ! isset($result['stdout'], $result['exitCode'], $result['timedOut'], $result['truncated'])) {
            throw new PhpSandboxUnavailable("The runner responded with {$response->status()}: ".Str::limit($response->body(), 200));
        }

        return $result;
    }

    private function describeStop(string $output, array $result): string
    {
        return match (true) {
            $result['timedOut'] => $output."\n\nStopped after {$this->timeLimitSeconds} seconds.",
            $result['truncated'] => $output."\n…output truncated",
            default => $output,
        };
    }

    /** Snippets from the theory may leave out the opening tag, as in the web sandbox. */
    private function withOpeningTag(string $code): string
    {
        return str_starts_with(ltrim($code), '<?php') ? $code : "<?php\n\n".$code;
    }
}
```

- [ ] **Step 4: Add the configuration and binding**

In `app/config/services.php`, add before the closing `];`:

```php
    'runner' => [
        'url' => env('RUNNER_URL'),
        'secret' => env('RUNNER_SECRET'),
    ],
```

At the end of `app/.env.example`, add:

```dotenv
# The PHP runner (see ../runner/README.md). Same secret in both apps.
RUNNER_URL=http://127.0.0.1:8001
RUNNER_SECRET=
```

In `app/app/Providers/AppServiceProvider.php`, add `use App\Sandbox\HttpPhpSandbox;` and `use App\Sandbox\PhpSandbox;`, then add this to `register()` after the `SqlSandbox` binding:

```php
        $this->app->singleton(PhpSandbox::class, fn () => new HttpPhpSandbox(
            config('services.runner.url'),
            config('services.runner.secret'),
            resource_path('php/check-harness.php'),
        ));
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd app && php artisan test --filter=HttpPhpSandboxTest`
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app
git commit -m "Add HttpPhpSandbox that runs learner PHP on the runner"
```

---

### Task 6: Send PHP lessons and challenges to `PhpSandbox`

**Files:**
- Create: `app/app/Http/Requests/RunPhpRequest.php`, `app/tests/Feature/PhpSandboxApiTest.php`
- Modify: `app/app/Http/Controllers/Api/SandboxController.php`, `app/app/Http/Controllers/Api/ChallengeCheckController.php`, `app/app/Http/Resources/ChallengeResource.php` (doc comment), `app/README.md`

**Interfaces:**
- Consumes: `PhpSandbox::run()`, `PhpSandbox::check()`, `PhpSandbox::MAX_CODE_LENGTH` and `PhpSandboxUnavailable` from Task 5.
- Produces, for Plan 2:
  - `POST /api/lessons/{id}/run` with `{ code }` for PHP lessons → `{ output, fatal, durationMs }`.
  - `POST /api/challenges/{id}/check` with `{ code }` for PHP challenges → `{ passed, loaded, error, output, checks }`.
  - Both return 503 `{ message }` when the runner is unavailable. SQL lessons and challenges are unchanged (`{ sql }`).

- [ ] **Step 1: Write the failing tests**

Create `app/tests/Feature/PhpSandboxApiTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Lesson;
use App\Sandbox\PhpSandbox;
use App\Sandbox\PhpSandboxUnavailable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpSandboxApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function phpLesson(): Lesson
    {
        return Lesson::whereRelation('course', 'language', 'php')->firstOrFail();
    }

    public function test_php_lessons_run_code_in_the_php_sandbox(): void
    {
        $this->mock(PhpSandbox::class)
            ->shouldReceive('run')->once()->with('echo 1;')
            ->andReturn(['output' => '1', 'fatal' => false, 'durationMs' => 12]);

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => 'echo 1;'])
            ->assertOk()
            ->assertExactJson(['output' => '1', 'fatal' => false, 'durationMs' => 12]);
    }

    public function test_php_code_is_required(): void
    {
        $this->mock(PhpSandbox::class)->shouldNotReceive('run');

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'Write some PHP before running it.']);
    }

    public function test_php_code_length_is_limited(): void
    {
        $this->mock(PhpSandbox::class)->shouldNotReceive('run');

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => str_repeat('x', PhpSandbox::MAX_CODE_LENGTH + 1)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_sql_lessons_still_expect_sql(): void
    {
        $lesson = Lesson::where('dataset', 'bookstore')->firstOrFail();

        $this->postJson("/api/lessons/{$lesson->id}/run", ['code' => 'SELECT 1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sql');
    }

    public function test_php_challenges_are_checked_against_their_checks(): void
    {
        $challenge = Challenge::where('title', 'Your first class')->firstOrFail();
        $report = ['passed' => true, 'loaded' => true, 'error' => null, 'output' => '', 'checks' => [['label' => 'a', 'passed' => true, 'message' => null]]];

        $this->mock(PhpSandbox::class)
            ->shouldReceive('check')->once()->with('<?php class Book {}', $challenge->checks)
            ->andReturn($report);

        $this->postJson("/api/challenges/{$challenge->id}/check", ['code' => '<?php class Book {}'])
            ->assertOk()
            ->assertExactJson($report);
    }

    public function test_an_unavailable_sandbox_returns_503_with_a_readable_message(): void
    {
        $this->mock(PhpSandbox::class)
            ->shouldReceive('run')->andThrow(new PhpSandboxUnavailable('Could not reach the runner.'));

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => 'echo 1;'])
            ->assertStatus(503)
            ->assertExactJson(['message' => 'The PHP sandbox is unavailable. Try again shortly.']);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd app && php artisan test --filter=PhpSandboxApiTest`
Expected: the PHP tests FAIL. PHP lessons return 404 "This lesson has no SQL sandbox." or 422 on `sql`. `test_sql_lessons_still_expect_sql` passes already.

- [ ] **Step 3: Write `RunPhpRequest` and update the controllers**

Create `app/app/Http/Requests/RunPhpRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Sandbox\PhpSandbox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RunPhpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:'.PhpSandbox::MAX_CODE_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Write some PHP before running it.',
            'code.max' => 'Keep your code under :max characters.',
        ];
    }
}
```

Replace `app/app/Http/Controllers/Api/SandboxController.php` with:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunPhpRequest;
use App\Http\Requests\RunSqlRequest;
use App\Models\Lesson;
use App\Sandbox\PhpSandbox;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class SandboxController extends Controller
{
    /**
     * Runs learner code: SQL against a fresh copy of the lesson's dataset, PHP
     * on the runner. The form request is picked by the course's language.
     */
    public function __invoke(Lesson $lesson, SqlSandbox $sqlSandbox, PhpSandbox $phpSandbox): JsonResponse
    {
        if ($lesson->course->language === 'php') {
            return response()->json($phpSandbox->run(app(RunPhpRequest::class)->validated('code')));
        }

        abort_if($lesson->dataset === null, 404, 'This lesson has no SQL sandbox.');

        return response()->json($sqlSandbox->run($lesson->dataset, app(RunSqlRequest::class)->validated('sql')));
    }
}
```

Replace `app/app/Http/Controllers/Api/ChallengeCheckController.php` with:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunPhpRequest;
use App\Http\Requests\RunSqlRequest;
use App\Models\Challenge;
use App\Sandbox\PhpSandbox;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class ChallengeCheckController extends Controller
{
    /**
     * SQL answers are compared with the reference solution's result; PHP
     * answers are run against the challenge's checks.
     */
    public function __invoke(Challenge $challenge, SqlSandbox $sqlSandbox, PhpSandbox $phpSandbox): JsonResponse
    {
        if ($challenge->lesson->course->language === 'php') {
            return response()->json($phpSandbox->check(app(RunPhpRequest::class)->validated('code'), $challenge->checks));
        }

        abort_if($challenge->lesson->dataset === null, 404, 'This challenge has no SQL sandbox.');

        return response()->json($sqlSandbox->check(
            $challenge->lesson->dataset,
            app(RunSqlRequest::class)->validated('sql'),
            $challenge->solution,
            $challenge->order_matters,
        ));
    }
}
```

In `app/app/Http/Resources/ChallengeResource.php`, replace the class docblock's first two lines with:

```php
/**
 * The reference solution stays on the server. SQL answers are checked by
 * ChallengeCheckController; PHP answers run against `checks`, in the browser
 * (web app) or on the runner through ChallengeCheckController (mobile app).
```

- [ ] **Step 4: Run the whole `app/` suite to verify it passes**

Run: `cd app && php artisan test`
Expected: all PASS, including the existing `SandboxApiTest`, `ChallengeCheckApiTest` and `PhpChallengeHarnessTest`.

- [ ] **Step 5: Document it in `app/README.md`**

At the end of the "How the PHP sandbox works" section, add:

```markdown
The mobile app can't run WebAssembly PHP, so for PHP lessons `POST /api/lessons/{lesson}/run`
and `POST /api/challenges/{challenge}/check` (with `{ "code": "..." }`) run the code on the
**runner**, a separate Laravel app in `../runner` that holds no data or secrets
(`App\Sandbox\HttpPhpSandbox`). Checks use the same `check-harness.php`. To use these
endpoints locally, start the runner (see `../runner/README.md`) and set `RUNNER_URL` and
`RUNNER_SECRET` in `.env`. Without it they return 503.
```

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app
git commit -m "Run PHP lessons and challenges through PhpSandbox"
```

---

### Task 7: End-to-end check against a real runner

**Files:**
- Create: `app/tests/Feature/RunnerEndToEndTest.php`

**Interfaces:**
- Consumes: `HttpPhpSandbox` (Task 5) and a running runner (Tasks 1–4).

This proves every seeded PHP challenge solution still passes, and every starter still fails, under the runner's restrictions. `PhpChallengeHarnessTest` only proves this with an unrestricted CLI.

- [ ] **Step 1: Write the test**

Create `app/tests/Feature/RunnerEndToEndTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Sandbox\HttpPhpSandbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Runs the seeded PHP challenges on a real runner, with all its restrictions.
 * Skipped unless RUNNER_E2E_URL and RUNNER_E2E_SECRET are set; see ../runner/README.md.
 */
#[Group('runner')]
class RunnerEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function sandbox(): HttpPhpSandbox
    {
        $url = env('RUNNER_E2E_URL');
        $secret = env('RUNNER_E2E_SECRET');

        if (blank($url) || blank($secret)) {
            $this->markTestSkipped('Set RUNNER_E2E_URL and RUNNER_E2E_SECRET to run against a real runner.');
        }

        return new HttpPhpSandbox($url, $secret, resource_path('php/check-harness.php'));
    }

    public function test_a_plain_run_returns_output(): void
    {
        $result = $this->sandbox()->run('echo "hello";');

        $this->assertSame('hello', $result['output']);
        $this->assertFalse($result['fatal']);
    }

    public function test_blocked_functions_are_reported_as_fatal_errors(): void
    {
        $result = $this->sandbox()->run('echo exec("id");');

        $this->assertTrue($result['fatal']);
        $this->assertStringContainsString('undefined function exec()', $result['output']);
    }

    public function test_every_php_challenge_solution_passes_on_the_runner(): void
    {
        $sandbox = $this->sandbox();

        foreach (Challenge::whereNotNull('checks')->get() as $challenge) {
            $result = $sandbox->check($challenge->solution, $challenge->checks);

            $this->assertTrue($result['passed'], "{$challenge->title}: ".json_encode($result));
        }
    }

    public function test_every_php_challenge_starter_fails_on_the_runner(): void
    {
        $sandbox = $this->sandbox();

        foreach (Challenge::whereNotNull('checks')->get() as $challenge) {
            $this->assertFalse($sandbox->check($challenge->starter_code, $challenge->checks)['passed'], $challenge->title);
        }
    }
}
```

- [ ] **Step 2: Check that it's skipped by default**

Run: `cd app && php artisan test --filter=RunnerEndToEndTest`
Expected: 4 tests SKIPPED.

- [ ] **Step 3: Run it against a local runner**

```bash
cd /Users/macbook/Desktop/dev/learnP/runner
cp -n .env.example .env && php artisan key:generate
SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
RUNNER_SECRET=$SECRET php artisan serve --port=8001 &
cd ../app
RUNNER_E2E_URL=http://127.0.0.1:8001 RUNNER_E2E_SECRET=$SECRET php artisan test --filter=RunnerEndToEndTest
kill %1
```

Expected: 4 tests PASS. If a solution fails, the message includes the full result. The most likely cause is a function a solution legitimately needs being in `DisabledFunctions::NAMES`. Remove it from the list only if it can't reach outside the sandbox, and add a line to the runner README saying why.

- [ ] **Step 4: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/tests/Feature/RunnerEndToEndTest.php
git commit -m "Add end-to-end test of PHP challenges on a real runner"
```
