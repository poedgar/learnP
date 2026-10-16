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

/**
 * The courses whose code runs on the learner's device (JavaScript, TypeScript, regular expressions,
 * HTML & CSS). Their content is checked with the same sandbox engine in Node
 * (tests/js/verify-content.mjs): theory examples, quiz snippets, solutions and starters. JavaScript
 * runs with jsdom for the page; HTML lessons need layout, so they run in a headless Chromium when
 * one is installed. Those tests are skipped when Node or the npm packages aren't installed.
 */
class JsLessonContentTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private const COURSES = [
        'js-basics' => [
            'values-and-variables',
            'comparisons-and-logic',
            'control-flow',
            'functions',
            'arrays',
            'objects-and-json',
            'classes',
            'errors-maps-and-sets',
        ],
        'js-async' => [
            'callbacks-and-the-event-loop',
            'promises',
            'async-and-await',
            'combining-promises',
            'async-patterns',
        ],
        'js-dom' => [
            'finding-things-on-the-page',
            'changing-the-page',
            'events',
            'forms',
            'building-a-small-app',
        ],
        'typescript' => [
            'types-and-inference',
            'object-types',
            'unions-and-narrowing',
            'functions-and-generics',
            'classes-and-utility-types',
            'type-level-tools',
        ],
        'regex' => [
            'patterns-and-character-classes',
            'quantifiers-and-anchors',
            'groups-and-alternation',
            'searching-and-replacing',
            'lookarounds-and-unicode',
            'regex-in-practice',
        ],
        'html-css' => [
            'structure-and-meaning',
            'lists-tables-and-figures',
            'forms',
            'selectors-and-the-cascade',
            'the-box-model',
            'flexbox',
            'grid-and-responsive-design',
        ],
    ];

    /** The verifier's report, shared by the tests below: running it takes a few minutes. */
    private static ?array $report = null;

    /**
     * @return Collection<int, Lesson>
     */
    private function lessons(?string $course = null): Collection
    {
        return Lesson::whereIn('course_id', Course::whereIn('slug', $course === null ? array_keys(self::COURSES) : [$course])->select('id'))
            ->orderBy('course_id')->orderBy('position')
            ->with(['course', 'quizQuestions', 'challenges'])
            ->get();
    }

    private function fence(Lesson $lesson): string
    {
        return $lesson->course->language;
    }

    /** @return array{problems: list<string>, snippets: list<array{lesson: string, question: string, output: string}>, skipped: list<string>} */
    private function report(): array
    {
        if (self::$report !== null) {
            return self::$report;
        }

        if (! Process::run(['node', '--version'])->successful() || ! File::exists(base_path('node_modules/jsdom'))) {
            $this->markTestSkipped('Needs Node and the npm packages (npm install) to check JavaScript lessons.');
        }

        $file = tempnam(sys_get_temp_dir(), 'learnp-js-');
        File::put($file, json_encode($this->lessons()->map(fn (Lesson $lesson) => [
            'name' => "{$lesson->course->slug}/{$lesson->slug}",
            'language' => $lesson->course->language,
            'content' => $lesson->content,
            'quiz' => $lesson->quizQuestions->map(fn ($q) => [
                'question' => $q->question,
                'code' => $q->code,
                'answer' => $q->options[$q->correct_option],
            ])->all(),
            'challenges' => $lesson->challenges->map(fn ($c) => [
                'title' => $c->title,
                'starter_code' => $c->starter_code,
                'solution' => $c->solution,
                'checks' => $c->checks,
            ])->all(),
        ])->all()));

        try {
            $result = Process::path(base_path())->timeout(900)->run(['node', 'tests/js/verify-content.mjs', $file]);
        } finally {
            File::delete($file);
        }

        $this->assertTrue($result->successful(), $result->errorOutput());

        return self::$report = json_decode($result->output(), true);
    }

    public function test_each_course_has_its_lessons_in_order(): void
    {
        foreach (self::COURSES as $course => $expected) {
            $lessons = $this->lessons($course);

            $this->assertSame($expected, $lessons->pluck('slug')->all());

            foreach ($lessons as $lesson) {
                $this->assertCount(8, $lesson->quizQuestions, "{$lesson->slug}: quiz questions");
                $this->assertCount(6, $lesson->challenges, "{$lesson->slug}: challenges");
            }
        }
    }

    public function test_quiz_questions_have_four_options_and_a_valid_answer(): void
    {
        foreach ($this->lessons() as $lesson) {
            foreach ($lesson->quizQuestions as $question) {
                $this->assertCount(4, $question->options, "{$lesson->slug}: {$question->question}");
                $this->assertContains($question->correct_option, [0, 1, 2, 3], "{$lesson->slug}: {$question->question}");
            }
        }
    }

    /** A challenge is no challenge if its solution is already on screen, in the starter code or a theory example. */
    public function test_no_challenge_solution_is_already_shown_in_the_lesson(): void
    {
        $normalise = fn (?string $code) => preg_replace('/\s+/', ' ', trim((string) $code));

        foreach ($this->lessons() as $lesson) {
            preg_match_all('/```'.$this->fence($lesson).'\n(.*?)```/s', $lesson->content, $matches);
            $shown = array_map($normalise, [$lesson->starter_code, ...$matches[1]]);

            foreach ($lesson->challenges as $challenge) {
                foreach ($shown as $code) {
                    $this->assertStringNotContainsString($normalise($challenge->solution), $code, "{$lesson->slug}: {$challenge->title}");
                }
            }
        }
    }

    /** Learners take the quiz before the challenges, so no quiz snippet may be a challenge's solution. */
    public function test_no_quiz_question_gives_away_a_challenge_solution(): void
    {
        $normalise = fn (?string $code) => preg_replace('/\s+/', ' ', trim((string) $code));

        foreach ($this->lessons() as $lesson) {
            $solutions = $lesson->challenges->map(fn ($challenge) => $normalise($challenge->solution))->all();

            foreach ($lesson->quizQuestions->whereNotNull('code') as $question) {
                $this->assertNotContains($normalise($question->code), $solutions, "{$lesson->slug}: {$question->question}");
            }
        }
    }

    /**
     * Every example runs (those starting with "// Error:" must fail); quiz snippets stand alone and
     * print what their answer says; every solution passes its checks and every starter fails one.
     */
    public function test_the_lessons_run_in_the_sandbox_engine(): void
    {
        $report = $this->report();

        $this->assertSame([], $report['problems'], implode("\n\n", $report['problems']));
        $this->assertNotEmpty($report['snippets']);

        if ($report['skipped'] !== []) {
            $this->markTestIncomplete('No Chromium to check the HTML lessons (npx playwright install chromium, or set LEARNP_CHROMIUM): '.implode(', ', $report['skipped']));
        }
    }

    public function test_the_server_does_not_run_javascript(): void
    {
        $lesson = $this->lessons('js-basics')->first();

        $this->postJson("/api/lessons/{$lesson->id}/run", ['code' => 'console.log(1)'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Code in this course runs on your device, not on the server.');

        $this->postJson("/api/challenges/{$lesson->challenges->first()->id}/check", ['code' => 'console.log(1)'])
            ->assertStatus(422);
    }
}
