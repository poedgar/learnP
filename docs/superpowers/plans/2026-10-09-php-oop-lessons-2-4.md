# OOP in PHP Lessons 2–4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add lessons 2 (*Inheritance*), 3 (*Interfaces and abstract classes*) and 4 (*Static members, constants and enums*) to the *OOP in PHP* course. (Lessons 5–7 come in a second plan.)

**Architecture:** Content only. Each lesson is a Markdown file (theory) plus a PHP definition file (title, summary, starter code, quiz, and challenges with their checks) in `app/database/content/php-oop/`. `PhpOopCourseSeeder` keeps lesson 1 exactly as it is and loads later lessons from those files, like `SqlCourseSeeder::BOOKSHOP_LESSONS`. A new `PhpLessonContentTest` mirrors `SqlLessonContentTest`. No runner, API or app changes.

**Tech Stack:** Laravel 13 / PHP (8.5 locally; the production runner is **PHP 8.4**), PHPUnit feature tests (`php artisan test`, which prints JSON here; pipe through `tail -1`). Challenge checks run in `app/resources/php/check-harness.php`, which provides `expect_same($actual, $expected)` and `expect_throws($class, $callback)`.

**Spec:** `docs/superpowers/specs/2026-10-09-php-oop-lessons-2-7-design.md`

## Global Constraints

- Slugs and positions: `inheritance` (2), `interfaces-and-abstract-classes` (3), `static-members-constants-and-enums` (4); `dataset` is `null`.
- Each new lesson: 6–8 `## ` sections, 8 quiz questions with 4 options each, 6 challenges with checks. (Lesson 1 has 5 challenges and stays unchanged.)
- PHP 8.4 only: no pipe operator `|>`, no `clone($x, [...])`, no `array_first()`/`array_last()`, no `#[\NoDiscard]`, nor anything else new in 8.5.
- Checks test behaviour (values, exceptions, types and visibility through reflection), never the learner's source text (the runner can't be relied on to let code read files).
- Every starter runs and fails at least one check; every solution passes all its checks.
- Theory ` ```php ` blocks are written without `<?php` and run on their own; a block whose first line starts with `// Error:` must stop with a fatal error, every other block must not.
- Quiz `code` is written without `<?php` and runs on its own (`PhpChallengeHarnessTest` prefixes it).
- Each lesson only uses what it or earlier lessons teach. Lesson 1 taught classes, properties, methods and `$this`, constructors with promotion, public/private, and throwing `InvalidArgumentException`.
- Every solution, starter, check, theory example and quiz snippet below was run through the real harness while writing this plan: all solutions pass, all starters fail at least one check, every example behaves as the text says, every quiz snippet prints its marked answer.

## Review Focus

1. **Reasonable alternative solutions must pass.** Checks call the learner's classes and compare results, so a different but correct implementation (another way of rounding, `self` instead of `static`, `UNIQUE`-style alternatives) passes. A reviewer should try a few per lesson. Covered by the design of the checks; spot-checked in the final review.
2. **Nothing newer than PHP 8.4.** Local tests run 8.5, so an 8.5-only feature would pass here and fail for learners. Covered by the constraint above and the final review; `RunnerEndToEndTest` checks it against a real runner when `RUNNER_E2E_URL` is set.
3. **Re-seeding must keep lesson 1's IDs.** Learners' progress is keyed by them. Test in Task 1.
4. **"This fails" examples must actually fail.** Test in Task 1 (covers every later lesson).
5. **A quiz must not hand over a challenge's answer.** Test in Task 1 (covers every later lesson).

---

## File Structure

| File | Responsibility |
|------|----------------|
| `app/database/content/php-oop/02-inheritance.{md,php}` | Lesson 2 |
| `app/database/content/php-oop/03-interfaces-and-abstract-classes.{md,php}` | Lesson 3 |
| `app/database/content/php-oop/04-static-members-constants-and-enums.{md,php}` | Lesson 4 |
| `app/database/seeders/PhpOopCourseSeeder.php` | `LATER_LESSONS` list and a loader for it |
| `app/tests/Feature/PhpLessonContentTest.php` | Lesson order and counts, quiz validity, theory examples, re-seeding, no give-aways |
| `app/tests/Feature/PhpChallengeHarnessTest.php` | Expected number of PHP challenges |
| `app/tests/Feature/CourseApiTest.php` | The PHP course's `lessons_count` |

All commands run from `/Users/macbook/Desktop/dev/learnP/app`.

---

### Task 1: Lesson 2, *Inheritance*, and the lesson-content tests

**Files:**
- Create: `app/database/content/php-oop/02-inheritance.md`, `app/database/content/php-oop/02-inheritance.php`, `app/tests/Feature/PhpLessonContentTest.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: `SyncsLessonContent::syncByPosition()` and `positioned()` (existing); the harness helpers `expect_same()` and `expect_throws()` (existing).
- Produces: the lesson definition file format (as in the SQL course, plus `checks` on each challenge: `list<array{label: string, code: string}>`); `PhpOopCourseSeeder::LATER_LESSONS`; `PhpLessonContentTest::LESSONS`. Tasks 2 and 3 append to both lists.

- [ ] **Step 1: Write the failing tests**

Create `app/tests/Feature/PhpLessonContentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class PhpLessonContentTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    /** The PHP course's lessons, in order. */
    private const LESSONS = [
        'classes-and-objects',
        'inheritance',
    ];

    /**
     * @return Collection<int, Lesson>
     */
    private function lessons(): Collection
    {
        return Course::where('slug', 'php-oop')->firstOrFail()
            ->lessons()->orderBy('position')
            ->withCount(['quizQuestions', 'challenges'])
            ->get();
    }

    public function test_the_course_has_its_lessons_in_order(): void
    {
        $lessons = $this->lessons();

        $this->assertSame(self::LESSONS, $lessons->pluck('slug')->all());

        foreach ($lessons as $lesson) {
            $this->assertNull($lesson->dataset, $lesson->slug);
            $this->assertSame(8, $lesson->quiz_questions_count, "{$lesson->slug} quiz questions");
            // Lesson 1 has five challenges; every later lesson has six.
            $this->assertSame($lesson->position === 1 ? 5 : 6, $lesson->challenges_count, "{$lesson->slug} challenges");
        }
    }

    public function test_every_quiz_question_has_four_options_and_a_real_answer(): void
    {
        foreach ($this->lessons() as $lesson) {
            foreach ($lesson->quizQuestions as $question) {
                $this->assertCount(4, $question->options, "{$lesson->slug}: {$question->question}");
                $this->assertContains($question->correct_option, [0, 1, 2, 3], "{$lesson->slug}: {$question->question}");
            }
        }
    }

    /**
     * Every ```php example runs on its own. One that starts with "// Error:"
     * shows a mistake, so it must stop with a fatal error; no other may.
     */
    public function test_every_theory_example_runs_as_the_lesson_says(): void
    {
        $directory = sys_get_temp_dir().'/learnp-theory-'.uniqid();
        File::ensureDirectoryExists($directory);

        try {
            foreach ($this->lessons() as $lesson) {
                preg_match_all('/```php\n(.*?)```/s', $lesson->content, $matches);
                $this->assertNotEmpty($matches[1], "{$lesson->slug} has no PHP examples");

                foreach ($matches[1] as $code) {
                    File::put($directory.'/example.php', "<?php\n\n".$code);
                    $result = Process::path($directory)->run([PHP_BINARY, '-d', 'display_errors=1', 'example.php']);
                    $failed = ! $result->successful() || preg_match('/Fatal error|Parse error/', $result->output()) === 1;

                    $this->assertSame(
                        str_starts_with($code, '// Error:'),
                        $failed,
                        "{$lesson->slug}: this example ".($failed ? 'fails' : 'runs').":\n{$code}\n{$result->output()}",
                    );
                }
            }
        } finally {
            File::deleteDirectory($directory);
        }
    }

    /**
     * Learners take the quiz before the challenges, so no quiz snippet may be a
     * challenge's solution.
     */
    public function test_no_quiz_question_gives_away_a_challenge_solution(): void
    {
        $normalise = fn (string $code) => preg_replace('/\s+/', ' ', trim(str_replace('<?php', '', $code)));

        foreach ($this->lessons() as $lesson) {
            $solutions = $lesson->challenges->map(fn ($challenge) => $normalise($challenge->solution))->all();

            foreach ($lesson->quizQuestions->whereNotNull('code') as $question) {
                $this->assertNotContains($normalise($question->code), $solutions, "{$lesson->slug}: {$question->question}");
            }
        }
    }

    public function test_reseeding_keeps_lesson_one_ids(): void
    {
        $ids = fn () => Lesson::where('slug', 'classes-and-objects')->firstOrFail()->load('quizQuestions', 'challenges');
        $before = $ids();

        $this->seed(DatabaseSeeder::class);
        $after = $ids();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->quizQuestions->pluck('id')->all(), $after->quizQuestions->pluck('id')->all());
        $this->assertSame($before->challenges->pluck('id')->all(), $after->challenges->pluck('id')->all());
    }
}
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, in `test_every_php_challenge_solution_passes_all_its_checks`, change `$this->assertCount(5, $challenges);` to:

```php
        $this->assertCount(11, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, in `test_courses_are_listed_with_lesson_counts`, add after `->assertJsonPath('data.1.language', 'php')`:

```php
            ->assertJsonPath('data.1.lessons_count', 2)
