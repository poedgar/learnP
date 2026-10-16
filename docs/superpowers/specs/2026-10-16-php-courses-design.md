# New PHP courses: design

Date: 2026-10-16
Status: built in conversation

## Goal

Six new PHP courses that fit the existing PHP runner. A separate "Modern PHP 8.x" course was
considered and folded into the existing courses instead: PHP basics teaches `match`, named arguments,
spreading and destructuring, strict types, nullable/union/intersection types, `mixed`, `never`, `??=`
and `?->`; enums, readonly and property hooks were already in OOP in PHP and Advanced OOP in PHP; the
8.5 pipe operator is shown in Functional PHP. Course order is now:

| Position | Course | Slug | Lessons |
|----------|--------|------|---------|
| 1 | SQL | `sql` | 10 |
| 2 | **PHP basics** | `php-basics` | 11 |
| 3 | OOP in PHP | `php-oop` | 7 |
| 4 | Advanced SQL | `advanced-sql` | 10 |
| 5 | Advanced OOP in PHP | `php-oop-advanced` | 10 |
| 6 | **Design patterns in PHP** | `php-design-patterns` | 9 |
| 7 | **Algorithms and data structures** | `php-algorithms` | 9 |
| 8 | **Functional PHP** | `functional-php` | 4 |
| 9 | **Testing without frameworks** | `php-testing` | 5 |
| 10 | **Clean code and refactoring** | `php-clean-code` | 5 |

PHP basics comes before OOP in PHP, so a learner new to PHP starts there.

### PHP basics

Values and variables · Strings and formatting (`sprintf`, heredoc/nowdoc) · Comparisons and type
juggling (truthiness, casting, strict types) · Control flow (including `match`) · Arrays (spreading,
destructuring, copy semantics) · Array functions · Functions (defaults, named arguments, variadics,
references, recursion) · Dates (`DateTimeImmutable`, formats, intervals, time zones) · Types and null
safety · Regular expressions · Handling errors (`false`/`null` returns, warnings, exceptions).

### Design patterns in PHP

Patterns and factories · Builder and Singleton · Adapter and Facade · Decorator and Proxy · Composite ·
Strategy and Template Method · Observer and Command · State and Chain of Responsibility · Visitor, and
choosing a pattern.

### Algorithms and data structures

Big-O and searching · Sorting · Recursion · Stacks and queues · Linked lists · Hash maps and sets ·
Trees and heaps · Graphs · Dynamic programming, two pointers and sliding windows.

### Functional PHP

Pure functions and immutability · Higher-order functions and reduce · Currying and partial
application · Pipelines and lazy collections (composition, PHP 8.5 `|>` shown but not run, generators,
a lazy immutable `Pipeline` class).

### Testing without frameworks

Writing assertions · A tiny test runner (failures vs errors, fresh state, test classes found by
reflection, data providers, captured output, risky tests) · Writing good tests (arrange-act-assert,
equivalence classes, boundaries, characterisation, mutation testing) · Test doubles by hand (stubs,
fakes, spies, mocks, a controllable clock) · Test-driven development (red-green-refactor, katas).

Lesson 3's challenges grade the learner's **tests**: each check runs them against the correct function
(they must pass) and against a mutant (they must throw).

### Clean code and refactoring

Names and functions · Code smells and refactoring moves (Extract Function/Variable, Split Loop, Move
Function, Replace Temp with Query, Fowler's theatre statement) · Simplifying conditionals (guard
clauses, lookup tables, polymorphism, Null Object) · Better data (constants, enums, value objects,
parameter objects, encapsulated collections) · Working with legacy code (seams, sprout, wrap, removing
globals, the Gilded Rose kata in two parts).

Refactoring challenges start from working code. Checks confirm the behaviour is unchanged and read the
structure with Reflection and `learnp_code()`: function length, named parameters, private methods,
return types, and the absence of `else`, `switch`, `global` or commented-out code. The Gilded Rose
checks run the learner's version and the original side by side for 30 days.

The OOP in PHP enums lesson also gained an example of an enum implementing an interface, the one
Modern PHP topic that wasn't yet covered.

## Shape

Same as the other PHP courses: Markdown theory with runnable ` ```php ` blocks (`// Error:` blocks must
fatal), 8 quiz questions and 6 challenges per lesson, checked by `resources/php/check-harness.php`.
Content lives in `app/database/content/<slug>/NN-slug.{md,php}`. Every new course (and the refactored
Advanced SQL and Advanced OOP seeders) extends the abstract `ContentCourseSeeder`, which upserts the
course and lessons and syncs quiz questions and challenges by position.

## Harness additions

- `learnp_output()`: what the learner's script printed.
- `learnp_code()`: the learner's source, for structural checks (e.g. "uses at most one loop").
- `learnp_run($code)`: runs a variant of the learner's script and returns its output.
- Failed checks show arrays up to 160 characters as JSON instead of "an array with N items".

## Constraints

- PHP 8.4 everywhere learners run code (php-wasm in the browser; `php -n` on the runner), with only
  core, SPL, Reflection, JSON and PCRE. No `mbstring`/`ctype`; no rounding on `.x5` boundaries; no
  deprecations. A PHP 8.5-only `|>` example is in a plain fence so it isn't run.
- A check must never hang the browser. Slow learner code is caught by timing a small case first
  (naive Fibonacci, coin change), by structural checks instead of timing for O(n²) vs O(n), by a
  hooked `GuardedNode` that throws after 100 000 reads (cycle detection), and by sources that throw
  after 1000 items (laziness).
- Theory never contains a challenge's solution, and every starter fails at least one check.

## Verification

- `PhpLessonContentTest` covers all PHP courses; `PhpChallengeHarnessTest` runs all 359 PHP solutions
  and starters with the PHP CLI; `CourseApiTest` checks the course list and lesson counts.
- The new solutions are also run through `RunnerEndToEndTest` against a local runner and through the
  web app's in-browser PHP 8.4.

## Out of scope

Web and mobile code changes: both apps list courses from the API.
