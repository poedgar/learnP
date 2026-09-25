# learnP mobile: Expo app and server-side PHP runner

Date: 2026-09-25
Status: Design approved in conversation; awaiting spec review

## Goal

Ship a real mobile version of learnP (iOS and Android) with the same features as the web app:
the four lesson steps (Learn, Sandbox, Quiz, Challenges) for both the SQL and the OOP in PHP courses.

Success means that a learner on a phone can read a lesson, run SQL and PHP in a sandbox, answer
the quiz and pass the challenges, with progress kept on the device, just as on the web.

### Decisions made

| Question | Decision |
|---|---|
| Purpose | A real, shippable mobile version with feature parity with the web app |
| Where learner PHP runs on mobile | On the server, not on the device |
| How server-side PHP is isolated | A separate "runner" Laravel Cloud application that holds no secrets or data |
| Hosting | Laravel Cloud |
| Mobile code editor | CodeMirror 6 in a WebView, with a native key bar |
| Web app | Unchanged: it keeps running PHP in the browser with php-wasm |

### Out of scope

Accounts and progress sync, offline mode, push notifications, app store submission and EAS
configuration, and any change to the Vue web app.

## Architecture

```
learnP/
├── app/      Laravel API + Vue web app (existing): gets server-side PHP support
├── runner/   NEW: minimal Laravel app on Laravel Cloud that only runs PHP
└── mobile/   NEW: Expo app (TypeScript, Expo Router)
```

A PHP run from the phone:

```
Expo app ──POST /api/lessons/{id}/run {code}──▶ main API (app/)
                                                 │ validates, rate-limits,
                                                 │ signs the request (HMAC)
                                                 ▼
                                          runner/ POST /run
                                                 │ checks the signature, starts a restricted
                                                 │ child `php` with a 5 s timeout
                                                 ▼
                                          { stdout, exitCode, timedOut, truncated }
```

- The mobile app talks only to the main API. The runner's URL and secret never ship in the app,
  and rate limiting stays in one place (the existing `throttle:sandbox` limiter, 60/min per IP).
- The runner holds no database, sessions or secrets other than its own signing secret. If learner
  code escapes PHP's restrictions, it reaches only a disposable container with nothing of value.

## Part 1: Main API changes (`app/`)

### PhpSandbox

- `App\Sandbox\PhpSandbox` interface:
  - `run(string $code): array` returns `{ output: string, fatal: bool, durationMs: int }`,
    the same shape as the web app's `runScript()` in `resources/js/lib/php/sandbox.js`.
  - `check(string $code, array $checks): array` returns
    `{ passed: bool, loaded: bool, error: ?string, output: string, checks: [{ label, passed, message }] }`,
    the same shape as the web app's `checkChallenge()`.
