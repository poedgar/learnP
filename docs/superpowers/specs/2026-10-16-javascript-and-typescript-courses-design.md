# JavaScript and TypeScript courses: design

Date: 2026-10-16
Status: built in conversation

## Goal

Four courses from section 4 of the topic list ("Courses that need a new runner"), each with
8 quiz questions and 6 challenges per lesson:

| Position | Course | Slug | Language | Lessons |
|----------|--------|------|----------|---------|
| 11 | JavaScript basics | `js-basics` | `javascript` | 8 |
| 12 | Async JavaScript | `js-async` | `javascript` | 5 |
| 13 | JavaScript and the DOM | `js-dom` | `javascript` | 5 |
| 14 | TypeScript | `typescript` | `typescript` | 6 |

- **JavaScript basics**: values and variables · comparisons and logic · control flow · functions ·
  arrays · objects and JSON · classes · errors, Map and Set.
- **Async JavaScript**: callbacks and the event loop · promises · async and await · combining
  promises · async patterns (generators, AbortController, backoff, throttle, queues).
- **JavaScript and the DOM**: finding things · changing the page · events · forms · building a
  small app (state and render).
- **TypeScript**: types and inference · object types · unions and narrowing · functions and
  generics · classes and utility types · type-level tools.

## Decisions

- No server runner: code runs on the learner's device, as the topic list suggested. The run and
  check endpoints answer 422 for these courses.
- TypeScript uses the real compiler for type errors (chosen over stripping types), loaded only on
  TypeScript lessons. TypeScript 7 is the Go port, so the browser uses 6.0.3, the last JavaScript
  release.

## Engine (`app/resources/js/lib/js/`)

- `prepare.js`: acorn syntax check (strict mode, learner line numbers), loop guards
  (`loop-guard.js`), and one async wrapper function holding the learner's code and the checks
  (hoisted function declarations), so top-level `await` works and checks see `let`/`const`.
- `frame-runtime.js`: runs inside a fresh sandboxed iframe per run (`allow-scripts allow-forms`, no
  same-origin): console capture with Node-style formatting, a loop guard (2 seconds of synchronous
  looping throws), timer tracking (runs wait up to 5 seconds for timers), uncaught errors, check
  helpers, `runVariant()` (also loop-guarded). Injected as source text, with acorn.
- `engine.js`: environment-independent: builds the job, sends it to a frame, collects logs and
  per-check results, and enforces a 10-second hard limit (an unfinished check is named).
- `ts-service.js`: compiler host over lib.es2023 and a small globals declaration; code is checked as
  a module (for top-level await), emitted as ES2023, and `{ compiles }`/`{ rejects }` checks append
  TypeScript to the learner's code.
- Web: `sandbox.js` (iframes, a blob worker that `importScripts` the compiler from
  `/vendor/typescript/6.0.3/`, which `vite.config.js` publishes), `JsSandboxStep.vue`,
  `JsOutput.vue`, and `PagePreview.vue` (a run that builds a page keeps its frame, live).
- Mobile: `src/js-runtime/` holds synced copies (`npm run sync-js-runtime`, with a test against
  drift), a `'use dom'` `JsRunnerView` doing the same in the WebView, and `useJsRunner()`.

## Verification

- `JsLessonContentTest` (PHPUnit) checks lesson order and counts, that no solution is shown in the
  theory or quiz, and runs `tests/js/verify-content.mjs`: every example (`// Error:` ones must fail),
  quiz snippets (stand alone, and match single-code-span answers), every solution passes and every
  starter fails, with the same engine in Node and jsdom.
- All 144 solutions pass through the web UI in Chromium, and all 240 theory examples behave there.
- The exported mobile runner bundle was driven in Chromium through Expo's DOM bridge protocol
  (JavaScript, the loop guard, checks, a live page, TypeScript errors and checks). Real iOS and
  Android WebViews still need testing on devices; see the checklist in `mobile/README.md`.
- Mobile Jest tests cover the JavaScript sandbox and challenge paths with a stand-in runner.
