<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Course;
use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    public function test_courses_are_listed_with_lesson_counts(): void
    {
        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'sql')
            ->assertJsonPath('data.0.language', 'sql')
            ->assertJsonPath('data.0.lessons_count', 7)
            ->assertJsonPath('data.1.slug', 'php-oop')
            ->assertJsonPath('data.1.language', 'php');
    }

    public function test_course_lists_its_lessons_without_their_content(): void
    {
        $response = $this->getJson('/api/courses/sql')
            ->assertOk()
            ->assertJsonPath('data.lessons.0.slug', 'your-first-queries')
            ->assertJsonPath('data.lessons.0.quiz_questions_count', 8)
            ->assertJsonPath('data.lessons.0.challenges_count', 6);

        $this->assertArrayNotHasKey('content', $response->json('data.lessons.0'));
    }

    public function test_lesson_includes_theory_schema_quiz_and_challenges(): void
    {
        $this->getJson('/api/courses/sql/lessons/your-first-queries')
            ->assertOk()
            ->assertJsonPath('data.title', 'Your first queries')
            ->assertJsonPath('data.course.slug', 'sql')
            ->assertJsonPath('schema.0.name', 'books')
            ->assertJsonPath('schema.0.row_count', 16)
            ->assertJsonCount(8, 'data.quiz_questions')
            ->assertJsonCount(6, 'data.challenges')
            ->assertJsonPath('navigation.previous', null);
    }

    public function test_lesson_does_not_reveal_answers_or_solutions(): void
    {
        $response = $this->getJson('/api/courses/sql/lessons/your-first-queries')->assertOk();

        $this->assertArrayNotHasKey('correct_option', $response->json('data.quiz_questions.0'));
        $this->assertArrayNotHasKey('explanation', $response->json('data.quiz_questions.0'));
        $this->assertArrayNotHasKey('solution', $response->json('data.challenges.0'));
    }

    public function test_lesson_must_belong_to_the_course_in_the_url(): void
    {
        $other = Course::create(['slug' => 'python', 'title' => 'Python', 'description' => 'Soon.', 'language' => 'php']);

        $this->getJson("/api/courses/{$other->slug}/lessons/your-first-queries")->assertNotFound();
    }

    public function test_navigation_links_neighbouring_lessons(): void
    {
        $this->getJson('/api/courses/sql/lessons/your-first-queries')
            ->assertJsonPath('navigation.previous', null)
            ->assertJsonPath('navigation.next.slug', 'finding-the-rows-you-want');
        $this->getJson('/api/courses/sql/lessons/finding-the-rows-you-want')
            ->assertJsonPath('navigation.previous.slug', 'your-first-queries');
        $this->getJson('/api/courses/sql/lessons/combining-tables')
            ->assertJsonPath('navigation.previous.slug', 'summarising-data')
            ->assertJsonPath('navigation.next.slug', 'queries-inside-queries');
        $this->getJson('/api/courses/sql/lessons/queries-inside-queries')
            ->assertJsonPath('navigation.previous.slug', 'combining-tables');
        $this->getJson('/api/courses/sql/lessons/changing-data')
            ->assertJsonPath('navigation.previous.slug', 'queries-inside-queries');
        $this->getJson('/api/courses/sql/lessons/designing-tables')
            ->assertJsonPath('navigation.previous.slug', 'changing-data')
            ->assertJsonPath('navigation.next', null);
    }

    public function test_unknown_course_returns_not_found(): void
    {
        $this->getJson('/api/courses/cobol')->assertNotFound();
    }

    public function test_page_routes_serve_the_single_page_app(): void
    {
        $this->get('/courses/sql/lessons/your-first-queries/quiz')
            ->assertOk()
            ->assertSee('<div id="app"></div>', false);
    }

    public function test_php_lesson_sends_challenge_checks_but_not_solutions(): void
    {
        $response = $this->getJson('/api/courses/php-oop/lessons/classes-and-objects')
            ->assertOk()
            ->assertJsonPath('data.course.language', 'php')
            ->assertJsonPath('schema', [])
            ->assertJsonCount(8, 'data.quiz_questions')
            ->assertJsonCount(5, 'data.challenges')
            ->assertJsonPath('data.challenges.0.checks.0.label', 'A class named Book exists');

        $this->assertArrayNotHasKey('solution', $response->json('data.challenges.0'));
        $this->assertStringStartsWith('<?php', $response->json('data.starter_code'));
    }

    public function test_sql_endpoints_refuse_php_lessons(): void
    {
        $lesson = Lesson::where('slug', 'classes-and-objects')->firstOrFail();
        $challenge = Challenge::where('lesson_id', $lesson->id)->firstOrFail();

        $this->postJson("/api/lessons/{$lesson->id}/run", ['sql' => 'SELECT 1'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->postJson("/api/challenges/{$challenge->id}/check", ['sql' => 'SELECT 1'])
            ->assertUnprocessable()->assertJsonValidationErrors('code');
    }
}
