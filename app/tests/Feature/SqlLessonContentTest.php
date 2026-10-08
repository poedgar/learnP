<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Sandbox\SqlSandbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SqlLessonContentTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    /** The SQL course's lessons, in order, with the dataset each one runs on. */
    private const LESSONS = [
        ['your-first-queries', 'bookstore'],
        ['finding-the-rows-you-want', 'bookshop'],
        ['summarising-data', 'bookshop'],
        ['combining-tables', 'bookshop'],
    ];

    /**
     * @return Collection<int, Lesson>
     */
    private function lessons(): Collection
    {
        return Course::where('slug', 'sql')->firstOrFail()
            ->lessons()->orderBy('position')
            ->withCount(['quizQuestions', 'challenges'])
            ->get();
    }

    public function test_the_course_has_its_lessons_in_order(): void
    {
        $lessons = $this->lessons();

        $this->assertSame(self::LESSONS, $lessons->map(fn (Lesson $lesson) => [$lesson->slug, $lesson->dataset])->all());

        foreach ($lessons as $lesson) {
            $this->assertSame(8, $lesson->quiz_questions_count, "{$lesson->slug} quiz questions");
            $this->assertSame(6, $lesson->challenges_count, "{$lesson->slug} challenges");
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
