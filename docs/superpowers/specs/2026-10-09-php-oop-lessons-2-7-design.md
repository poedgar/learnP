# OOP in PHP lessons 2–7: design

Date: 2026-10-09
Status: approved in conversation, awaiting review of this document

## Goal

Complete the *OOP in PHP* course. Today it has one lesson, *Classes and objects*, which covers classes,
properties, methods and `$this`, constructors (including promotion), and public/private visibility.
Add six lessons:

| # | Title | Slug | Topic |
|---|-------|------|-------|
| 2 | Inheritance | `inheritance` | `extends`, overriding, `parent::`, `protected`, `final`, `instanceof` |
| 3 | Interfaces and abstract classes | `interfaces-and-abstract-classes` | contracts, type hints, polymorphism, abstract classes, `Countable`, `Stringable` |
| 4 | Static members, constants and enums | `static-members-constants-and-enums` | class constants, static, `self::`/`static::`, named constructors, enums, `match` |
| 5 | Exceptions | `exceptions` | `try`/`catch`/`finally`, the hierarchy, custom exceptions, `$previous` |
| 6 | Traits and readonly properties | `traits-and-readonly-properties` | traits, `insteadof`, readonly properties and classes, withers, value objects |
| 7 | Putting objects together | `putting-objects-together` | composition, dependency injection, depending on interfaces, fakes, a checkout |

Audience and voice as lesson 1. The running example is the bookshop from the SQL course (`Book`,
`Customer`, `Order`, …). No app, API or runner changes.

Delivered as one spec and two implementation plans: lessons 2–4, then lessons 5–7.

## Constraints

- **PHP 8.4.** The production runner runs PHP 8.4 (runner/README.md); this machine has 8.5. Lessons,
  solutions, starters, checks and quiz code use nothing newer than 8.4 (no pipe operator, no
  `clone` with arguments, no other 8.5 additions).
- The runner forbids file writes, `php://` streams and extensions; no lesson needs them.
- Each lesson only uses what it or earlier lessons teach.
- Lesson 1 is unchanged (content, quiz, challenge IDs and learners' progress keyed by them).

## Shape of each lesson

- Markdown theory in `app/database/content/php-oop/0N-<slug>.md`, about 6–8 `## ` sections with
  runnable ` ```php ` examples (each example runs on its own and prints something).
- A PHP definition file `app/database/content/php-oop/0N-<slug>.php` returning title, summary, starter
  code, 8 quiz questions (4 options, one correct, an explanation; `code` where it helps) and 6
  challenges. Each challenge has a prompt, starter code, a reference `solution`, a hint, and `checks`:
  a list of `['label' => …, 'code' => …]` in lesson 1's format — PHP that returns `true` or a message,
  using the harness helpers lesson 1 uses (`expect_same`, …).
- Checks test behaviour (values returned, exceptions thrown, types and visibility via reflection), not
  the wording of the learner's code, so reasonable alternative solutions pass.
- Every starter must run and fail at least one check.
- `PhpOopCourseSeeder` keeps lesson 1 exactly as it is and loads lessons 2–7 from those files, in order
  (the same pattern as `SqlCourseSeeder::BOOKSHOP_LESSONS`).

## Lesson outlines

### Lesson 2: Inheritance

Sections: `extends` (`EBook` and `AudioBook` are kinds of `Book`); inherited properties and methods;
overriding; `parent::` and `parent::__construct()`; `protected`; `final`; `instanceof`; when not to
inherit.

Challenges: an `EBook` extending `Book`; overriding `describe()`; a child constructor calling the
parent's; a protected price only subclasses can change; a `final` method; a small hierarchy.

### Lesson 3: Interfaces and abstract classes

Sections: interfaces as contracts (`Discountable`); implementing several; type hints that accept any
implementation; polymorphism; abstract classes and methods; interface or abstract class?; built-in
`Countable` and `Stringable`.

Challenges: implement an interface; a function that accepts the interface; an abstract `Product` with a
concrete `Book`; `Countable` on a `Shelf`; `__toString`; a small payment-methods design.

### Lesson 4: Static members, constants and enums

Sections: class constants; static properties and methods, and shared-state gotchas; `self::` vs
`static::`; named constructors (`Book::fromArray()`); pure enums; backed enums, `from()`/`tryFrom()`;
enum methods; `match` with enums.

Challenges: a VAT-rate constant; a static counter of created books; a named constructor; a `Genre` enum;
a backed `OrderStatus` enum with `label()`; `match` over an enum.

### Lesson 5: Exceptions

Sections: why exceptions; `throw` and `try`/`catch`; `finally`; several catch blocks, most specific
first; the built-in hierarchy (`Throwable`, `Exception`, `Error`, `InvalidArgumentException`,
`DomainException`, …); custom exception classes (`OutOfStockException`); exceptions carrying data;
re-throwing with `$previous`; when not to use exceptions.

Challenges: validate a quantity; catch and report; a custom `OutOfStockException`; an exception carrying
the book; `finally` that always runs; wrap a low-level error.

### Lesson 6: Traits and readonly properties

Sections: traits for sharing code without inheritance (`HasTimestamps`); trait methods and properties;
several traits and `insteadof`; readonly properties; readonly classes; immutable "wither" methods
(`withPrice()`); value objects (`Money`, `Isbn`) and comparing them.

Challenges: a `Describable` trait; a trait used by two unrelated classes; a readonly `Isbn`;
`Money::withAmount()` returning a new object; `Money::equals()`; resolving a trait conflict.

### Lesson 7: Putting objects together

Sections: composition over inheritance ("has-a" vs "is-a"); objects holding objects (an `Order` with
`OrderLine`s); dependency injection through the constructor; depending on interfaces; swapping in fakes;
a small project (`Cart`, `Checkout`, `PriceCalculator`, `DiscountPolicy`); course recap.

Challenges: an `Order` with lines and a total; inject a `TaxCalculator`; depend on an interface; a fake
that records calls; a swappable discount policy; a full checkout.

## Correctness checks

- While writing each plan, every solution is run against its checks, every starter against its checks,
  and every theory example and quiz snippet on its own, through the same harness the tests use.
- Existing tests extend automatically: `PhpChallengeHarnessTest` (every solution passes all its checks;
  every starter fails at least one; quiz snippets run on their own).
- New tests, mirroring the SQL ones: the PHP course seeds 7 lessons in order with 8 quiz questions and 6
  challenges each; every quiz `correct_option` points at one of 4 options; every ` ```php ` theory example
  runs without a fatal error; re-seeding keeps lesson 1's IDs; no quiz `code` equals a challenge
  solution.
- PHP 8.4 compatibility: a test (or plan step) that lints every solution, starter and example with the
  8.4 grammar is not available locally, so the plans list the 8.5-only features to avoid and the final
  review checks for them. The skipped `RunnerEndToEndTest` covers this against a real 8.4 runner when
  `RUNNER_E2E_URL` is set.
- `CourseApiTest` gains a check that the PHP course lists 7 lessons.

## Out of scope

Changes to lesson 1, the runner, the API or the apps; PHP 8.4 property hooks and asymmetric visibility
(could be a later lesson); generics-style docblocks, namespaces/autoloading, Composer, testing frameworks.