- `App\Sandbox\HttpPhpSandbox`, the production implementation:
  - `run()` sends `{ files: { "main.php": <code> }, entry: "main.php" }`.
  - `check()` sends `main.php`, `checks.json` (the challenge's `checks`) and `harness.php`
    (the contents of `resources/php/check-harness.php`), with `entry: "harness.php"`. It then
    splits stdout on the `\n@@LEARNP_REPORT@@\n` marker and decodes the JSON report the same way
    `checkChallenge()` does, and sets `passed = loaded && every check passed`.
  - Code without an opening `<?php` tag gets one prepended (like `withOpeningTag()`).
  - Leading newlines are removed from the output, as the web app does.
  - A timeout adds `\n\nStopped after 5 seconds.` to the output and sets `fatal: true`.
    Truncation adds `\n…output truncated`.
  - HTTP timeout is 10 s. The runner's `401`, any `5xx`, connection errors and timeouts are
    logged and turned into a `PhpSandboxUnavailable` exception, which is rendered as
    **503** `{ "message": "The PHP sandbox is unavailable. Try again shortly." }`.
  - A check whose report marker is missing returns `loaded: false` with the error
    "The checker could not read your code. Run it first to look for errors."
- Tests replace `PhpSandbox` with a Mockery mock (`$this->mock(PhpSandbox::class)`).
- Configuration in `config/services.php`: `runner.url`, `runner.secret` (from `RUNNER_URL` and
  `RUNNER_SECRET`).

### Routes and controllers

The routes stay the same. The controllers choose a sandbox based on the lesson's course language:

- `POST /api/lessons/{lesson}/run`: SQL lessons keep using `SqlSandbox` with `{ sql }`;
  PHP lessons use `PhpSandbox::run()` with `{ code }`.
- `POST /api/challenges/{challenge}/check`: SQL challenges are unchanged; PHP challenges use
  `PhpSandbox::check()` with `{ code }` and the challenge's `checks`.
- A new `RunPhpRequest` validates `code` as a required string of at most 20,000 characters, with
  messages that match `RunSqlRequest`'s tone.
- `ChallengeResource` keeps sending `checks`, because the web app still checks PHP in the browser.
  Update its doc comment to say that PHP answers can be checked in either place.

## Part 2: The runner (`runner/`)

A minimal Laravel application (PHP 8.4, matching the web app's php-wasm version) with one route
and no database, sessions, cache or views. It is deployed to Laravel Cloud as its own
application, with no attached resources and one secret, `RUNNER_SECRET`.

### API

```
POST /run
X-Timestamp: <unix seconds>
X-Signature: hex(hmac_sha256(timestamp + "." + rawBody, RUNNER_SECRET))
{ "files": { "<name>": "<contents>", ... }, "entry": "<name>" }

200 → { "stdout": string, "exitCode": int, "timedOut": bool, "truncated": bool }
401 → bad signature, or a timestamp more than 60 s away from the server clock
422 → invalid body
```

- File names must match `^[a-z0-9_-]+\.(php|json)$`, `entry` must be one of them, and there can be
  at most 5 files and 100 KB of content in total.
- The signature is compared with `hash_equals`.

### Running code

1. Create `/tmp/run-<random>/` and write the files into it.
2. Start the child with Symfony Process: `php -n <ini flags> <entry>`, with an **empty
   environment** and the temp folder as the working directory. The ini flags are:
   - `-n`: no `php.ini`. This does **not** remove extensions compiled into the binary (Homebrew's
     PHP has curl, sockets, PDO and FFI built in), so the settings below are what matter.
   - `open_basedir=/tmp/run-<random>`
   - `allow_url_fopen=0`, `allow_url_include=0`, `ffi.enable=0`
   - `memory_limit=64M`, `max_execution_time=5`
   - `display_errors=stdout`, `log_errors=0`, `error_reporting=E_ALL`, `html_errors=0`
   - `disable_functions=`: program execution (`exec`, `shell_exec`, `system`, `passthru`, `popen`,
     `proc_*`), process and settings control (`pcntl_*`, `posix_*`, `putenv`, `ini_set`,
     `set_time_limit`…), mail and logging, links and permissions, DNS, and sockets (`fsockopen`,
     `stream_socket_*`, `socket_*`, `curl_*`, `ftp_*`…). `disable_functions` has no wildcards,
     so prefixed families are expanded from the **child binary's own** function list
     (`App\Runner\DisabledFunctions`).
3. Stop the process after 6 s of real time. PHP's own 5 s `max_execution_time` stops busy loops
   first, with a readable error; the real-time limit catches code that sleeps or blocks. If more
   than 64 KB of output is produced, stop the process and set `truncated`.
4. Delete the temp folder in a `finally` block.

### Before launch

Read the runner's environment on Laravel Cloud and confirm that it contains only `RUNNER_SECRET`
and Cloud's harmless defaults: no database, cache or storage credentials, and no `APP_KEY` value
used elsewhere.

### Known remaining risk

`disable_functions` can't remove classes. If the binary includes `pdo_mysql`, `mysqli` or `soap`,
learner code can still open network connections through those classes. The runner holds nothing
to steal; the risk is that it could be used to reach other hosts. The runner README documents this.

## Part 3: The Expo app (`mobile/`)

### Stack

The current Expo SDK, TypeScript, Expo Router, TanStack Query (API data), Zustand with
AsyncStorage (progress), `react-native-webview` (editor), `react-native-keyboard-controller`
(key bar), `react-native-markdown-display` (theory), and Prism (highlighting code blocks as
native text). It follows the system's light or dark mode. The API base URL comes from
`EXPO_PUBLIC_API_URL`.

### Screens

| Route | Screen |
|---|---|
| `/` | Course list, with a progress bar per course |
| `/courses/[course]` | Lessons in order, each with a Read / Quiz / Challenges summary |
| `/courses/[course]/lessons/[lesson]?step=` | Lesson with a segmented control: Learn · Sandbox · Quiz · Challenges |

- The URL scheme is `learnp://`, and its paths mirror the web app's routes.
- The steps switch only when their segment is tapped, not by swiping, so swipes can't conflict
  with scrolling in the editor. A step that has been opened stays mounted, so editor contents
  survive switching steps.
- The segments show status as the web app does: "Read", quiz `correct/total`, and challenges
  `passed/total`.

### Lesson steps

- **Learn:** Markdown theory. Each code block is highlighted and has a "Try it in the sandbox"
  button that opens the Sandbox with that code. "Done reading" marks the lesson as read and opens
  the Sandbox.
- **Sandbox:** the editor, a Run button and a results panel.
  - SQL shows one table per statement, scrollable sideways, with a collapsible schema panel.
  - PHP shows the output, in the error style when `fatal`.
  - The code is saved per lesson in `sandboxCode`.
- **Quiz:** tapping an option calls `POST /api/quiz-questions/{id}/answer`, then shows whether it
  was correct and the explanation. The result is stored in progress.
- **Challenges:** each challenge shows its prompt, an editor with its starter code (or the saved
  attempt), Check, and Hint.
  - SQL failures show the server's comparison message.
  - PHP results list each check as passed or failed with its message, plus any output.
  - A pass is stored in progress.

The quiz and challenge result displays match what the web app shows for the same API responses.
Read `resources/js/components/lesson/*.vue` when building each step.

### Editor

- `editor-web/` contains the CodeMirror 6 source, with the SQL and PHP languages and light and
  dark themes. An esbuild script bundles it into one self-contained HTML string in
  `src/editor/editorHtml.ts`, so the editor loads nothing from the internet.
- `CodeEditor.tsx` hosts it in a WebView. The two sides exchange JSON messages:
  - From React Native to the editor: `setValue`, `insert { text, cursorOffset }`, `setLanguage`,
    `setTheme`, `setReadOnly`, `focus`.
  - From the editor to React Native: `ready`, `change { value }`, `focus`, `blur`.
  - Values are sent back to the editor only when they differ from what it last reported, so
    changes don't bounce back and forth.
- `KeyBar.tsx` sits above the keyboard while the editor has focus.
  - PHP keys: Tab, `$`, `->`, `;`, `{}`, `()`, `[]`, `'`, `=>`.
  - SQL keys: Tab, `*`, `;`, `()`, `'`, `,`, `=`.
  - Paired keys put the cursor between the pair.

### Data and progress

- `src/api/`: a typed client whose types match the Laravel resources. Its error rules match
  `resources/js/api.js`:
  - A network failure gives "Could not reach the server. Check your connection and try again."
  - 429 gives the existing "running queries very quickly" message.
  - Any other error uses the server's `message`.
- `src/progress/`: a port of `resources/js/progress.js`, with the same state shape and the same
  `lessonSummary()` rules, saved to AsyncStorage under `learnp:progress:v2`.
- The app needs a connection. TanStack Query keeps lessons that have been opened in memory for
  the session.

### Layout

```
mobile/
  app/                       Expo Router screens
  src/api/                   client + types
  src/progress/              store + summary logic
  src/components/            ResultTable, PhpOutput, Markdown, CodeBlock, StepSwitcher, LoadState…
  src/editor/                CodeEditor.tsx, KeyBar.tsx, editorHtml.ts (generated)
  editor-web/                CodeMirror source + build script
```

## Errors: from runner to screen

| What happens | Runner | Main API | App shows |
|---|---|---|---|
| Code runs, may print warnings | `exitCode 0` | `{ output, fatal: false, durationMs }` | Output |
| Fatal error or exception | `exitCode 255` | `fatal: true` | Output in the error style |
| Endless loop | `timedOut: true` | `fatal: true`, output ends with "Stopped after 5 seconds." | Same |
| Too much output | `truncated: true` | output ends with "…output truncated" | Same |
| Bad or expired signature | 401 | logged, **503** | Error banner with Retry |
| Runner down or slow (10 s) | — | **503** | Error banner with Retry |
| Too many runs | — | 429 | Existing rate-limit message |
| No network | — | — | "Could not reach the server…" |

## Testing

- **`runner/`** (PHPUnit, as in `app/`; feature tests that start a real `php` child):
  - `echo` output is returned; a fatal error gives exit code 255.
  - `while (true) {}` is stopped at 5 s and `timedOut` is set.
  - Each blocked capability fails: `exec`, `shell_exec`, `file_get_contents('/etc/passwd')`,
    `getenv()` returning an empty array, `fsockopen`, and `ini_set('open_basedir', '/')`.
  - Output over 64 KB is truncated, and temp folders are deleted after success, failure and timeout.
  - Missing, wrong and expired signatures get 401; invalid file names and entries get 422.
- **`app/`** (PHPUnit):
  - PHP run and check endpoints are tested with a mocked `PhpSandbox`: validation, response shape, and
    503 when the sandbox is unavailable.
  - `HttpPhpSandbox` is tested with `Http::fake`: the signature headers are correct, report
    parsing works, a missing marker is handled, and timeout and truncation suffixes are added.
  - The existing SQL tests and `PhpChallengeHarnessTest` keep passing.
- **`mobile/`** (Jest with `jest-expo` and React Native Testing Library):
  - Progress summary rules, using the same cases the web logic implies.
  - The API client's error mapping.
  - The Quiz and Challenges steps against a mocked API.
  - The editor bridge's message handling.
  - A written manual checklist, run on the iOS simulator and an Android emulator.

## Build order

1. The runner, with its tests.
2. `PhpSandbox` in the main API, with its tests. After this step the API fully supports mobile.
3. The Expo app: scaffold, API client and progress, screens, the Learn and Quiz steps, the editor,
   then the Sandbox and Challenges steps.
4. Manual device checklist; deploy the runner and configure `RUNNER_URL` and `RUNNER_SECRET`.
