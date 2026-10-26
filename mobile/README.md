# learnP mobile

The learnP courses on iOS and Android, built with Expo (SDK 57) and Expo Router. It uses the same
API as the web app (`../app`). SQL and PHP both run on the server; PHP runs on the separate runner
(`../runner`). JavaScript, TypeScript and HTML & CSS run on the phone, in a WebView (`src/js-runtime`), with the
same engine as the web app; TypeScript loads its compiler from the API server's
`/vendor/typescript/` (created by `npm run build` or `npm run dev` in `../app`). After changing the
engine in `../app/resources/js/lib/js`, run `npm run sync-js-runtime` here.

## Running it on your phone

1. Install **Expo Go** on your phone. Your phone and computer must be on the same Wi-Fi.
2. Start the API so the phone can reach it:
   ```sh
   cd ../app && php artisan serve --host=0.0.0.0 --port=8000
   ```
   For PHP lessons, also start the runner (see `../runner/README.md`) and set `RUNNER_URL` and
   `RUNNER_SECRET` in `../app/.env`.
3. Point the app at your computer's LAN address (find it with `ipconfig getifaddr en0` on macOS):
   ```sh
   cp .env.example .env   # then edit EXPO_PUBLIC_API_URL, e.g. http://192.168.1.20:8000
   ```
4. Start Expo and scan the QR code with the camera (iOS) or Expo Go (Android):
   ```sh
   export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"
   npm install
   npx expo start
   ```

Changing `.env` needs a restart of `npx expo start`.

`.env` isn't committed, so EAS builds never see it: the `preview` and `production` profiles in
`eas.json` set `EXPO_PUBLIC_API_URL` to the live server instead.

## How it's built

- `app/`: routes (`/`, `/courses/[course]`, `/courses/[course]/lessons/[lesson]?step=…`). Deep links use the scheme `learnp://`.
- `src/api`: typed client. Its error messages match the web app's `api.js`.
- `src/progress`: progress stored on the device (AsyncStorage key `learnp:progress:v2`). There are no accounts.
- `src/components/markdown`: lesson Markdown tokenised with `marked` and drawn as native views, with code highlighted by Prism.
- `src/editor`: CodeMirror 6 in an Expo DOM component (`'use dom'`), with a native key bar above the keyboard.
  - The editor only reports text; the app replaces it by bumping `resetKey`, so typing is never overwritten.

## Tests

```sh
npm test
npm run typecheck
```

Jest replaces the WebView editor with a `TextInput` (`src/editor/__mocks__`).

## Manual checklist (Expo Go, on a real phone)

Run through this on one iPhone and one Android phone before a release.

- [ ] The course list loads. With the API stopped, you see "Could not reach the server…", and **Try again** works once the API is back.
- [ ] Opening a course shows its lessons; opening a lesson lands on **Learn**.
- [ ] Learn:
  - [ ] Next and Previous change sections.
  - [ ] Tables scroll sideways.
  - [ ] **Try it in the sandbox** opens the Sandbox with that code and runs it.
- [ ] Sandbox (SQL):
  - [ ] **Show tables** and **Preview** work.
  - [ ] Running shows rows.
  - [ ] An error shows the statement number.
  - [ ] **Restore example** works.
- [ ] Sandbox (PHP): echo output shows. With the runner stopped you see "The PHP sandbox is unavailable. Try again shortly." and your code stays.
- [ ] Sandbox (JavaScript), with the phone offline apart from the API:
  - [ ] `console.log()` output shows, and an error shows its line.
  - [ ] `while (true) {}` stops after about 2 seconds with a message, and the app stays responsive.
  - [ ] In a DOM lesson, the page appears under the output; tapping its buttons works and adds their logs.
- [ ] Sandbox (HTML & CSS): **Run** shows the page under the output, styled by its `<style>`; the key bar has `< >`, `</` and `=" "`.
- [ ] Sandbox (TypeScript): the first run loads the compiler from the API server; a type error shows its line and the code doesn't run.
- [ ] Challenges (JavaScript, TypeScript and HTML): **Check answer** gives the verdict and the check list; the Grid lesson's narrow and wide checks pass.
- [ ] Editor:
  - [ ] Typing fast (hold a key, paste a long snippet) loses no characters.
  - [ ] The key bar appears above the keyboard, inserts `$`, `->` and `{}`, and paired keys put the cursor inside.
  - [ ] The keyboard stays open while you use the key bar.
  - [ ] The editor stays visible while typing.
