<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use App\Models\Track;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    public function test_tracks_list_their_courses_in_order(): void
    {
        $response = $this->getJson('/api/tracks')->assertOk();

        $response->assertJsonPath('data.0.slug', 'php-developer')
            ->assertJsonPath('data.0.courses.0', 'php-basics')
            ->assertJsonPath('data.0.courses.1', 'php-oop');
    }

    public function test_every_course_is_on_a_track(): void
    {
        $onTracks = DB::table('course_track')->distinct()->pluck('course_id')->all();

        $this->assertSame([], Course::whereNotIn('id', $onTracks)->pluck('slug')->all(), 'These courses are on no track; add them in TrackSeeder.');
        $this->assertGreaterThan(0, Track::count());
    }

    public function test_courses_carry_a_label_and_their_lessons_for_progress(): void
    {
        $response = $this->getJson('/api/courses')->assertOk();
        $courses = collect($response->json('data'))->keyBy('slug');

        $this->assertSame('Docker', $courses['docker']['label']);
        $this->assertNull($courses['sql']['label']);
        $this->assertSame(Lesson::whereRelation('course', 'slug', 'sql')->count(), count($courses['sql']['lessons']));
        $this->assertArrayHasKey('challenges_count', $courses['sql']['lessons'][0]);
        $this->assertArrayNotHasKey('content', $courses['sql']['lessons'][0]);
    }

    public function test_search_finds_titles_first_then_theory(): void
    {
        $results = $this->getJson('/api/search?q=window functions')->assertOk()->json('data');

        $this->assertSame('window-functions', $results[0]['lesson']['slug']);
        $this->assertSame('sql', $results[0]['course']['slug']);
    }

    public function test_search_snippets_show_the_match_in_the_theory(): void
    {
        $results = collect($this->getJson('/api/search?q=array_column')->assertOk()->json('data'));

        $this->assertNotEmpty($results);
        $this->assertTrue($results->contains(fn ($r) => str_contains(strtolower($r['snippet']), 'array_column')));
    }

    public function test_search_treats_wildcards_as_text_and_needs_two_characters(): void
    {
        $this->getJson('/api/search?q=%25%25%25')->assertOk()->assertJsonPath('data', []);
        $this->getJson('/api/search?q=a')->assertUnprocessable();
    }

    public function test_checks_hints_and_solutions_are_counted(): void
    {
        $challenge = Challenge::firstOrFail();

        $this->postJson("/api/challenges/{$challenge->id}/attempts", ['passed' => false, 'attempt' => 1])->assertNoContent();
        $this->postJson("/api/challenges/{$challenge->id}/attempts", ['passed' => true, 'attempt' => 2])->assertNoContent();
        $this->postJson("/api/challenges/{$challenge->id}/hint-views")->assertNoContent();
        $this->getJson("/api/challenges/{$challenge->id}/solution")->assertOk()->assertJsonPath('solution', $challenge->solution);

        $this->assertDatabaseHas('challenge_stats', [
            'challenge_id' => $challenge->id,
            'checks' => 2,
            'passes' => 1,
            'first_try_passes' => 0,
            'hint_views' => 1,
            'solution_views' => 1,
        ]);
    }

    public function test_first_try_passes_are_counted(): void
    {
        $challenge = Challenge::firstOrFail();

        $this->postJson("/api/challenges/{$challenge->id}/attempts", ['passed' => true, 'attempt' => 1]);

        $this->assertDatabaseHas('challenge_stats', ['challenge_id' => $challenge->id, 'checks' => 1, 'passes' => 1, 'first_try_passes' => 1]);
    }

    public function test_quiz_answers_are_counted(): void
    {
        $question = QuizQuestion::firstOrFail();
        $wrong = ($question->correct_option + 1) % count($question->options);

        $this->postJson("/api/quiz-questions/{$question->id}/answer", ['option' => $question->correct_option]);
        $this->postJson("/api/quiz-questions/{$question->id}/answer", ['option' => $wrong]);

        $this->assertDatabaseHas('quiz_question_stats', ['quiz_question_id' => $question->id, 'answers' => 2, 'correct' => 1]);
    }

    public function test_the_report_lists_the_hardest_challenges(): void
    {
        $challenge = Challenge::firstOrFail();
        foreach ([false, false, true] as $attempt => $passed) {
            $this->postJson("/api/challenges/{$challenge->id}/attempts", ['passed' => $passed, 'attempt' => $attempt + 1]);
        }

        $this->artisan('stats:report', ['--min' => 1])
            ->expectsOutputToContain('Hardest challenges')
            ->expectsOutputToContain($challenge->title)
            ->assertSuccessful();
    }
}
