# Advanced OOP in PHP course: design

Date: 2026-10-16
Status: built in conversation

## Goal

A course, **Advanced OOP in PHP** (`php-oop-advanced`, position 5 since PHP basics was added), for learners who finished
OOP in PHP. It builds on that course rather than repeating it: `Stringable`/`Countable`, `self::` vs
`static::`, readonly classes and withers, value objects, composition and constructor injection are
referred back to, not retaught.

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 1 | Magic methods | `magic-methods` | `__get`/`__set`/`__isset`/`__unset`, `__call`/`__callStatic`, `__invoke`, the cost of magic |
| 2 | Object identity | `object-identity` | handles, `clone`, shallow and deep copies with `__clone`, `==`/`===`, `compareTo()` and `<=>`, `WeakMap` |
| 3 | Closures and callables | `closures-and-callables` | closures and `use`, arrow functions, every shape of callable, `name(...)`, functions returning functions, `Closure::bind`/`call` |
| 4 | Late static binding and fluent interfaces | `late-static-binding-and-fluent-interfaces` | `new static`, `static` return type, `static::` constants, shared static properties, fluent and immutable fluent APIs |
| 5 | Iterators and generators | `iterators-and-generators` | `IteratorAggregate`, `Iterator`, `yield`, keys, lazy pipelines, `yield from` |
| 6 | Objects that act like arrays | `objects-that-act-like-arrays` | `ArrayAccess` (and `$c[]`), rules in `offsetSet()`, `Countable` + `IteratorAggregate`, `JsonSerializable` |
| 7 | Property hooks and asymmetric visibility | `property-hooks-and-asymmetric-visibility` | PHP 8.4 `get`/`set` hooks, virtual properties, `private(set)`/`protected(set)` vs `readonly`, properties in interfaces |
| 8 | Attributes and reflection | `attributes-and-reflection` | `ReflectionClass`/`Property`/constructor parameters, `#[Attribute]`, targets, a validator and a router |
| 9 | Namespaces and autoloading | `namespaces-and-autoloading` | namespaces (braced form for one file), `use`/`as`, the global-class backslash trap, autoloaders, PSR-4, Composer |
| 10 | SOLID and a tiny container | `solid-and-a-tiny-container` | the five principles, decorators, a container with factories and singletons, autowiring with reflection |

Same shape as OOP in PHP: Markdown theory with runnable ` ```php ` blocks (`// Error:` blocks must stop
with a fatal error), 8 quiz questions and 6 challenges per lesson, each challenge checked by PHP
snippets run after the learner's code (`resources/php/check-harness.php`). Content lives in
`app/database/content/php-oop-advanced/` and is seeded by `AdvancedPhpOopCourseSeeder`.

## Constraints

- Code must run on PHP 8.4 in both places learners run it: the browser (php-wasm, web app) and the
  runner (`php -n` with disabled functions, read-only folder; mobile app). Only core, SPL, Reflection,
  JSON and PCRE are used. Autoloading is taught with a logging autoloader and PSR-4 string functions,
  since the sandbox runs a single file.
- One check was dropped because PHP 8.4 and 8.5 differ: reading a hooked property of another object of
  the same class from inside that property's own `get` hook.

## Verification

- `PhpLessonContentTest` covers both PHP courses (order, counts, theory examples, no quiz or theory
  giving away a solution); `PhpChallengeHarnessTest` runs all 101 solutions (and checks every starter
  fails at least one check) with the PHP CLI.
- All 101 solutions were also run through `RunnerEndToEndTest` against a local runner, and all 60 new
  ones through the web app's in-browser PHP 8.4.

## Out of scope

New courses beyond this one, and any web or mobile code changes: both apps list courses from the API.
