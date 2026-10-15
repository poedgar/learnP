<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewItemsApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    public function test_it_returns_the_requested_items_with_their_lessons(): void
    {
        $lesson = Lesson::where('slug', 'your-first-queries')->firstOrFail();
        $question = $lesson->quizQuestions()->firstOrFail();
        $challenge = Lesson::where('slug', 'classes-and-objects')->firstOrFail()->challenges()->firstOrFail();

        $this->postJson('/api/review-items', ['quiz_questions' => [$question->id], 'challenges' => [$challenge->id]])
            ->assertOk()
            ->assertJsonCount(1, 'quiz_questions')
            ->assertJsonPath('quiz_questions.0.id', $question->id)
            ->assertJsonPath('quiz_questions.0.question', $question->question)
            ->assertJsonPath('quiz_questions.0.lesson_id', $lesson->id)
            ->assertJsonCount(1, 'challenges')
            ->assertJsonPath('challenges.0.id', $challenge->id)
            ->assertJsonPath('challenges.0.lesson_id', $challenge->lesson_id)
            ->assertJsonCount(2, 'lessons')
            ->assertJsonFragment(['slug' => 'your-first-queries', 'course' => ['slug' => 'sql', 'title' => 'SQL', 'language' => 'sql', 'position' => 1]]);
    }

    public function test_it_never_reveals_answers_or_solutions(): void
    {
        $response = $this->postJson('/api/review-items', [
            'quiz_questions' => QuizQuestion::pluck('id')->all(),
            'challenges' => Challenge::pluck('id')->all(),
        ])->assertOk();

        foreach ($response->json('quiz_questions') as $question) {
            $this->assertArrayNotHasKey('correct_option', $question);
            $this->assertArrayNotHasKey('explanation', $question);
        }

        foreach ($response->json('challenges') as $challenge) {
            $this->assertArrayNotHasKey('solution', $challenge);
            $this->assertArrayNotHasKey('check_sql', $challenge);
        }
    }

    public function test_ids_that_no_longer_exist_are_left_out(): void
    {
        $this->postJson('/api/review-items', ['quiz_questions' => [999999], 'challenges' => []])
            ->assertOk()
            ->assertJsonPath('quiz_questions', [])
            ->assertJsonPath('challenges', [])
            ->assertJsonPath('lessons', []);
    }

    public function test_the_ids_must_be_lists_of_integers(): void
    {
        $this->postJson('/api/review-items', ['quiz_questions' => ['one'], 'challenges' => 'all'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quiz_questions.0', 'challenges']);

        $this->postJson('/api/review-items', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quiz_questions', 'challenges']);
    }
}
