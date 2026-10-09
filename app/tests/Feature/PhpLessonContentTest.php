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
        'interfaces-and-abstract-classes',
        'static-members-constants-and-enums',
        'exceptions',
        'traits-and-readonly-properties',
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

    /**
     * A challenge is no challenge if its solution is already on screen, in the
     * lesson's starter code or one of its theory examples.
     */
    public function test_no_challenge_solution_is_already_shown_in_the_lesson(): void
    {
        $normalise = fn (string $code) => preg_replace('/\s+/', ' ', trim(str_replace('<?php', '', $code)));

        foreach ($this->lessons()->where('position', '>', 1) as $lesson) {
            preg_match_all('/```php\n(.*?)```/s', $lesson->content, $matches);
            $shown = array_map($normalise, [$lesson->starter_code, ...$matches[1]]);

            foreach ($lesson->challenges as $challenge) {
                foreach ($shown as $code) {
                    $this->assertStringNotContainsString($normalise($challenge->solution), $code, "{$lesson->slug}: {$challenge->title}");
                }
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