```

(If `data.1.language` is the last line of the chain, move its `;` to the new line.)

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL. `test_the_course_has_its_lessons_in_order` (only `classes-and-objects`), the harness count (5, not 11), and `data.1.lessons_count` (1, not 2). The other new tests pass on lesson 1 alone; that confirms lesson 1 already meets them.

- [ ] **Step 3: Load later lessons in the seeder**

In `app/database/seeders/PhpOopCourseSeeder.php`, add after `use SyncsLessonContent;`:

```php

    /**
     * Lessons 2 onwards, in order. Each is a Markdown file (the theory) and a
     * PHP file (title, summary, starter code, quiz, and challenges with their
     * checks) in database/content/php-oop, sharing this basename.
     */
    private const LATER_LESSONS = [
        '02-inheritance',
    ];
```

At the end of `run()`, after lesson 1's two `syncByPosition` calls, add:

```php

        foreach (self::LATER_LESSONS as $index => $name) {
            $this->seedLesson($course, $name, $index + 2);
        }
```

Add this method after `run()`:

```php
    private function seedLesson(Course $course, string $name, int $position): void
    {
        $definition = require database_path("content/php-oop/{$name}.php");

        $lesson = $course->lessons()->updateOrCreate(['slug' => $definition['slug']], [
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'content' => file_get_contents(database_path("content/php-oop/{$name}.md")),
            'dataset' => null,
            'starter_code' => $definition['starter_code'],
            'position' => $position,
        ]);

        $this->syncByPosition($lesson->quizQuestions(), $this->positioned($definition['quiz']));
        $this->syncByPosition($lesson->challenges(), $this->positioned($definition['challenges']));
    }
