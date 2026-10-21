<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Lesson;
use App\Sandbox\SqlSandbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\ReadsContentCatalog;
use Tests\TestCase;

class SqlLessonContentTest extends TestCase
{
    use ReadsContentCatalog, RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    /**
     * This test's courses and their lessons in order, from database/content.
     *
     * @return array<string, list<string>>
     */
    private function courses(): array
    {
        return $this->catalogFor('sql');
    }

    /**
     * The lessons of one SQL course, or of every SQL course.
     *
     * @return Collection<int, Lesson>
     */
    private function lessons(?string $course = null): Collection
    {
        return Lesson::whereIn('course_id', Course::whereIn('slug', $course === null ? array_keys($this->courses()) : [$course])->select('id'))
            ->orderBy('course_id')->orderBy('position')
            ->withCount(['quizQuestions', 'challenges'])
            ->get();
    }

    public function test_each_course_has_its_lessons_in_order(): void
    {
        $this->assertNotEmpty($this->courses());

        foreach ($this->courses() as $course => $expected) {
            $lessons = $this->lessons($course);

            $this->assertSame($expected, $lessons->pluck('slug')->all());

            foreach ($lessons as $lesson) {
                // Every SQL lesson runs on one of the datasets in database/datasets.
                $this->assertFileExists(database_path("datasets/{$lesson->dataset}.sql"), "{$lesson->slug} dataset");
                $this->assertSame(8, $lesson->quiz_questions_count, "{$lesson->slug} quiz questions");
                $this->assertSame(6, $lesson->challenges_count, "{$lesson->slug} challenges");
            }
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
     * Every ```sql block is something the learner can run with one tap. Blocks
     * that begin with "-- Error:" show a mistake, so they must fail.
     */
    public function test_every_theory_example_runs_as_the_lesson_says(): void
    {
        $sandbox = app(SqlSandbox::class);

        foreach ($this->lessons() as $lesson) {
            preg_match_all('/```sql\n(.*?)```/s', $lesson->content, $matches);
            $this->assertNotEmpty($matches[1], "{$lesson->slug} has no SQL examples");

            foreach ($matches[1] as $sql) {
                $run = $sandbox->run($lesson->dataset, $sql);

                if (str_starts_with($sql, '-- Error:')) {
                    $this->assertNotNull($run['error'], "{$lesson->slug}: this example should fail:\n{$sql}");
                } else {
                    $this->assertNull($run['error'], "{$lesson->slug}: ".($run['error']['message'] ?? '')."\n{$sql}");
                }
            }
        }
    }

    /**
     * Learners take the quiz before the challenges, so a quiz question must not
     * hand them a challenge's answer. A "complete this query" question (code
     * with a …) is compared with its correct option filled in.
     */
    public function test_no_quiz_question_gives_away_a_challenge_solution(): void
    {
        $normalise = fn (string $sql) => strtolower(preg_replace('/\s+/', ' ', trim($sql, " \n;")));

        foreach ($this->lessons() as $lesson) {
            $solutions = $lesson->challenges->map(fn ($challenge) => $normalise($challenge->solution))->all();

            foreach ($lesson->quizQuestions->whereNotNull('code') as $question) {
                $answered = str_replace('…', trim($question->options[$question->correct_option], '`'), $question->code);
                $this->assertNotContains($normalise($answered), $solutions, "{$lesson->slug}: {$question->question}");
            }
        }
    }

    /**
     * A challenge with a follow-up check must change what the check sees, or a
     * learner could pass without changing anything.
     */
    public function test_every_follow_up_check_sees_what_the_solution_changed(): void
    {
        $sandbox = app(SqlSandbox::class);
        $challenges = Challenge::whereNotNull('check_sql')->with('lesson')->get();
        $this->assertNotEmpty($challenges, 'No challenge has a follow-up check yet.');

        foreach ($challenges as $challenge) {
            $solved = $sandbox->run($challenge->lesson->dataset, $challenge->solution."\n;\n".$challenge->check_sql);
            $untouched = $sandbox->run($challenge->lesson->dataset, $challenge->check_sql);

            $this->assertNull($solved['error'], "{$challenge->title}: ".($solved['error']['message'] ?? ''));
            // Compare rows only: each result also carries its run time, which differs between runs.
            $rows = fn (array $run) => $run['error'] === null ? (end($run['results'])['rows'] ?? null) : null;
            $this->assertNotEquals(
                $rows($solved),
                $rows($untouched),
                "{$challenge->title}: the check sees the same thing whether or not the solution ran",
            );
        }
    }

    public function test_reseeding_keeps_lesson_one_ids(): void
    {
        $ids = fn () => Lesson::where('slug', 'your-first-queries')->firstOrFail()->load('quizQuestions', 'challenges');
        $before = $ids();

        $this->seed(DatabaseSeeder::class);
        $after = $ids();

        $this->assertSame($before->id, $after->id);
        $this->assertSame($before->quizQuestions->pluck('id')->all(), $after->quizQuestions->pluck('id')->all());
        $this->assertSame($before->challenges->pluck('id')->all(), $after->challenges->pluck('id')->all());
    }
}
