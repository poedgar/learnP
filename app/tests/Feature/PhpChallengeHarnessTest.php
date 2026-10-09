<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\QuizQuestion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * PHP challenges are checked in the browser, by resources/php/check-harness.php
 * running in WebAssembly. These tests run the same harness with the PHP CLI.
 */
class PhpChallengeHarnessTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/learnp-harness-'.uniqid();
        File::ensureDirectoryExists($this->directory);
        File::copy(resource_path('php/check-harness.php'), $this->directory.'/harness.php');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    /**
     * @param  list<array{label: string, code: string}>  $checks
     * @return array{loaded: bool, error: ?string, output: string, checks: list<array{label: string, passed: bool, message: ?string}>}
     */
    private function runHarness(string $code, array $checks): array
    {
        File::put($this->directory.'/main.php', $code);
        File::put($this->directory.'/checks.json', json_encode($checks));

        $result = Process::path($this->directory)->run([PHP_BINARY, '-d', 'display_errors=1', 'harness.php']);
        [, $report] = explode("\n@@LEARNP_REPORT@@\n", $result->output(), 2) + [1 => null];

        $this->assertNotNull($report, 'The harness printed no report: '.$result->output().$result->errorOutput());

        return json_decode($report, true);
    }

    public function test_every_php_challenge_solution_passes_all_its_checks(): void
    {
        $challenges = Challenge::whereNotNull('checks')->get();
        $this->assertCount(23, $challenges);

        foreach ($challenges as $challenge) {
            $report = $this->runHarness($challenge->solution, $challenge->checks);

            $this->assertTrue($report['loaded'], "{$challenge->title}: {$report['error']}");
            foreach ($report['checks'] as $check) {
                $this->assertTrue($check['passed'], "{$challenge->title} / {$check['label']}: {$check['message']}");
            }
        }
    }

    public function test_every_php_challenge_starter_fails_at_least_one_check(): void
    {
        foreach (Challenge::whereNotNull('checks')->get() as $challenge) {
            $report = $this->runHarness($challenge->starter_code, $challenge->checks);

            $this->assertContains(false, array_column($report['checks'], 'passed'), "{$challenge->title} passes without any work.");
        }
    }

    public function test_php_quiz_snippets_run_on_their_own_in_the_sandbox(): void
    {
        $questions = QuizQuestion::whereNotNull('code')
            ->whereRelation('lesson.course', 'language', 'php')
            ->get();
        $this->assertNotEmpty($questions);

        foreach ($questions as $question) {
            File::put($this->directory.'/snippet.php', "<?php\n\n".$question->code);
            $output = Process::path($this->directory)->run([PHP_BINARY, '-d', 'display_errors=1', 'snippet.php'])->output();

            // Some snippets end in an error on purpose; none may depend on code from another question.
            $this->assertDoesNotMatchRegularExpression('/not found|Undefined (variable|function)/', $output, $question->question);
        }
    }

    public function test_failed_checks_explain_what_was_expected(): void
    {
        $report = $this->runHarness(
            '<?php class Book { public function describe() { return "Dune"; } }',
            [['label' => 'describe', 'code' => "return expect_same((new Book)->describe(), 'Dune by Frank Herbert');"]],
        );

        $this->assertFalse($report['checks'][0]['passed']);
        $this->assertSame("Expected 'Dune by Frank Herbert', got 'Dune'.", $report['checks'][0]['message']);
    }

    public function test_exceptions_in_checks_fail_only_that_check(): void
    {
        $report = $this->runHarness('<?php class Cart {}', [
            ['label' => 'count', 'code' => 'return expect_same((new Cart)->count(), 0);'],
            ['label' => 'exists', 'code' => "return class_exists('Cart');"],
        ]);

        $this->assertSame([false, true], array_column($report['checks'], 'passed'));
        $this->assertStringContainsString('undefined method Cart::count()', $report['checks'][0]['message']);
    }

    public function test_syntax_errors_fail_every_check_and_are_reported(): void
    {
        $report = $this->runHarness('<?php class Book {', [
            ['label' => 'a', 'code' => 'return true;'],
            ['label' => 'b', 'code' => 'return true;'],
        ]);

        $this->assertFalse($report['loaded']);
        $this->assertStringContainsString('ParseError', $report['error']);
        $this->assertSame([false, false], array_column($report['checks'], 'passed'));
    }

    public function test_uncatchable_fatal_errors_still_produce_a_report(): void
    {
        $report = $this->runHarness('<?php class Book {} class Book {}', [['label' => 'a', 'code' => 'return true;']]);

        $this->assertFalse($report['loaded']);
        $this->assertStringContainsString('Cannot redeclare', $report['error']);
        $this->assertFalse($report['checks'][0]['passed']);
    }

    public function test_learner_output_is_captured_separately(): void
    {
        $report = $this->runHarness('<?php echo "hello";', [['label' => 'a', 'code' => 'return true;']]);

        $this->assertSame('hello', $report['output']);
        $this->assertTrue($report['checks'][0]['passed']);
    }
}