```

Lesson 1's code stays exactly as it is.

- [ ] **Step 4: Write lesson 2's theory**

Create `app/database/content/php-oop/02-inheritance.md`:

````markdown
## Kinds of things: `extends`

An e-book is a book: it has a title, an author and a price, and it can describe itself. It
also has something ordinary books don't, a file size. Rather than copying `Book` and adding
to it, say that `EBook` **extends** `Book`:

```php
class Book
{
    public function __construct(
        public string $title,
        public string $author,
        public float $price,
    ) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class EBook extends Book
{
    public int $fileSizeMb = 0;
}

$ebook = new EBook('Dune', 'Frank Herbert', 6.99);
$ebook->fileSizeMb = 3;

echo $ebook->describe() . "\n";
echo $ebook->fileSizeMb . " MB\n";
```

`EBook` is the **child class** (or subclass), and `Book` is its **parent** (or superclass).
The child **inherits** the parent's properties and methods, including its constructor, so
`new EBook(…)` takes the same arguments as `new Book(…)`. Then it adds its own.

## Overriding a method

A child can replace an inherited method by declaring one with the same name. This is called
**overriding**:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class EBook extends Book
{
    public function describe(): string
    {
        return $this->title . ' by ' . $this->author . ' (e-book)';
    }
}

echo (new Book('Dune', 'Frank Herbert'))->describe() . "\n";
echo (new EBook('Dune', 'Frank Herbert'))->describe() . "\n";
```

Each object uses the version from its own class. The overriding method must be
**compatible** with the one it replaces: it accepts the same arguments and returns the same
kind of value. Otherwise PHP refuses the class:

```php
// Error: describe() in EBook must be compatible with describe() in Book.
class Book
{
    public function describe(): string
    {
        return 'a book';
    }
}

class EBook extends Book
{
    public function describe(int $width): string
    {
        return 'an e-book';
    }
}
```

## Calling the parent's version: `parent::`

Often the child wants to do what the parent does, *plus a little more*. Copying the parent's
code works until the parent changes. Instead, call the parent's version with `parent::`:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function describe(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

class EBook extends Book
{
    public function describe(): string
    {
        return parent::describe() . ' (e-book)';
    }
}

echo (new EBook('Circe', 'Madeline Miller'))->describe();
```

The same goes for constructors. If a child declares its own constructor, the parent's one
**doesn't run** unless the child calls it. An audiobook needs a narrator as well:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}
}

class AudioBook extends Book
{
    public function __construct(string $title, string $author, public string $narrator)
    {
        parent::__construct($title, $author);
    }
}

$audiobook = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
echo $audiobook->title . ', read by ' . $audiobook->narrator;
```

## Protected: for the family only

You've met `public` (anyone can use it) and `private` (only the class itself). Inheritance
adds a third: `protected` means **the class and its children**, but nobody outside.

```php
class Book
{
    public function __construct(public string $title, protected float $price) {}

    public function getPrice(): float
    {
        return $this->price;
    }
}

class SecondHandBook extends Book
{
    public function markDown(): void
    {
        $this->price = $this->price / 2;
    }
}

$book = new SecondHandBook('Dracula', 7.99);
$book->markDown();
echo $book->getPrice();
```

`SecondHandBook` can change `$price` because it's protected. Code outside the classes can't:

```php
// Error: $price is protected, so code outside the class can't read it.
class Book
{
    public function __construct(protected float $price) {}
}

$book = new Book(7.99);
echo $book->price;
```

Had `$price` been `private`, not even `SecondHandBook` could see it. A good rule: start with
`private`, and loosen it to `protected` only when a child class really needs it.

## Final: no further changes

Mark a method `final` and no child may override it. Mark a class `final` and no class may
extend it:

```php
// Error: Isbn is final, so it can't be extended.
final class Isbn
{
    public function __construct(public string $value) {}
}

class FancyIsbn extends Isbn
{
}
```

Use `final` for things that must behave exactly one way, such as how an ID is worked out,
or for classes that weren't designed to be extended.

## Checking the kind: `instanceof`

An `EBook` *is a* `Book`, so it can go anywhere a `Book` is expected, including a parameter
typed `Book`. `instanceof` asks whether an object is of a class or one of its children:

```php
class Book
{
    public function __construct(public string $title) {}
}

class EBook extends Book
{
}

function shelfLabel(Book $book): string
{
    return strtoupper($book->title);
}

$ebook = new EBook('Neuromancer');

echo shelfLabel($ebook) . "\n";
var_dump($ebook instanceof EBook);
var_dump($ebook instanceof Book);
var_dump(new Book('Dune') instanceof EBook);
```

## When not to inherit

Inheritance says "an X **is a** Y". Use it only when that sentence is true. An e-book is a
book, so `EBook extends Book` is right. A customer is *not* a book, even though both have a
name, so `Customer extends Book` would be wrong, however much code it saved.

A class can only extend **one** parent, and every child is tied to every detail of its
parent. When you just want to reuse some behaviour, there are better tools, which the
rest of this course covers: interfaces, traits, and objects that hold other objects.

Next, try the quiz to check your understanding, then solve the challenges.
````

- [ ] **Step 5: Write lesson 2's quiz and challenges**

Create `app/database/content/php-oop/02-inheritance.php`:

```php
<?php

// Lesson 2 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'inheritance',
    'title' => 'Inheritance',
    'summary' => 'Build specialised classes on top of general ones with extends, override and extend their behaviour with parent::, and share data with subclasses using protected.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        class Book
        {
            public function __construct(public string $title, public string $author) {}

            public function describe(): string
            {
                return $this->title . ' by ' . $this->author;
            }
        }

        class EBook extends Book
        {
            public function describe(): string
            {
                return parent::describe() . ' (e-book)';
            }
        }

        echo (new EBook('Dune', 'Frank Herbert'))->describe();
        PHP,

    'quiz' => [
        [
            'question' => 'What does `class EBook extends Book` mean?',
            'options' => [
                '`EBook` is a copy of `Book` that no longer changes when `Book` does',
                '`EBook` is a kind of `Book`: it inherits `Book`\'s properties and methods and can add its own',
                '`Book` inherits everything from `EBook`',
                'Every `Book` object is also an `EBook`',
            ],
            'correct_option' => 1,
            'explanation' => 'The child class inherits from the parent, and can add or override. Every `EBook` is a `Book`, but not every `Book` is an `EBook`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function kind(): string
                    {
                        return 'book';
                    }
                }

                class EBook extends Book
                {
                    public function kind(): string
                    {
                        return 'e-book';
                    }
                }

                $item = new EBook();
                echo $item->kind();
                PHP,
            'options' => ['`book`', '`e-book`', '`booke-book`', 'An error: `kind()` is declared twice'],
            'correct_option' => 1,
            'explanation' => '`EBook` overrides `kind()`, and an object always uses the version from its own class.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function kind(): string
                    {
                        return 'book';
                    }
                }

                class EBook extends Book
                {
                    public function kind(): string
                    {
                        return 'electronic ' . parent::kind();
                    }
                }

                echo (new EBook())->kind();
                PHP,
            'options' => ['`book`', '`electronic`', '`electronic book`', 'Nothing: the method calls itself forever'],
            'correct_option' => 2,
            'explanation' => '`parent::kind()` runs `Book`\'s version, which returns `book`; the override adds `electronic ` in front.',
        ],
        [
            'question' => 'Where can a `protected` property be used?',
            'options' => [
                'Anywhere',
                'Only in the class that declares it',
                'In the class that declares it and in its child classes',
                'Only in child classes',
            ],
            'correct_option' => 2,
            'explanation' => '`protected` sits between `public` and `private`: the family of classes can use it, code outside can\'t.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public string $title) {}
                }

                class EBook extends Book
                {
                    public function __construct(public int $sizeMb) {}
                }

                $ebook = new EBook(3);
                echo $ebook->title;
                PHP,
            'options' => [
                'It prints an empty line',
                'It prints `3`',
                'It prints `Book`',
                'An error: `$title` was never set, because `EBook`\'s constructor doesn\'t call `Book`\'s',
            ],
            'correct_option' => 3,
            'explanation' => 'A child constructor replaces the parent\'s. Call `parent::__construct(…)` to run it too; here nothing ever sets `$title`.',
        ],
        [
            'question' => 'What does `final class Isbn` prevent?',
            'options' => [
                'Any class from extending `Isbn`',
                'Creating more than one `Isbn` object',
                'Changing an `Isbn`\'s properties',
                'Calling `Isbn`\'s methods from outside',
            ],
            'correct_option' => 0,
            'explanation' => 'A `final` class can\'t have children. (`final` on a method stops that one method from being overridden.)',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                }

                class EBook extends Book
                {
                }

                var_dump(new EBook() instanceof Book);
                var_dump(new Book() instanceof EBook);
                PHP,
            'options' => [
                '`bool(true)` then `bool(true)`',
                '`bool(false)` then `bool(true)`',
                '`bool(true)` then `bool(false)`',
                '`bool(false)` then `bool(false)`',
            ],
            'correct_option' => 2,
            'explanation' => 'An e-book is a book, so the first is true. A plain book isn\'t an e-book, so the second is false.',
        ],
        [
            'question' => 'How many classes can a PHP class extend directly?',
            'options' => ['None', 'One', 'Two', 'As many as you like'],
            'correct_option' => 1,
            'explanation' => 'PHP classes have exactly one parent at most. Interfaces and traits, later in this course, cover the cases where one isn\'t enough.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'An e-book is a book',
            'prompt' => 'Declare a class `EBook` that extends `Book`, with a public `int` property `$fileSizeMb` that starts at `0`. An `EBook` should be created just like a `Book`: `new EBook(\'Dune\', \'Frank Herbert\')`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                // Declare EBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    public int $fileSizeMb = 0;
                }
                PHP,
            'hint' => 'Write `class EBook extends Book`, and declare the property inside it: `public int $fileSizeMb = 0;`. The constructor is inherited.',
            'checks' => [
                ['label' => 'A class named EBook exists', 'code' => "return class_exists('EBook', false) ?: 'There is no class named EBook.';"],
                ['label' => 'EBook extends Book', 'code' => "return is_subclass_of('EBook', 'Book') ?: 'EBook should extend Book.';"],
                ['label' => 'An EBook is created like a Book and can describe itself', 'code' => "return expect_same((new EBook('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert');"],
                ['label' => 'EBook has a public int $fileSizeMb that starts at 0', 'code' => <<<'PHP'
                    if (! property_exists('EBook', 'fileSizeMb')) return 'EBook has no $fileSizeMb property.';
                    $p = new ReflectionProperty('EBook', 'fileSizeMb');
                    if (! $p->isPublic()) return '$fileSizeMb should be public.';
                    if ((string) $p->getType() !== 'int') return '$fileSizeMb should be an int.';
                    return expect_same((new EBook('Dune', 'Frank Herbert'))->fileSizeMb, 0);
                    PHP],
            ],
        ],
        [
            'title' => 'Say it\'s an e-book',
            'prompt' => 'Override `describe()` in `EBook` so it adds ` (e-book)` to what `Book` returns: `Dune by Frank Herbert (e-book)`. Plain books should still describe themselves as before.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    // Override describe() here.
                }

                echo (new EBook('Dune', 'Frank Herbert'))->describe();
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class EBook extends Book
                {
                    public function describe(): string
                    {
                        return parent::describe() . ' (e-book)';
                    }
                }
                PHP,
            'hint' => 'Declare `public function describe(): string` in `EBook`, and return `parent::describe() . \' (e-book)\'`.',
            'checks' => [
                ['label' => 'EBook has its own describe()', 'code' => "return (new ReflectionMethod('EBook', 'describe'))->getDeclaringClass()->getName() === 'EBook' ?: 'EBook should declare its own describe().';"],
                ['label' => "An e-book describes itself as 'Dune by Frank Herbert (e-book)'", 'code' => "return expect_same((new EBook('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert (e-book)');"],
                ['label' => 'It works for any e-book', 'code' => "return expect_same((new EBook('Circe', 'Madeline Miller'))->describe(), 'Circe by Madeline Miller (e-book)');"],
                ['label' => 'Plain books are unchanged', 'code' => "return expect_same((new Book('Dune', 'Frank Herbert'))->describe(), 'Dune by Frank Herbert');"],
            ],
        ],
        [
            'title' => 'Read by a narrator',
            'prompt' => 'Declare `AudioBook`, which extends `Book` and adds a public string `$narrator`. Its constructor takes the title, the author and the narrator, in that order, and must still set the title and author through `Book`\'s constructor. Override `describe()` to give `Dune by Frank Herbert, read by Scott Brick`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class AudioBook extends Book
                {
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public function describe(): string
                    {
                        return $this->title . ' by ' . $this->author;
                    }
                }

                class AudioBook extends Book
                {
                    public function __construct(string $title, string $author, public string $narrator)
                    {
                        parent::__construct($title, $author);
                    }

                    public function describe(): string
                    {
                        return parent::describe() . ', read by ' . $this->narrator;
                    }
                }
                PHP,
            'hint' => 'Give `AudioBook` a constructor `(string $title, string $author, public string $narrator)` that calls `parent::__construct($title, $author)`.',
            'checks' => [
                ['label' => 'AudioBook extends Book', 'code' => "return is_subclass_of('AudioBook', 'Book') ?: 'AudioBook should extend Book.';"],
                ['label' => 'The constructor sets the title, author and narrator', 'code' => <<<'PHP'
                    $book = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
                    return expect_same([$book->title, $book->author, $book->narrator], ['Dune', 'Frank Herbert', 'Scott Brick']);
                    PHP],
                ['label' => "describe() gives 'Dune by Frank Herbert, read by Scott Brick'", 'code' => "return expect_same((new AudioBook('Dune', 'Frank Herbert', 'Scott Brick'))->describe(), 'Dune by Frank Herbert, read by Scott Brick');"],
                ['label' => 'Each audiobook keeps its own narrator', 'code' => <<<'PHP'
                    $a = new AudioBook('Dune', 'Frank Herbert', 'Scott Brick');
                    $b = new AudioBook('Circe', 'Madeline Miller', 'Perdita Weeks');
                    return expect_same($b->describe(), 'Circe by Madeline Miller, read by Perdita Weeks');
                    PHP],
            ],
        ],
        [
            'title' => 'Only the family can change the price',
            'prompt' => "Change `Book` so its `\$price` is **protected** instead of public; `getPrice()` still reads it. Then declare `SaleBook`, which extends `Book` and has `applyDiscount(float \$percent): void`, lowering the price by that percentage.\n\nFor example, a `SaleBook` costing 20.0 costs 15.0 after `applyDiscount(25)`.",
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public float $price) {}

                    public function getPrice(): float
                    {
                        return $this->price;
                    }
                }

                // Declare SaleBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, protected float $price) {}

                    public function getPrice(): float
                    {
                        return $this->price;
                    }
                }

                class SaleBook extends Book
                {
                    public function applyDiscount(float $percent): void
                    {
                        $this->price = $this->price * (100 - $percent) / 100;
                    }
                }
                PHP,
            'hint' => 'Change `public float $price` to `protected float $price` in the constructor. Inside `SaleBook`, `$this->price` is still available.',
            'checks' => [
                ['label' => 'Book\'s $price is protected', 'code' => "return (new ReflectionProperty('Book', 'price'))->isProtected() ?: '\$price should be protected.';"],
                ['label' => 'getPrice() still works', 'code' => "return expect_same((new Book('Dune', 19.99))->getPrice(), 19.99);"],
                ['label' => 'SaleBook extends Book', 'code' => "return is_subclass_of('SaleBook', 'Book') ?: 'SaleBook should extend Book.';"],
                ['label' => 'applyDiscount(25) takes 20.0 down to 15.0', 'code' => <<<'PHP'
                    $book = new SaleBook('Dune', 20.0);
                    $book->applyDiscount(25);
                    return expect_same(round($book->getPrice(), 2), 15.0);
                    PHP],
                ['label' => 'Code outside the classes can\'t read the price directly', 'code' => "return expect_throws(Error::class, fn () => (new SaleBook('Dune', 20.0))->price);"],
            ],
        ],
        [
            'title' => 'Lock the shelf code',
            'prompt' => 'Every book has a shelf code worked out by `shelfCode()`: the first three letters of the author\'s surname in capitals, then a dash and the year, like `HER-1965`. Make `shelfCode()` **final**, so no subclass can change how it works.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $author, public int $year) {}

                    public function shelfCode(): string
                    {
                        $surname = substr($this->author, strrpos($this->author, ' ') + 1);

                        return strtoupper(substr($surname, 0, 3)) . '-' . $this->year;
                    }
                }

                echo (new Book('Frank Herbert', 1965))->shelfCode();
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $author, public int $year) {}

                    final public function shelfCode(): string
                    {
                        $surname = substr($this->author, strrpos($this->author, ' ') + 1);

                        return strtoupper(substr($surname, 0, 3)) . '-' . $this->year;
                    }
                }
                PHP,
            'hint' => 'Put `final` in front of the method: `final public function shelfCode(): string`.',
            'checks' => [
                ['label' => 'shelfCode() is final', 'code' => "return (new ReflectionMethod('Book', 'shelfCode'))->isFinal() ?: 'shelfCode() should be final.';"],
                ['label' => "shelfCode() gives 'HER-1965' for Frank Herbert, 1965", 'code' => "return expect_same((new Book('Frank Herbert', 1965))->shelfCode(), 'HER-1965');"],
                ['label' => 'Book itself can still be extended', 'code' => "return ! (new ReflectionClass('Book'))->isFinal() ?: 'Only the method should be final, not the whole class.';"],
            ],
        ],
        [
            'title' => 'A small family of products',
            'prompt' => "The shop sells more than books. Build this family:\n\n- `Product`: constructor `(string \$name, float \$price)`; a public `\$name`, a **protected** `\$price`, and `priceWithVat(): float`, which adds 20% VAT, rounded to 2 places.\n- `Book` extends `Product`. Books pay no VAT, so its `priceWithVat()` returns the price unchanged.\n- `EBook` extends `Book`, so it pays no VAT either.\n\nA `Product` costing 10.0 gives 12.0; a `Book` or an `EBook` costing 10.0 gives 10.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public string $name, protected float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }

                // Declare Book and EBook here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public string $name, protected float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }

                class Book extends Product
                {
                    public function priceWithVat(): float
                    {
                        return $this->price;
                    }
                }

                class EBook extends Book
                {
                }
                PHP,
            'hint' => '`Book` overrides `priceWithVat()` to return `$this->price`. `EBook` only needs `extends Book`: it inherits that version.',
            'checks' => [
                ['label' => 'A Product costing 10.0 costs 12.0 with VAT', 'code' => "return expect_same((new Product('Mug', 10.0))->priceWithVat(), 12.0);"],
                ['label' => 'Product\'s $price is protected', 'code' => "return (new ReflectionProperty('Product', 'price'))->isProtected() ?: '\$price should be protected.';"],
                ['label' => 'Book extends Product and pays no VAT', 'code' => <<<'PHP'
                    if (! is_subclass_of('Book', 'Product')) return 'Book should extend Product.';
                    return expect_same((new Book('Dune', 10.0))->priceWithVat(), 10.0);
                    PHP],
                ['label' => 'EBook extends Book and pays no VAT', 'code' => <<<'PHP'
                    if (! is_subclass_of('EBook', 'Book')) return 'EBook should extend Book.';
                    return expect_same((new EBook('Dune', 6.99))->priceWithVat(), 6.99);
                    PHP],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if not, run `vendor/bin/pint database tests` and re-run the tests).

- [ ] **Step 7: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/02-inheritance.md app/database/content/php-oop/02-inheritance.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 2: Inheritance"
```

---

### Task 2: Lesson 3, *Interfaces and abstract classes*

**Files:**
- Create: `app/database/content/php-oop/03-interfaces-and-abstract-classes.md`, `app/database/content/php-oop/03-interfaces-and-abstract-classes.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpLessonContentTest.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: the lesson definition file format, `LATER_LESSONS` and `LESSONS` (Task 1).
- Produces: lesson `interfaces-and-abstract-classes` at position 3.

- [ ] **Step 1: Update the tests to expect lesson 3**

In `app/tests/Feature/PhpLessonContentTest.php`, add to the end of `LESSONS`:

```php
        'interfaces-and-abstract-classes',
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, change `$this->assertCount(11, $challenges);` to:

```php
        $this->assertCount(17, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, change `->assertJsonPath('data.1.lessons_count', 2)` to:

```php
            ->assertJsonPath('data.1.lessons_count', 3)
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL on the lesson list, the harness count and `data.1.lessons_count`.

- [ ] **Step 3: Register the lesson**

In `app/database/seeders/PhpOopCourseSeeder.php`, add to the end of `LATER_LESSONS`:

```php
        '03-interfaces-and-abstract-classes',
```

- [ ] **Step 4: Write lesson 3's theory**

Create `app/database/content/php-oop/03-interfaces-and-abstract-classes.md`:

````markdown
## Interfaces: a promise

Inheritance says what a class **is**. An **interface** says what a class **can do**. It lists
public methods, with their parameters and return types but without their code. A class that
`implements` the interface promises to provide every one of them:

```php
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

class Book implements Discountable
{
    public function __construct(public string $title, public float $price) {}

    public function discountedPrice(float $percent): float
    {
        return round($this->price * (100 - $percent) / 100, 2);
    }
}

echo (new Book('Dune', 19.99))->discountedPrice(10);
```

If the class breaks its promise, PHP refuses it before any of it runs:

```php
// Error: Book promises to be Discountable but has no discountedPrice().
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

class Book implements Discountable
{
    public function __construct(public float $price) {}
}
```

## Several promises at once

A class has only one parent, but it can implement **as many interfaces as you like**,
separated by commas:

```php
interface Discountable
{
    public function discountedPrice(float $percent): float;
}

interface Describable
{
    public function describe(): string;
}

class Book implements Discountable, Describable
{
    public function __construct(public string $title, public float $price) {}

    public function discountedPrice(float $percent): float
    {
        return round($this->price * (100 - $percent) / 100, 2);
    }

    public function describe(): string
    {
        return $this->title . ', ' . $this->price;
    }
}

$book = new Book('Circe', 13.99);
echo $book->describe() . "\n";
echo $book->discountedPrice(50);
```

## Code that accepts any implementation

The real power is in type hints. A parameter typed with an interface accepts **any** object
whose class implements it, even classes that have nothing else in common:

```php
interface Describable
{
    public function describe(): string;
}

class Book implements Describable
{
    public function __construct(public string $title) {}

    public function describe(): string
    {
        return 'Book: ' . $this->title;
    }
}

class GiftCard implements Describable
{
    public function __construct(public float $amount) {}

    public function describe(): string
    {
        return 'Gift card worth ' . $this->amount;
    }
}

function receiptLine(Describable $item): string
{
    return '- ' . $item->describe();
}

echo receiptLine(new Book('Dune')) . "\n";
echo receiptLine(new GiftCard(25)) . "\n";
```

`receiptLine()` doesn't know or care what the item really is. It only relies on the promise.

## Polymorphism

Put different objects that keep the same promise in one list, and the same call does the
right thing for each. This is called **polymorphism**, "many shapes":

```php
interface Shippable
{
    public function weightKg(): float;
}

class Book implements Shippable
{
    public function __construct(public int $pages) {}

    public function weightKg(): float
    {
        return $this->pages / 1000;
    }
}

class Mug implements Shippable
{
    public function weightKg(): float
    {
        return 0.35;
    }
}

$parcel = [new Book(612), new Book(304), new Mug()];

$total = 0;
foreach ($parcel as $item) {
    $total += $item->weightKg();
}

echo 'Parcel weighs ' . $total . ' kg';
```

Adding a new kind of shippable thing later needs no change to the loop.

## Abstract classes

Sometimes the members of a family share most of their code and differ in one or two details.
An **abstract class** is a parent that can't be created on its own and can leave some methods
**abstract**, with no body, for each child to fill in:

```php
abstract class Product
{
    public function __construct(public string $name, protected float $price) {}

    abstract public function vatRate(): float;

    public function priceWithVat(): float
    {
        return round($this->price * (1 + $this->vatRate()), 2);
    }
}

class Book extends Product
{
    public function vatRate(): float
    {
        return 0.0;
    }
}

class Mug extends Product
{
    public function vatRate(): float
    {
        return 0.2;
    }
}

echo (new Book('Dune', 10))->priceWithVat() . "\n";
echo (new Mug('Logo mug', 10))->priceWithVat() . "\n";
```

`priceWithVat()` is written once, in `Product`, and calls `vatRate()`, which each child
provides. A plain `Product` makes no sense on its own, and PHP won't create one:

```php
// Error: Product is abstract, so it can't be created with new.
abstract class Product
{
    abstract public function vatRate(): float;
}

$product = new Product();
```

A child that doesn't fill in every abstract method must be abstract itself.

## Interface or abstract class?

- Use an **interface** to describe something a class *can do*, especially when unrelated
  classes should be able to do it (books, mugs and gift cards can all be `Describable`).
- Use an **abstract class** when closely related classes share real code and data, and only
  some details differ.

The two work well together: an abstract class can implement an interface, leaving some of its
methods for the children.

## Built-in interfaces: Countable and Stringable

PHP comes with interfaces of its own. Implement `Countable`, with its `count(): int` method,
and PHP's own `count()` function works on your objects:

```php
class Order implements Countable
{
    private array $lines = [];

    public function add(string $title): void
    {
        $this->lines[] = $title;
    }

    public function count(): int
    {
        return count($this->lines);
    }
}

$order = new Order();
$order->add('Dune');
$order->add('Circe');

echo count($order);
```

Give a class a `__toString(): string` method and its objects can be used as text, in `echo`
or joined with `.`. PHP then counts the class as implementing `Stringable` automatically:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public function __toString(): string
    {
        return $this->title . ' by ' . $this->author;
    }
}

$book = new Book('Dune', 'Frank Herbert');

echo 'You ordered ' . $book . "\n";
var_dump($book instanceof Stringable);
```

Next, try the quiz to check your understanding, then solve the challenges.
````

- [ ] **Step 5: Write lesson 3's quiz and challenges**

Create `app/database/content/php-oop/03-interfaces-and-abstract-classes.php`:

```php
<?php

// Lesson 3 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'interfaces-and-abstract-classes',
    'title' => 'Interfaces and abstract classes',
    'summary' => 'Describe what classes can do with interfaces, write code that works with any class that keeps the promise, and share code in a family with abstract classes.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        interface Describable
        {
            public function describe(): string;
        }

        class Book implements Describable
        {
            public function __construct(public string $title) {}

            public function describe(): string
            {
                return 'Book: ' . $this->title;
            }
        }

        function receiptLine(Describable $item): string
        {
            return '- ' . $item->describe();
        }

        echo receiptLine(new Book('Dune'));
        PHP,

    'quiz' => [
        [
            'question' => 'What does an interface contain?',
            'options' => [
                'The public methods a class promises to have, without their code',
                'Complete methods that classes inherit',
                'The properties every object must store',
                'A list of the classes that use it',
            ],
            'correct_option' => 0,
            'explanation' => 'An interface lists method signatures only. Each class that implements it writes the code.',
        ],
        [
            'question' => 'How many interfaces can one class implement?',
            'options' => ['None', 'One', 'Two', 'As many as you like'],
            'correct_option' => 3,
            'explanation' => 'A class has at most one parent, but can implement any number of interfaces, separated by commas.',
        ],
        [
            'question' => 'What happens when this code runs?',
            'code' => <<<'PHP'
                interface Describable
                {
                    public function describe(): string;
                }

                class Mug implements Describable
                {
                }

                echo 'Ready';
                PHP,
            'options' => [
                'It prints `Ready`',
                'It prints `Ready`, and fails later when `describe()` is called',
                'An error before anything is printed: `Mug` doesn\'t provide `describe()`',
                'It prints nothing',
            ],
            'correct_option' => 2,
            'explanation' => 'PHP checks the promise when the class is declared, so the script stops before `echo` runs.',
        ],
        [
            'question' => 'A function is declared as `function receiptLine(Describable $item)`. What can you pass to it?',
            'options' => [
                'Only objects of a class called `Describable`',
                'Any object whose class implements `Describable`',
                'Any object at all',
                'Only strings',
            ],
            'correct_option' => 1,
            'explanation' => 'An interface type accepts every object that keeps the promise, whatever its class.',
        ],
        [
            'question' => 'Can you create an object from an abstract class with `new`?',
            'options' => [
                'Yes, like any other class',
                'No; only from a non-abstract class that extends it',
                'Only if it has no abstract methods left',
                'Only from inside the class itself',
            ],
            'correct_option' => 1,
            'explanation' => 'Abstract classes exist to be extended. `new` on one is an error, even if it happens to have no abstract methods.',
        ],
        [
            'question' => 'A class extends an abstract class but doesn\'t implement one of its abstract methods. What then?',
            'options' => [
                'The method silently returns `null`',
                'PHP uses the parent\'s empty version',
                'The class must be declared abstract too, or PHP refuses it',
                'The method is skipped when called',
            ],
            'correct_option' => 2,
            'explanation' => 'Every abstract method needs a body somewhere down the family before objects can be created.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Order implements Countable
                {
                    private array $lines = ['Dune', 'Circe', 'Beloved'];

                    public function count(): int
                    {
                        return count($this->lines);
                    }
                }

                echo count(new Order());
                PHP,
            'options' => ['`0`', '`1`', '`3`', 'An error: `count()` only works on arrays'],
            'correct_option' => 2,
            'explanation' => 'Because `Order` implements `Countable`, PHP\'s `count()` calls its `count()` method.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public function __construct(public string $title) {}

                    public function __toString(): string
                    {
                        return '"' . $this->title . '"';
                    }
                }

                echo 'Now reading ' . new Book('Circe');
                PHP,
            'options' => [
                '`Now reading Book`',
                '`Now reading "Circe"`',
                '`Now reading Circe`',
                'An error: an object can\'t be joined with text',
            ],
            'correct_option' => 1,
            'explanation' => 'When an object is used as text, PHP calls its `__toString()` method.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Keep the promise',
            'prompt' => 'Make `Mug` implement the `Describable` interface. A mug\'s `describe()` returns `Mug with ` followed by its design, like `Mug with the bookshop logo`.',
            'starter_code' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Mug
                {
                    public function __construct(public string $design) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Mug implements Describable
                {
                    public function __construct(public string $design) {}

                    public function describe(): string
                    {
                        return 'Mug with ' . $this->design;
                    }
                }
                PHP,
            'hint' => 'Add `implements Describable` after `class Mug`, then write `public function describe(): string`.',
            'checks' => [
                ['label' => 'Mug implements Describable', 'code' => "return in_array('Describable', class_implements('Mug')) ?: 'Mug should implement Describable.';"],
                ['label' => "describe() gives 'Mug with the bookshop logo'", 'code' => "return expect_same((new Mug('the bookshop logo'))->describe(), 'Mug with the bookshop logo');"],
                ['label' => 'Each mug describes its own design', 'code' => "return expect_same((new Mug('a cat'))->describe(), 'Mug with a cat');"],
            ],
        ],
        [
            'title' => 'Anything describable',
            'prompt' => 'Write a function `label(Describable $item): string` that returns `* ` followed by the item\'s description, like `* Book: Dune`. It must accept **any** `Describable` object, not just books.',
            'starter_code' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Book implements Describable
                {
                    public function __construct(public string $title) {}

                    public function describe(): string
                    {
                        return 'Book: ' . $this->title;
                    }
                }

                // Write label() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface Describable
                {
                    public function describe(): string;
                }

                class Book implements Describable
                {
                    public function __construct(public string $title) {}

                    public function describe(): string
                    {
                        return 'Book: ' . $this->title;
                    }
                }

                function label(Describable $item): string
                {
                    return '* ' . $item->describe();
                }
                PHP,
            'hint' => 'Type the parameter with the interface: `function label(Describable $item): string`.',
            'checks' => [
                ['label' => 'A function label() exists', 'code' => "return function_exists('label') ?: 'There is no function called label().';"],
                ['label' => 'Its parameter is typed Describable', 'code' => "return expect_same((string) (new ReflectionFunction('label'))->getParameters()[0]->getType(), 'Describable');"],
                ['label' => "label() gives '* Book: Dune' for Dune", 'code' => "return expect_same(label(new Book('Dune')), '* Book: Dune');"],
                ['label' => 'It works for a Describable that isn\'t a book', 'code' => <<<'PHP'
                    $card = new class implements Describable {
                        public function describe(): string
                        {
                            return 'Gift card';
                        }
                    };
                    return expect_same(label($card), '* Gift card');
                    PHP],
            ],
        ],
        [
            'title' => 'Membership levels',
            'prompt' => "Write an **abstract** class `Membership` with:\n\n- an abstract method `discountPercent(): int`;\n- `price(float \$listPrice): float`, which takes the discount off the list price and rounds to 2 places.\n\nThen write `StandardMembership` (0% off) and `GoldMembership` (10% off). With a list price of 20.0, Standard gives 20.0 and Gold gives 18.0.",
            'starter_code' => <<<'PHP'
                <?php

                class Membership
                {
                    public function price(float $listPrice): float
                    {
                        return $listPrice;
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Membership
                {
                    abstract public function discountPercent(): int;

                    public function price(float $listPrice): float
                    {
                        return round($listPrice * (100 - $this->discountPercent()) / 100, 2);
                    }
                }

                class StandardMembership extends Membership
                {
                    public function discountPercent(): int
                    {
                        return 0;
                    }
                }

                class GoldMembership extends Membership
                {
                    public function discountPercent(): int
                    {
                        return 10;
                    }
                }
                PHP,
            'hint' => 'Declare `abstract class Membership` with `abstract public function discountPercent(): int;`, and call `$this->discountPercent()` inside `price()`.',
            'checks' => [
                ['label' => 'Membership is abstract', 'code' => "return (new ReflectionClass('Membership'))->isAbstract() ?: 'Membership should be abstract.';"],
                ['label' => 'discountPercent() is abstract in Membership', 'code' => <<<'PHP'
                    if (! method_exists('Membership', 'discountPercent')) return 'Membership has no discountPercent() method.';
                    return (new ReflectionMethod('Membership', 'discountPercent'))->isAbstract() ?: 'discountPercent() should be abstract in Membership.';
                    PHP],
                ['label' => 'StandardMembership gives 20.0 for 20.0', 'code' => "return expect_same((new StandardMembership())->price(20.0), 20.0);"],
                ['label' => 'GoldMembership gives 18.0 for 20.0', 'code' => "return expect_same((new GoldMembership())->price(20.0), 18.0);"],
                ['label' => 'GoldMembership rounds to 2 places', 'code' => "return expect_same((new GoldMembership())->price(13.99), 12.59);"],
            ],
        ],
        [
            'title' => 'Count the shelf',
            'prompt' => 'Make `Shelf` implement `Countable`, so that `count($shelf)` gives the number of books added to it with `add()`.',
            'starter_code' => <<<'PHP'
                <?php

                class Shelf
                {
                    private array $books = [];

                    public function add(string $title): void
                    {
                        $this->books[] = $title;
                    }
                }

                $shelf = new Shelf();
                $shelf->add('Dune');
                echo count($shelf);
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Shelf implements Countable
                {
                    private array $books = [];

                    public function add(string $title): void
                    {
                        $this->books[] = $title;
                    }

                    public function count(): int
                    {
                        return count($this->books);
                    }
                }
                PHP,
            'hint' => 'Add `implements Countable`, and a `public function count(): int` that returns `count($this->books)`.',
            'checks' => [
                ['label' => 'Shelf implements Countable', 'code' => "return (new Shelf()) instanceof Countable ?: 'Shelf should implement Countable.';"],
                ['label' => 'An empty shelf counts 0', 'code' => "return expect_same(count(new Shelf()), 0);"],
                ['label' => 'A shelf with three books counts 3', 'code' => <<<'PHP'
                    $shelf = new Shelf();
                    $shelf->add('Dune');
                    $shelf->add('Circe');
                    $shelf->add('Beloved');
                    return expect_same(count($shelf), 3);
                    PHP],
            ],
        ],
        [
            'title' => 'A book as text',
            'prompt' => 'Give `Book` a `__toString()` method, so that a book used as text reads like `Dune (Frank Herbert, 1965)`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public int $year,
                    ) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(
                        public string $title,
                        public string $author,
                        public int $year,
                    ) {}

                    public function __toString(): string
                    {
                        return $this->title . ' (' . $this->author . ', ' . $this->year . ')';
                    }
                }
                PHP,
            'hint' => 'Add `public function __toString(): string` and join the title, author and year with `.`.',
            'checks' => [
                ['label' => 'Book is Stringable', 'code' => "return (new Book('Dune', 'Frank Herbert', 1965)) instanceof Stringable ?: 'Book needs a __toString() method.';"],
                ['label' => "A book reads as 'Dune (Frank Herbert, 1965)'", 'code' => "return expect_same((string) new Book('Dune', 'Frank Herbert', 1965), 'Dune (Frank Herbert, 1965)');"],
                ['label' => 'It works for any book', 'code' => "return expect_same('Read ' . new Book('Circe', 'Madeline Miller', 2018), 'Read Circe (Madeline Miller, 2018)');"],
            ],
        ],
        [
            'title' => 'Ways to pay',
            'prompt' => "Design payment methods:\n\n- An interface `PaymentMethod` with `pay(float \$amount): string`.\n- `CardPayment`, created with the card's last four digits, whose `pay(12.5)` returns `Paid 12.50 by card ending 4242`.\n- `GiftCardPayment`, created with a balance. `pay()` takes the amount off the balance and returns `Paid 12.50 by gift card, 7.50 left` (for a balance of 20.0). If the balance is too small, it throws an `InvalidArgumentException` and leaves the balance alone.\n- A function `checkout(PaymentMethod \$method, float \$amount): string` that pays and returns the message.\n\nShow amounts with 2 decimal places, as `number_format(\$amount, 2)` does.",
            'starter_code' => <<<'PHP'
                <?php

                // Declare PaymentMethod, CardPayment, GiftCardPayment and checkout() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface PaymentMethod
                {
                    public function pay(float $amount): string;
                }

                class CardPayment implements PaymentMethod
                {
                    public function __construct(private string $lastFour) {}

                    public function pay(float $amount): string
                    {
                        return 'Paid ' . number_format($amount, 2) . ' by card ending ' . $this->lastFour;
                    }
                }

                class GiftCardPayment implements PaymentMethod
                {
                    public function __construct(private float $balance) {}

                    public function pay(float $amount): string
                    {
                        if ($amount > $this->balance) {
                            throw new InvalidArgumentException('Not enough left on the gift card.');
                        }

                        $this->balance -= $amount;

                        return 'Paid ' . number_format($amount, 2) . ' by gift card, ' . number_format($this->balance, 2) . ' left';
                    }
                }

                function checkout(PaymentMethod $method, float $amount): string
                {
                    return $method->pay($amount);
                }
                PHP,
            'hint' => 'Both payment classes implement `PaymentMethod`. `checkout()` is typed with the interface, so it works with either.',
            'checks' => [
                ['label' => 'PaymentMethod is an interface with pay()', 'code' => <<<'PHP'
                    if (! interface_exists('PaymentMethod')) return 'There is no interface called PaymentMethod.';
                    return method_exists('PaymentMethod', 'pay') ?: 'PaymentMethod should declare pay().';
                    PHP],
                ['label' => "A card pays 'Paid 12.50 by card ending 4242'", 'code' => <<<'PHP'
                    if (! in_array('PaymentMethod', class_implements('CardPayment'))) return 'CardPayment should implement PaymentMethod.';
                    return expect_same((new CardPayment('4242'))->pay(12.5), 'Paid 12.50 by card ending 4242');
                    PHP],
                ['label' => 'A gift card pays and keeps track of its balance', 'code' => <<<'PHP'
                    if (! in_array('PaymentMethod', class_implements('GiftCardPayment'))) return 'GiftCardPayment should implement PaymentMethod.';
                    $card = new GiftCardPayment(20.0);
                    $first = $card->pay(12.5);
                    if ($first !== 'Paid 12.50 by gift card, 7.50 left') return expect_same($first, 'Paid 12.50 by gift card, 7.50 left');
                    return expect_same($card->pay(7.5), 'Paid 7.50 by gift card, 0.00 left');
                    PHP],
                ['label' => 'A gift card refuses to pay more than its balance', 'code' => <<<'PHP'
                    $card = new GiftCardPayment(5.0);
                    $refused = expect_throws(InvalidArgumentException::class, fn () => $card->pay(12.5));
                    if ($refused !== true) return $refused;
                    return expect_same($card->pay(5.0), 'Paid 5.00 by gift card, 0.00 left');
                    PHP],
                ['label' => 'checkout() works with any payment method', 'code' => <<<'PHP'
                    if (! function_exists('checkout')) return 'There is no function called checkout().';
                    $type = (string) (new ReflectionFunction('checkout'))->getParameters()[0]->getType();
                    if ($type !== 'PaymentMethod') return 'checkout()\'s first parameter should be typed PaymentMethod.';
                    return expect_same(checkout(new CardPayment('1234'), 3), 'Paid 3.00 by card ending 1234');
                    PHP],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if not, run `vendor/bin/pint database tests` and re-run the tests).

- [ ] **Step 7: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/03-interfaces-and-abstract-classes.md app/database/content/php-oop/03-interfaces-and-abstract-classes.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 3: Interfaces and abstract classes"
```

---

### Task 3: Lesson 4, *Static members, constants and enums*, and final checks

**Files:**
- Create: `app/database/content/php-oop/04-static-members-constants-and-enums.md`, `app/database/content/php-oop/04-static-members-constants-and-enums.php`
- Modify: `app/database/seeders/PhpOopCourseSeeder.php`, `app/tests/Feature/PhpLessonContentTest.php`, `app/tests/Feature/PhpChallengeHarnessTest.php`, `app/tests/Feature/CourseApiTest.php`

**Interfaces:**
- Consumes: the lesson definition file format, `LATER_LESSONS` and `LESSONS` (Task 1).
- Produces: lesson `static-members-constants-and-enums` at position 4.

- [ ] **Step 1: Update the tests to expect lesson 4**

In `app/tests/Feature/PhpLessonContentTest.php`, add to the end of `LESSONS`:

```php
        'static-members-constants-and-enums',
```

In `app/tests/Feature/PhpChallengeHarnessTest.php`, change `$this->assertCount(17, $challenges);` to:

```php
        $this->assertCount(23, $challenges);
```

In `app/tests/Feature/CourseApiTest.php`, change `->assertJsonPath('data.1.lessons_count', 3)` to:

```php
            ->assertJsonPath('data.1.lessons_count', 4)
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: FAIL on the lesson list, the harness count and `data.1.lessons_count`.

- [ ] **Step 3: Register the lesson**

In `app/database/seeders/PhpOopCourseSeeder.php`, add to the end of `LATER_LESSONS`:

```php
        '04-static-members-constants-and-enums',
```

- [ ] **Step 4: Write lesson 4's theory**

Create `app/database/content/php-oop/04-static-members-constants-and-enums.md`:

````markdown
## Class constants

Some values belong to a class and never change, like the VAT rate on books. Declare them as
**class constants** with `const`. Read one with the class name and `::`, or with `self::`
inside the class:

```php
class Book
{
    public const VAT_RATE = 0.0;
    public const MAX_TITLE_LENGTH = 120;

    public function __construct(public string $title, public float $price) {}

    public function priceWithVat(): float
    {
        return round($this->price * (1 + self::VAT_RATE), 2);
    }
}

echo Book::MAX_TITLE_LENGTH . "\n";
echo (new Book('Dune', 19.99))->priceWithVat();
```

Constant names are usually written in `UPPER_SNAKE_CASE`. Trying to change one is an error
before the script even runs:

```php
// Error: a constant can't be assigned a new value.
class Book
{
    public const VAT_RATE = 0.0;
}

Book::VAT_RATE = 0.2;
```

## Static properties and methods

Normal properties belong to each **object**: every book has its own title. A **static**
property belongs to the **class** itself, so there is only one, shared by everyone. Static
methods are called on the class too, without an object:

```php
class Book
{
    private static int $created = 0;

    public function __construct(public string $title)
    {
        self::$created++;
    }

    public static function created(): int
    {
        return self::$created;
    }
}

new Book('Dune');
new Book('Circe');
new Book('Beloved');

echo Book::created() . ' books created';
```

Inside a static method there is no `$this`, because it isn't called on an object.

## Watch out for shared state

Because a static property is shared, every part of a program that touches it affects every
other part. Two separate shopping baskets that kept their items in a static property would
end up with each other's books:

```php
class Basket
{
    private static array $items = [];

    public function add(string $title): void
    {
        self::$items[] = $title;
    }

    public function count(): int
    {
        return count(self::$items);
    }
}

$mine = new Basket();
$yours = new Basket();

$mine->add('Dune');
$yours->add('Circe');

echo 'My basket has ' . $mine->count() . " items\n";
```

It prints 2, though only one book went into "my" basket. Keep per-object data in normal
properties. Use static ones only for things that truly belong to the class as a whole, like a
counter or a cache, and even then, sparingly.

## `self::` and `static::`

Inside a class, `self::` means "the class this code is written in". `static::` means "the
class the method was actually called on", which can be a child. The difference shows when a
child overrides something:

```php
class Book
{
    public static function kind(): string
    {
        return 'book';
    }

    public static function withSelf(): string
    {
        return self::kind();
    }

    public static function withStatic(): string
    {
        return static::kind();
    }
}

class EBook extends Book
{
    public static function kind(): string
    {
        return 'e-book';
    }
}

echo EBook::withSelf() . "\n";
echo EBook::withStatic() . "\n";
```

`self::kind()` is always `Book`'s version; `static::kind()` picked `EBook`'s.

## Named constructors

A class has one `__construct()`, but objects often arrive in different shapes: a row from a
database, an array from a form. A **named constructor** is a static method that builds an
object from one such shape, with a name that says what it expects:

```php
class Book
{
    public function __construct(public string $title, public string $author) {}

    public static function fromArray(array $data): static
    {
        return new static($data['title'], $data['author']);
    }

    public static function fromCsvLine(string $line): static
    {
        [$title, $author] = explode(',', $line);

        return new static(trim($title), trim($author));
    }
}

$a = Book::fromArray(['title' => 'Dune', 'author' => 'Frank Herbert']);
$b = Book::fromCsvLine('Circe, Madeline Miller');

echo $a->title . ' / ' . $b->author;
```

`new static(…)` creates an object of whichever class the method was called on, so
`EBook::fromArray(…)` would build an `EBook`.

## Enums

Some values can only be one of a fixed set: a genre is Fantasy, Horror, Romance or one of a
few others. A string would let typos like `'Fantsy'` slip in. An **enum** lists the allowed
values as **cases**:

```php
enum Genre
{
    case Fantasy;
    case Horror;
    case Romance;
    case ScienceFiction;
}

class Book
{
    public function __construct(public string $title, public Genre $genre) {}
}

$book = new Book('Dune', Genre::ScienceFiction);

var_dump($book->genre === Genre::ScienceFiction);
echo $book->genre->name . "\n";
echo count(Genre::cases()) . ' genres';
```

A parameter typed `Genre` accepts only those four cases. Each case is a single object, so
`===` compares them, `->name` gives the case's name, and `Genre::cases()` lists them all.

## Backed enums

To store an enum in a database or read one from a form, each case needs a plain value. A
**backed enum** gives every case a string (or int) value:

```php
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
    case EBook = 'ebook';
}

echo Format::Paperback->value . "\n";

$format = Format::from('ebook');
echo $format->name . "\n";

var_dump(Format::tryFrom('scroll'));
```

`->value` gives the stored value. `from()` turns a value back into a case, and `tryFrom()`
does the same but gives `null` for a value that isn't one. `from()` with an unknown value is
an error:

```php
// Error: 'scroll' isn't a valid Format.
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
}

