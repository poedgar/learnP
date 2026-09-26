<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Lesson;
use App\Sandbox\PhpSandbox;
use App\Sandbox\PhpSandboxUnavailable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhpSandboxApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function phpLesson(): Lesson
    {
        return Lesson::whereRelation('course', 'language', 'php')->firstOrFail();
    }

    public function test_php_lessons_run_code_in_the_php_sandbox(): void
    {
        $this->mock(PhpSandbox::class)
            ->shouldReceive('run')->once()->with('echo 1;')
            ->andReturn(['output' => '1', 'fatal' => false, 'durationMs' => 12]);

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => 'echo 1;'])
            ->assertOk()
            ->assertExactJson(['output' => '1', 'fatal' => false, 'durationMs' => 12]);
    }

    public function test_php_code_is_required(): void
    {
        $this->mock(PhpSandbox::class)->shouldNotReceive('run');

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'Write some PHP before running it.']);
    }

    public function test_php_code_length_is_limited(): void
    {
        $this->mock(PhpSandbox::class)->shouldNotReceive('run');

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => str_repeat('x', PhpSandbox::MAX_CODE_LENGTH + 1)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_sql_lessons_still_expect_sql(): void
    {
        $lesson = Lesson::where('dataset', 'bookstore')->firstOrFail();

        $this->postJson("/api/lessons/{$lesson->id}/run", ['code' => 'SELECT 1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sql');
    }

    public function test_php_challenges_are_checked_against_their_checks(): void
    {
        $challenge = Challenge::where('title', 'Your first class')->firstOrFail();
        $report = ['passed' => true, 'loaded' => true, 'error' => null, 'output' => '', 'checks' => [['label' => 'a', 'passed' => true, 'message' => null]]];

        $this->mock(PhpSandbox::class)
            ->shouldReceive('check')->once()->with('<?php class Book {}', $challenge->checks)
            ->andReturn($report);

        $this->postJson("/api/challenges/{$challenge->id}/check", ['code' => '<?php class Book {}'])
            ->assertOk()
            ->assertExactJson($report);
    }

    public function test_an_unavailable_sandbox_returns_503_with_a_readable_message(): void
    {
        $this->mock(PhpSandbox::class)
            ->shouldReceive('run')->andThrow(new PhpSandboxUnavailable('Could not reach the runner.'));

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => 'echo 1;'])
            ->assertStatus(503)
            ->assertExactJson(['message' => 'The PHP sandbox is unavailable. Try again shortly.']);
    }

    public function test_an_unavailable_sandbox_returns_503_with_a_readable_message_for_check(): void
    {
        $challenge = Challenge::where('title', 'Your first class')->firstOrFail();

        $this->mock(PhpSandbox::class)
            ->shouldReceive('check')->andThrow(new PhpSandboxUnavailable('Could not reach the runner.'));

        $this->postJson("/api/challenges/{$challenge->id}/check", ['code' => '<?php class Book {}'])
            ->assertStatus(503)
            ->assertExactJson(['message' => 'The PHP sandbox is unavailable. Try again shortly.']);
    }

    public function test_php_code_is_passed_on_untrimmed(): void
    {
        $this->mock(PhpSandbox::class)
            ->shouldReceive('run')->once()->with("  echo 1;\n\n")
            ->andReturn(['output' => '1', 'fatal' => false, 'durationMs' => 12]);

        $this->postJson("/api/lessons/{$this->phpLesson()->id}/run", ['code' => "  echo 1;\n\n"])
            ->assertOk();
    }
}
