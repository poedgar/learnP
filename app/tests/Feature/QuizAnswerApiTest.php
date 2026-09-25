<?php

namespace Tests\Feature;

use App\Models\QuizQuestion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizAnswerApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    public function test_correct_answer_is_confirmed_with_an_explanation(): void
    {
        $question = QuizQuestion::first();

        $this->postJson("/api/quiz-questions/{$question->id}/answer", ['option' => $question->correct_option])
            ->assertOk()
            ->assertJsonPath('correct', true)
            ->assertJsonPath('correct_option', $question->correct_option)
            ->assertJsonPath('explanation', $question->explanation);
    }

    public function test_wrong_answer_reveals_the_correct_option(): void
    {
        $question = QuizQuestion::first();
        $wrong = ($question->correct_option + 1) % count($question->options);

        $this->postJson("/api/quiz-questions/{$question->id}/answer", ['option' => $wrong])
            ->assertOk()
            ->assertJsonPath('correct', false)
            ->assertJsonPath('correct_option', $question->correct_option);
    }

    public function test_option_must_exist(): void
    {
        $question = QuizQuestion::first();

        $this->postJson("/api/quiz-questions/{$question->id}/answer", ['option' => count($question->options)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option');
    }

    public function test_every_seeded_question_has_a_valid_correct_option(): void
    {
        foreach (QuizQuestion::all() as $question) {
            $this->assertArrayHasKey($question->correct_option, $question->options, $question->question);
        }
    }
}