$format = Format::from('scroll');
```

## Methods on enums, and `match`

Enums can have methods. Inside one, `$this` is the case, and `match` picks a result for each
case. A `match` must cover every case it is given; if one is missing, PHP throws an
`UnhandledMatchError` when it meets it.

```php
enum Format: string
{
    case Hardback = 'hardback';
    case Paperback = 'paperback';
    case EBook = 'ebook';

    public function label(): string
    {
        return match ($this) {
            self::Hardback => 'Hardback edition',
            self::Paperback => 'Paperback edition',
            self::EBook => 'E-book (instant download)',
        };
    }

    public function isPhysical(): bool
    {
        return $this !== self::EBook;
    }
}

foreach (Format::cases() as $format) {
    echo $format->label() . ($format->isPhysical() ? ', ships by post' : '') . "\n";
}
```

Next, try the quiz to check your understanding, then solve the challenges.
````

- [ ] **Step 5: Write lesson 4's quiz and challenges**

Create `app/database/content/php-oop/04-static-members-constants-and-enums.php`:

```php
<?php

// Lesson 4 of the OOP in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'static-members-constants-and-enums',
    'title' => 'Static members, constants and enums',
    'summary' => 'Give classes their own constants and static members, build objects with named constructors, and model fixed sets of values with enums.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        enum Format: string
        {
            case Hardback = 'hardback';
            case Paperback = 'paperback';
            case EBook = 'ebook';
        }

        $format = Format::from('ebook');
        echo $format->name;
        PHP,

    'quiz' => [
        [
            'question' => 'How do you read the constant `VAT_RATE` of class `Book` from outside the class?',
            'options' => ['`Book->VAT_RATE`', '`Book::VAT_RATE`', '`$Book::VAT_RATE`', '`Book.VAT_RATE`'],
            'correct_option' => 1,
            'explanation' => 'Class constants and static members are read with the class name and `::`. Inside the class, `self::VAT_RATE` works too.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Visitor
                {
                    public static int $count = 0;

                    public function __construct()
                    {
                        self::$count++;
                    }
                }

                $a = new Visitor();
                $b = new Visitor();

                echo Visitor::$count;
                PHP,
            'options' => ['`0`', '`1`', '`2`', 'An error: `$count` belongs to each object'],
            'correct_option' => 2,
            'explanation' => 'There is only one static `$count`, shared by the whole class, so both constructors add to the same number.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                class Book
                {
                    public static function kind(): string
                    {
                        return 'book';
                    }

                    public static function describe(): string
                    {
                        return self::kind() . ' / ' . static::kind();
                    }
                }

                class EBook extends Book
                {
                    public static function kind(): string
                    {
                        return 'e-book';
                    }
                }

                echo EBook::describe();
                PHP,
            'options' => ['`book / book`', '`e-book / e-book`', '`book / e-book`', '`e-book / book`'],
            'correct_option' => 2,
            'explanation' => '`self::` means the class the code is written in (`Book`); `static::` means the class it was called on (`EBook`).',
        ],
        [
            'question' => 'Why is a static method like `Book::fromArray($data)` called a *named constructor*?',
            'options' => [
                'Because it replaces `__construct()`',
                'Because it\'s a static method that creates and returns a new object, with a name that says what it\'s built from',
                'Because it can only be called once',
                'Because PHP calls it automatically on `new`',
            ],
            'correct_option' => 1,
            'explanation' => 'Named constructors are ordinary static methods that build objects, often from a particular shape of data.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                enum Genre
                {
                    case Fantasy;
                    case Horror;
                }

                $a = Genre::Fantasy;
                $b = Genre::Fantasy;

                var_dump($a === $b);
                echo $a->name;
                PHP,
            'options' => [
                '`bool(true)` then `Fantasy`',
                '`bool(false)` then `Fantasy`',
                '`bool(true)` then `0`',
                'An error: enum cases can\'t be compared',
            ],
            'correct_option' => 0,
            'explanation' => 'Each enum case exists exactly once, so two variables holding `Genre::Fantasy` hold the same object. `->name` is the case\'s name.',
        ],
        [
            'question' => 'For a backed enum `Format`, what does `Format::tryFrom(\'scroll\')` return when no case has the value `scroll`?',
            'options' => ['The first case', '`null`', 'An empty string', 'It throws an error'],
            'correct_option' => 1,
            'explanation' => '`tryFrom()` gives `null` for an unknown value. `from()` is the one that throws a `ValueError`.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                enum Format: string
                {
                    case Hardback = 'hardback';
                    case Paperback = 'paperback';
                }

                echo Format::Paperback->value;
                PHP,
            'options' => ['`Paperback`', '`paperback`', '`1`', '`Format::Paperback`'],
            'correct_option' => 1,
            'explanation' => '`->value` is the value the case is backed by; `->name` would give `Paperback`.',
        ],
        [
            'question' => 'A `match ($this)` inside an enum has an arm for every case except one. What happens when that case\'s method is called?',
            'options' => [
                'It returns `null`',
                'It returns the first arm\'s result',
                'PHP refuses the enum before anything runs',
                'An `UnhandledMatchError` is thrown',
            ],
            'correct_option' => 3,
            'explanation' => '`match` has no silent fallback: a value with no matching arm (and no `default`) throws an `UnhandledMatchError`.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'One VAT rate',
            'prompt' => 'Give `Product` a public class constant `VAT_RATE` of `0.2`, and make `priceWithVat()` use it instead of the number written into the calculation.',
            'starter_code' => <<<'PHP'
                <?php

                class Product
                {
                    public function __construct(public float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * 1.2, 2);
                    }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Product
                {
                    public const VAT_RATE = 0.2;

                    public function __construct(public float $price) {}

                    public function priceWithVat(): float
                    {
                        return round($this->price * (1 + self::VAT_RATE), 2);
                    }
                }
                PHP,
            'hint' => 'Declare `public const VAT_RATE = 0.2;` and write `(1 + self::VAT_RATE)` in the calculation.',
            'checks' => [
                ['label' => 'Product::VAT_RATE is 0.2', 'code' => <<<'PHP'
                    if (! defined('Product::VAT_RATE')) return 'Product has no VAT_RATE constant.';
                    return expect_same(Product::VAT_RATE, 0.2);
                    PHP],
                ['label' => 'priceWithVat() gives 12.0 for 10.0', 'code' => "return expect_same((new Product(10.0))->priceWithVat(), 12.0);"],
            ],
        ],
        [
            'title' => 'Counting books',
            'prompt' => 'Make `Book` count how many books have been created: a **private static** `int` property that the constructor adds 1 to, and a **static** method `created(): int` that returns it.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    private static int $created = 0;

                    public function __construct(public string $title)
                    {
                        self::$created++;
                    }

                    public static function created(): int
                    {
                        return self::$created;
                    }
                }
                PHP,
            'hint' => 'Declare `private static int $created = 0;`, add `self::$created++;` to the constructor, and return `self::$created` from a `public static function created(): int`.',
            'checks' => [
                ['label' => 'created() is a static method', 'code' => <<<'PHP'
                    if (! method_exists('Book', 'created')) return 'Book has no created() method.';
                    return (new ReflectionMethod('Book', 'created'))->isStatic() ?: 'created() should be static.';
                    PHP],
                ['label' => 'Creating three books adds 3', 'code' => <<<'PHP'
                    $before = Book::created();
                    new Book('Dune');
                    new Book('Circe');
                    new Book('Beloved');
                    return expect_same(Book::created() - $before, 3);
                    PHP],
                ['label' => 'The count is kept in a private static property', 'code' => <<<'PHP'
                    foreach ((new ReflectionClass('Book'))->getProperties(ReflectionProperty::IS_STATIC) as $p) {
                        if ($p->isPrivate()) return true;
                    }
                    return 'Keep the count in a private static property.';
                    PHP],
            ],
        ],
        [
            'title' => 'Build from an array',
            'prompt' => 'Add a named constructor `fromArray(array $data)` to `Book`: a static method that returns a new `Book` built from an array with `title` and `author` keys, like `[\'title\' => \'Dune\', \'author\' => \'Frank Herbert\']`.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $author) {}

                    public static function fromArray(array $data): static
                    {
                        return new static($data['title'], $data['author']);
                    }
                }
                PHP,
            'hint' => '`public static function fromArray(array $data): static` returning `new static($data[\'title\'], $data[\'author\'])`. (`self` works too.)',
            'checks' => [
                ['label' => 'fromArray() is a static method', 'code' => <<<'PHP'
                    if (! method_exists('Book', 'fromArray')) return 'Book has no fromArray() method.';
                    return (new ReflectionMethod('Book', 'fromArray'))->isStatic() ?: 'fromArray() should be static.';
                    PHP],
                ['label' => 'It builds a Book with the title and author', 'code' => <<<'PHP'
                    $book = Book::fromArray(['title' => 'Dune', 'author' => 'Frank Herbert']);
                    if (! $book instanceof Book) return 'fromArray() should return a Book.';
                    return expect_same([$book->title, $book->author], ['Dune', 'Frank Herbert']);
                    PHP],
                ['label' => 'The order of the keys doesn\'t matter', 'code' => <<<'PHP'
                    $book = Book::fromArray(['author' => 'Toni Morrison', 'title' => 'Beloved']);
                    return expect_same([$book->title, $book->author], ['Beloved', 'Toni Morrison']);
                    PHP],
            ],
        ],
        [
            'title' => 'Genres',
            'prompt' => 'Declare an enum `Genre` with the cases `Fantasy`, `Horror`, `Romance` and `ScienceFiction`, in that order. Then change `Book` so its `$genre` is a `Genre` instead of a string.',
            'starter_code' => <<<'PHP'
                <?php

                class Book
                {
                    public function __construct(public string $title, public string $genre) {}
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum Genre
                {
                    case Fantasy;
                    case Horror;
                    case Romance;
                    case ScienceFiction;
                }

                class Book
                {
                    public function __construct(public string $title, public Genre $genre) {}
                }
                PHP,
            'hint' => 'Declare `enum Genre { case Fantasy; … }`, and change the constructor\'s `string $genre` to `Genre $genre`.',
            'checks' => [
                ['label' => 'An enum named Genre exists', 'code' => "return enum_exists('Genre', false) ?: 'There is no enum named Genre.';"],
                ['label' => 'Genre has Fantasy, Horror, Romance and ScienceFiction', 'code' => "return expect_same(array_map(fn (\$case) => \$case->name, Genre::cases()), ['Fantasy', 'Horror', 'Romance', 'ScienceFiction']);"],
                ['label' => 'A book\'s genre is a Genre', 'code' => <<<'PHP'
                    $type = (string) (new ReflectionProperty('Book', 'genre'))->getType();
                    if ($type !== 'Genre') return '$genre should be typed Genre.';
                    return expect_same((new Book('Dune', Genre::ScienceFiction))->genre, Genre::ScienceFiction);
                    PHP],
            ],
        ],
        [
            'title' => 'Order status',
            'prompt' => "Declare a **string-backed** enum `OrderStatus` with these cases and values: `Pending` (`pending`), `Shipped` (`shipped`) and `Delivered` (`delivered`). Give it a method `label(): string` that returns `Waiting to ship`, `On its way` and `Delivered` respectively.",
            'starter_code' => <<<'PHP'
                <?php

                enum OrderStatus
                {
                    case Pending;
                    case Shipped;
                    case Delivered;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum OrderStatus: string
                {
                    case Pending = 'pending';
                    case Shipped = 'shipped';
                    case Delivered = 'delivered';

                    public function label(): string
                    {
                        return match ($this) {
                            self::Pending => 'Waiting to ship',
                            self::Shipped => 'On its way',
                            self::Delivered => 'Delivered',
                        };
                    }
                }
                PHP,
            'hint' => 'Write `enum OrderStatus: string` and give each case a value with `=`. In `label()`, use `match ($this)` with an arm for each case.',
            'checks' => [
                ['label' => 'OrderStatus is backed by strings', 'code' => <<<'PHP'
                    if (! enum_exists('OrderStatus', false)) return 'There is no enum named OrderStatus.';
                    return expect_same((string) (new ReflectionEnum('OrderStatus'))->getBackingType(), 'string');
                    PHP],
                ['label' => 'The values are pending, shipped and delivered', 'code' => "return expect_same(array_map(fn (\$case) => \$case->value, OrderStatus::cases()), ['pending', 'shipped', 'delivered']);"],
                ['label' => "OrderStatus::from('shipped') is Shipped", 'code' => "return expect_same(OrderStatus::from('shipped'), OrderStatus::Shipped);"],
                ['label' => 'label() gives the right text for each case', 'code' => "return expect_same(array_map(fn (\$case) => \$case->label(), OrderStatus::cases()), ['Waiting to ship', 'On its way', 'Delivered']);"],
            ],
        ],
        [
            'title' => 'Shipping costs',
            'prompt' => "Write a function `shippingCost(DeliverySpeed \$speed, float \$orderTotal): float` for the `DeliverySpeed` enum below:\n\n- `Standard` costs 2.99, but is **free** (0.0) when the order total is 50 or more;\n- `Express` costs 4.99;\n- `NextDay` costs 9.99.",
            'starter_code' => <<<'PHP'
                <?php

                enum DeliverySpeed
                {
                    case Standard;
                    case Express;
                    case NextDay;
                }

                // Write shippingCost() here.
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum DeliverySpeed
                {
                    case Standard;
                    case Express;
                    case NextDay;
                }

                function shippingCost(DeliverySpeed $speed, float $orderTotal): float
                {
                    return match ($speed) {
                        DeliverySpeed::Standard => $orderTotal >= 50 ? 0.0 : 2.99,
                        DeliverySpeed::Express => 4.99,
                        DeliverySpeed::NextDay => 9.99,
                    };
                }
                PHP,
            'hint' => 'Use `match ($speed)` with one arm per case. The `Standard` arm can use `$orderTotal >= 50 ? 0.0 : 2.99`.',
            'checks' => [
                ['label' => 'A function shippingCost() exists', 'code' => "return function_exists('shippingCost') ?: 'There is no function called shippingCost().';"],
                ['label' => 'Standard costs 2.99 under 50', 'code' => "return expect_same(shippingCost(DeliverySpeed::Standard, 49.99), 2.99);"],
                ['label' => 'Standard is free from 50', 'code' => "return expect_same(shippingCost(DeliverySpeed::Standard, 50.0), 0.0);"],
                ['label' => 'Express and NextDay cost 4.99 and 9.99, whatever the total', 'code' => "return expect_same([shippingCost(DeliverySpeed::Express, 80.0), shippingCost(DeliverySpeed::NextDay, 10.0)], [4.99, 9.99]);"],
            ],
        ],
    ],
];
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter='PhpLessonContentTest|PhpChallengeHarnessTest|CourseApiTest' | tail -1`
Expected: PASS. Then `php artisan test | tail -1` (PASS, 4 skipped) and `vendor/bin/pint --test database tests | tail -1` (passed; if not, run `vendor/bin/pint database tests` and re-run the tests).

- [ ] **Step 7: Seed and check**

```bash
php artisan db:seed --class=PhpOopCourseSeeder
php artisan tinker --execute="echo App\Models\Course::where('slug','php-oop')->first()->lessons()->orderBy('position')->pluck('slug')->toJson();"
```

Expected: the seeder finishes with no errors; the list is `["classes-and-objects","inheritance","interfaces-and-abstract-classes","static-members-constants-and-enums"]`.

- [ ] **Step 8: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add app/database/content/php-oop/04-static-members-constants-and-enums.md app/database/content/php-oop/04-static-members-constants-and-enums.php app/database/seeders/PhpOopCourseSeeder.php app/tests/Feature/PhpLessonContentTest.php app/tests/Feature/PhpChallengeHarnessTest.php app/tests/Feature/CourseApiTest.php
git commit -m "Add OOP in PHP lesson 4: Static members, constants and enums"
```