- [ ] Switching Learn → Sandbox → Quiz → Sandbox keeps the Sandbox code.
- [ ] Quiz:
  - [ ] Answers show Correct/Not quite and the explanation.
  - [ ] The numbers turn green and red.
  - [ ] **Retake the quiz** clears answers.
- [ ] Challenges:
  - [ ] **Check answer** shows the verdict and details.
  - [ ] **Next challenge** works.
  - [ ] Solved challenges show ✓.
  - [ ] **Start over** works.
- [ ] Progress survives closing and reopening the app. The course screen shows "Completed." for a finished lesson.
- [ ] Dark mode (switch the phone's appearance) keeps everything readable, including the editor.
- [ ] `npx uri-scheme open "learnp://courses/sql/lessons/your-first-queries?step=quiz" --ios` (or tapping such a link) opens the quiz directly.

## Topics:
Here's the list, built around what learnP already has: **SQL** (10 lessons, running in SQLite on the server) and **OOP in PHP** (7 lessons, running on the runner).

I sorted topics by what each needs:
- **(fits)** works with the sandboxes you already have.
- **(limited)** works only in part, because of how the sandboxes are restricted.
- **(new runner)** needs a new way to run code.

The PHP runner's restrictions matter here: `php -n` with no extensions loaded from config, a read-only folder, no network, 5 seconds and 64 MB per run.

## 1. More SQL (all fit in SQLite unless marked)

**Fundamentals not covered yet**
- NULL in depth: three-valued logic, `IS NULL`, `COALESCE`, `NULLIF`, NULLs in aggregates and joins
- `CASE` expressions: conditional columns, pivoting with `SUM(CASE …)`
- Set operations: `UNION` / `UNION ALL`, `INTERSECT`, `EXCEPT`
- Self-joins and hierarchies: employees and managers, categories
- Every kind of join: `LEFT`, `RIGHT`, `FULL OUTER` and `CROSS` joins, and anti-joins with `NOT EXISTS`
- Dates and times: `date()`, `strftime`, ranges, grouping by month or week
- Text functions: `LIKE` vs `GLOB`, `substr`, `instr`, `replace`, `trim`, case-insensitive search

**Intermediate**
- Recursive CTEs: org charts, trees, generating series, graph walks
- Advanced window functions: frames (`ROWS BETWEEN`), `LAG`/`LEAD`, running totals, moving averages, `NTILE`, top-N per group
- Pivot and unpivot patterns
- Deduplication: finding and removing duplicates, keeping the latest row
- Gaps and islands: streaks, consecutive days, missing ids
- `UPSERT` (`INSERT … ON CONFLICT`) and `RETURNING`
- Working with JSON: `json_extract`, `json_each`, `->`/`->>`

**Design and internals**
- Normalization: 1NF–3NF, and when to denormalize
- Keys and constraints: `PRIMARY KEY`, `FOREIGN KEY`, `UNIQUE`, `CHECK`, `ON DELETE CASCADE`
- Many-to-many relations and junction tables
- Indexes: what they are, `EXPLAIN QUERY PLAN`, composite and covering indexes
- Views, and when to use them
- Transactions and ACID (`BEGIN`/`COMMIT`/`ROLLBACK`, savepoints)
- Triggers
- Generated columns
- Full-text search with FTS5 (check that your SQLite build includes it)

**Practice courses on new datasets**
- SQL for analytics: cohorts, retention, funnels, A/B results
- An e-commerce case study
- A library or school database case study
- "SQL interview questions": a challenge-only course

**Limited by SQLite**
- PostgreSQL- or MySQL-specific features: stored procedures, roles and permissions, `LATERAL`, arrays. These need a Postgres runner.

## 2. More OOP in PHP (all fit)

- Magic methods: `__toString`, `__get`/`__set`, `__call`, `__invoke`, `__clone`
- Cloning, shallow vs deep copies, and value objects
- Late static binding (`static::` vs `self::`)
- First-class callables, closures and `Closure::bind`
- Iterators and generators: `Iterator`, `IteratorAggregate`, `yield`
- `ArrayAccess`, `Countable`, `Stringable`
- Property hooks and asymmetric visibility (PHP 8.4)
- Attributes and reflection
- Namespaces and autoloading as concepts (no Composer in the sandbox)
- The SOLID principles, one lesson each or one combined lesson
- Composition over inheritance
- Dependency injection and a tiny container
- Immutability and `with…` methods
- Equality and comparison of objects
- Weak references (`WeakMap`)

## 3. New PHP courses (fit the runner)

**PHP basics**: a beginner course before OOP
- Variables and types, strings, numbers, type juggling and its gotchas
- Arrays, both indexed and associative, and array functions (`array_map`, `array_filter`, `usort`, …)
- Control flow, `match`, loops
- Functions: default and named arguments, variadics, strict types
- String formatting (`sprintf`, heredoc and nowdoc)
- Dates with `DateTimeImmutable`
- Regular expressions (`preg_*`)
- Error handling basics

**Modern PHP (8.x features)**
- Union and intersection types, `never`, `mixed`, nullsafe `?->`, `match`, named arguments, readonly classes, enums with interfaces, first-class callable syntax, property hooks

**Design patterns in PHP**
- Creational: Factory, Builder, Singleton (and why to avoid it)
- Structural: Adapter, Decorator, Composite, Facade, Proxy
- Behavioural: Strategy, Observer, Command, State, Chain of Responsibility, Template Method, Visitor

**Algorithms and data structures in PHP**
- Big-O, then searching and sorting
- Recursion
- Stacks, queues, linked lists and `SplStack`
- Hash maps and sets
- Trees, binary search trees and heaps
- Graphs: BFS, DFS, Dijkstra
- Dynamic programming, two pointers, sliding windows

**Functional PHP**
- Pure functions, higher-order functions, `array_reduce`, composition, currying, pipelines

**Testing without frameworks**
- Writing assertions, test doubles by hand, TDD katas. Your challenge checks already work like a test runner.

**Clean code and refactoring**
- Code smells, then refactoring challenges that start from messy code

**Limited by the runner**
- PDO and databases from PHP: the runner deliberately has no database. You could allow an in-memory SQLite PDO, but that's a security decision.
- Files and streams: the run folder is read-only. Input files are fine, writing isn't.
- HTTP and APIs: there's no network. Requests and responses could only be simulated with arrays.
- Laravel or Symfony: they need Composer packages and a framework install.

## 4. Courses that need a new runner (new runner)

**Languages**
- JavaScript: basics, then async (Promises, `async`/`await`), then DOM. This could run in the browser or a WebView with no server.
- TypeScript
- Python: basics, OOP, data handling
- Go, Rust, Java, C#: good for a "learn a second language" track

**Web**
- HTML and CSS: a live preview sandbox in a WebView
- Regex as its own course: it could run in-browser in JS, or on the PHP runner with `preg_*`

**Data and tools**
- Bash and the command line: needs a heavily sandboxed shell
- Git: commands simulated against a toy repository
- PostgreSQL-specific SQL
- MongoDB and NoSQL concepts
- Pandas or data analysis

**Concepts that need no code runner**
- These could use quiz-only lessons or diagram-based lessons.
- How the web works: HTTP, DNS, cookies, REST
- Security basics: SQL injection (which you can demonstrate in the SQL sandbox), XSS, CSRF, password hashing
- System design basics

## Where I'd start

1. **SQL: NULLs, CASE and set operations.** These are the most-used gaps, and they fit the current SQLite setup.
2. **PHP basics.** Right now the PHP track starts at OOP, so beginners have no way in.
3. **Design patterns in PHP.** It follows directly from lesson 7, "Putting objects together".
4. **SQL: indexes and EXPLAIN, plus transactions.** This completes the design side of the course.

I can draft a lesson plan for any of these in the same format as [docs/superpowers](docs/superpowers).


