<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgressApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private User $user;

    private int $lessonId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function afterRefreshingDatabase(): void
    {
        $this->lessonId = Lesson::where('slug', 'your-first-queries')->firstOrFail()->id;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function lesson(array $overrides = []): array
    {
        return [...['read' => false, 'sandboxCode' => null, 'quiz' => [], 'challenges' => []], ...$overrides];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, int|float>
     */
    private function card(array $overrides = []): array
    {
        return [...['due' => 1_700_000_000_000, 'interval' => 1, 'ease' => 2.5, 'reps' => 1, 'lapses' => 0], ...$overrides];
    }

    public function test_a_new_account_has_empty_maps_not_lists(): void
    {
        $response = $this->getJson('/api/progress')->assertOk();

        $this->assertSame('{"lessons":{},"cards":{}}', $response->getContent());
    }

    public function test_saved_progress_comes_back_in_the_same_shape(): void
    {
        $quiz = [11 => ['option' => 1, 'correct' => true, 'correct_option' => 1, 'explanation' => 'FROM names the table.']];
        $challenges = [21 => ['passed' => true, 'code' => 'SELECT 1;']];

        $this->putJson('/api/progress', [
            'lessons' => [$this->lessonId => $this->lesson(['read' => true, 'theoryPage' => 3, 'quiz' => $quiz, 'challenges' => $challenges])],
            'cards' => ['quiz:11' => $this->card()],
        ])->assertNoContent();

        $this->getJson('/api/progress')
            ->assertOk()
            ->assertExactJson([
                'lessons' => [$this->lessonId => [
                    'read' => true,
                    'theoryPage' => 3,
                    'sandboxCode' => null,
                    'quiz' => $quiz,
                    'challenges' => $challenges,
                ]],
                'cards' => ['quiz:11' => $this->card()],
            ]);
    }

    public function test_saving_replaces_a_lesson_so_a_reset_quiz_stays_reset(): void
    {
        $answer = ['option' => 0, 'correct' => false, 'correct_option' => 1, 'explanation' => ''];
        $this->putJson('/api/progress', ['lessons' => [$this->lessonId => $this->lesson(['quiz' => [11 => $answer]])], 'cards' => []]);

        $this->putJson('/api/progress', ['lessons' => [$this->lessonId => $this->lesson()], 'cards' => []])->assertNoContent();

        $this->assertStringContainsString('"quiz":{}', $this->getJson('/api/progress')->getContent());
        $this->assertSame(1, $this->user->lessonProgress()->count());
    }

    public function test_lessons_that_do_not_exist_and_unknown_card_keys_are_ignored(): void
    {
        $this->putJson('/api/progress', [
            'lessons' => [999999 => $this->lesson(['read' => true])],
            'cards' => ['bogus' => $this->card(), 'challenge:5' => $this->card()],
        ])->assertNoContent();

        $this->assertSame(0, $this->user->lessonProgress()->count());
        $this->assertSame(['challenge:5'], $this->user->reviewCards()->pluck('item_key')->all());
    }

    public function test_merging_keeps_everything_passed_on_either_side(): void
    {
        $right = ['option' => 1, 'correct' => true, 'correct_option' => 1, 'explanation' => 'FROM names the table.'];
        $wrong = ['option' => 0, 'correct' => false, 'correct_option' => 1, 'explanation' => 'FROM names the table.'];

        $this->putJson('/api/progress', [
            'lessons' => [$this->lessonId => $this->lesson([
                'read' => true,
                'theoryPage' => 4,
                'sandboxCode' => 'SELECT * FROM books;',
                'quiz' => [11 => $right],
                'challenges' => [21 => ['passed' => true, 'code' => 'solved'], 22 => ['passed' => false, 'code' => 'draft']],
            ])],
            'cards' => ['quiz:11' => $this->card(['due' => 2000]), 'quiz:12' => $this->card(['due' => 1000])],
        ]);

        $this->postJson('/api/progress/merge', [
            'lessons' => [$this->lessonId => $this->lesson([
                'theoryPage' => 2,
                'quiz' => [12 => $wrong],
                'challenges' => [21 => ['passed' => false, 'code' => 'worse'], 22 => ['passed' => true, 'code' => 'solved too']],
            ])],
            'cards' => ['quiz:11' => $this->card(['due' => 1500]), 'quiz:12' => $this->card(['due' => 3000])],
        ])
            ->assertOk()
            ->assertJsonPath("lessons.{$this->lessonId}.read", true)
            ->assertJsonPath("lessons.{$this->lessonId}.theoryPage", 4)
            ->assertJsonPath("lessons.{$this->lessonId}.sandboxCode", 'SELECT * FROM books;')
            ->assertJsonPath("lessons.{$this->lessonId}.quiz.11", $right)
            ->assertJsonPath("lessons.{$this->lessonId}.quiz.12", $wrong)
            ->assertJsonPath("lessons.{$this->lessonId}.challenges.21", ['passed' => true, 'code' => 'solved'])
            ->assertJsonPath("lessons.{$this->lessonId}.challenges.22", ['passed' => true, 'code' => 'solved too'])
            ->assertJsonPath('cards.quiz:11.due', 1500)
            ->assertJsonPath('cards.quiz:12.due', 1000);
    }

    public function test_merging_into_an_empty_account_saves_the_device_progress(): void
    {
        $this->postJson('/api/progress/merge', [
            'lessons' => [$this->lessonId => $this->lesson(['read' => true])],
            'cards' => [],
        ])
            ->assertOk()
            ->assertJsonPath("lessons.{$this->lessonId}.read", true);

        $this->assertSame(1, $this->user->lessonProgress()->count());
    }

    public function test_progress_is_private_to_each_account(): void
    {
        $this->putJson('/api/progress', ['lessons' => [$this->lessonId => $this->lesson(['read' => true])], 'cards' => []]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/progress')->assertExactJson(['lessons' => [], 'cards' => []]);
    }

    public function test_the_shape_is_validated(): void
    {
        $this->putJson('/api/progress', [
            'lessons' => [$this->lessonId => ['read' => 'yes', 'quiz' => [], 'challenges' => [], 'extra' => 1]],
            'cards' => ['quiz:1' => ['due' => -1]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(["lessons.{$this->lessonId}", "lessons.{$this->lessonId}.read", 'cards.quiz:1.due', 'cards.quiz:1.ease']);
    }

    public function test_progress_needs_a_signed_in_learner(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/progress')->assertUnauthorized();
        $this->putJson('/api/progress', ['lessons' => [], 'cards' => []])->assertUnauthorized();
        $this->postJson('/api/progress/merge', ['lessons' => [], 'cards' => []])->assertUnauthorized();
    }
}
