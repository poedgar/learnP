<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\QuizQuestion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\Concerns\RunsPhpScripts;
use Tests\TestCase;

/**
 * PHP challenges are checked in the browser, by resources/php/check-harness.php
 * running in WebAssembly. These tests run the same harness with the PHP CLI.
 */
class PhpChallengeHarnessTest extends TestCase
{
    use RefreshDatabase, RunsPhpScripts;

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

    /**
     * Runs each code through the harness with its challenge's checks, in parallel.
     *
     * @param  iterable<Challenge>  $challenges
     * @return array<int, array{challenge: Challenge, report: ?array}>
     */
    private function runHarnessFor(iterable $challenges, callable $code): array
    {
        $harness = File::get(resource_path('php/check-harness.php'));
        $scripts = [];
        $byKey = [];
        foreach ($challenges as $challenge) {
            $scripts[$challenge->id] = [
                'files' => ['harness.php' => $harness, 'main.php' => $code($challenge), 'checks.json' => json_encode($challenge->checks)],
                'entry' => 'harness.php',
            ];
            $byKey[$challenge->id] = $challenge;
        }

        $results = [];
        foreach ($this->runPhpScripts($scripts) as $id => $result) {
            [, $report] = explode("\n@@LEARNP_REPORT@@\n", $result['output'], 2) + [1 => null];
            $this->assertNotNull($report, "{$byKey[$id]->title}: the harness printed no report: {$result['output']}{$result['errors']}");
            $results[$id] = ['challenge' => $byKey[$id], 'report' => json_decode($report, true)];
        }

        return $results;
    }

    public function test_every_php_challenge_solution_passes_all_its_checks(): void
    {
        // Every PHP challenge is checked by code; none may be missing its checks.
        $this->assertFalse(Challenge::whereNull('checks')->whereRelation('lesson.course', 'language', 'php')->exists());
        $challenges = Challenge::whereNotNull('checks')->whereRelation('lesson.course', 'language', 'php')->get();
        $this->assertNotEmpty($challenges);

        foreach ($this->runHarnessFor($challenges, fn (Challenge $c) => $c->solution) as ['challenge' => $challenge, 'report' => $report]) {
            $this->assertTrue($report['loaded'], "{$challenge->title}: {$report['error']}");
            foreach ($report['checks'] as $check) {
                $this->assertTrue($check['passed'], "{$challenge->title} / {$check['label']}: {$check['message']}");
            }
        }
    }

    public function test_every_php_challenge_starter_fails_at_least_one_check(): void
    {
        $challenges = Challenge::whereNotNull('checks')->whereRelation('lesson.course', 'language', 'php')->get();

        foreach ($this->runHarnessFor($challenges, fn (Challenge $c) => $c->starter_code) as ['challenge' => $challenge, 'report' => $report]) {

            $this->assertContains(false, array_column($report['checks'], 'passed'), "{$challenge->title} passes without any work.");
        }
    }

    public function test_php_quiz_snippets_run_on_their_own_in_the_sandbox(): void
    {
        $questions = QuizQuestion::whereNotNull('code')
            ->whereRelation('lesson.course', 'language', 'php')
            ->get();
        $this->assertNotEmpty($questions);

        $outputs = $this->runPhpScripts($questions->mapWithKeys(fn (QuizQuestion $q) => [
            $q->id => ['files' => ['snippet.php' => "<?php\n\n".$q->code], 'entry' => 'snippet.php'],
        ])->all());

        foreach ($questions as $question) {
            // Some snippets end in an error on purpose; none may depend on code from another question.
            $this->assertDoesNotMatchRegularExpression('/not found|Undefined (variable|function)/', $outputs[$question->id]['output'], $question->question);
        }
    }

    public function test_list_checks_name_the_item_that_differs(): void
    {
        $challenge = Challenge::where('title', 'Where is it shelved?')->firstOrFail();
        $report = $this->runHarness(<<<'PHP'
            <?php
            enum Shelf { case Front; case Window; case Back; case Stockroom; }
            class Book { public function __construct(public string $title, public Shelf $shelf) {} }
            PHP, $challenge->checks);

        $this->assertStringContainsString('Stockroom', implode(' ', array_filter(array_column($report['checks'], 'message'))));
    }

    public function test_a_narrator_shared_by_every_audiobook_fails(): void
    {
        $challenge = Challenge::where('title', 'Read by a narrator')->firstOrFail();
        $report = $this->runHarness(<<<'PHP'
            <?php
            class Book
            {
                public function __construct(public string $title, public string $author) {}

                public function describe(): string
                {
                    return $this->title . ' by ' . $this->author;
                }
            }

            class AudioBook extends Book
            {
                private static string $lastNarrator = '';

                public function __construct(string $title, string $author, public string $narrator)
                {
                    parent::__construct($title, $author);
                    self::$lastNarrator = $narrator;
                }

                public function describe(): string
                {
                    return parent::describe() . ', read by ' . self::$lastNarrator;
                }
            }
            PHP, $challenge->checks);

        $this->assertContains(false, array_column($report['checks'], 'passed'));
    }

    public function test_traits_can_be_listed_in_either_order(): void
    {
        $challenge = Challenge::where('title', 'Settle the conflict')->firstOrFail();
        $report = $this->runHarness(str_replace('use CatalogueEntry, ShelfLabel {', 'use ShelfLabel, CatalogueEntry {', $challenge->solution), $challenge->checks);

        $this->assertNotContains(false, array_column($report['checks'], 'passed'), json_encode($report['checks']));
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

    public function test_checks_can_read_the_learners_output_variables_and_code(): void
    {
        $report = $this->runHarness('<?php $title = "Dune"; echo "Reading $title";', [
            ['label' => 'output', 'code' => "return expect_same(learnp_output(), 'Reading Dune');"],
            ['label' => 'variable', 'code' => "return expect_same(\$GLOBALS['title'], 'Dune');"],
            ['label' => 'code', 'code' => "return str_contains(learnp_code(), 'echo') ?: 'no echo';"],
        ]);

        $this->assertSame([true, true, true], array_column($report['checks'], 'passed'), json_encode($report['checks']));
        $this->assertSame('Reading Dune', $report['output']);
    }

    public function test_checks_can_run_a_variant_of_the_learners_script(): void
    {
        $report = $this->runHarness('<?php $n = 2; echo $n * 10;', [
            ['label' => 'variant', 'code' => "return expect_same(learnp_run(str_replace('\$n = 2', '\$n = 5', learnp_code())), '50');"],
            ['label' => 'original output is untouched', 'code' => "return expect_same(learnp_output(), '20');"],
        ]);

        $this->assertSame([true, true], array_column($report['checks'], 'passed'), json_encode($report['checks']));
        $this->assertSame('20', $report['output']);
    }

    public function test_failed_array_checks_show_short_arrays(): void
    {
        $report = $this->runHarness('<?php $prices = [9.99, 20.0];', [
            ['label' => 'short', 'code' => "return expect_same(\$GLOBALS['prices'], [9.99, 19.99]);"],
            ['label' => 'long', 'code' => 'return expect_same(range(1, 100), []);'],
        ]);

        $this->assertSame('Expected [9.99,19.99], got [9.99,20.0].', $report['checks'][0]['message']);
        $this->assertSame('Expected [], got an array with 100 items.', $report['checks'][1]['message']);
    }

    public function test_checks_can_read_dockerfiles(): void
    {
        $report = $this->runHarness(<<<'PHP'
            <?php
            $dockerfile = <<<'DOCKERFILE'
            # syntax=docker/dockerfile:1
            FROM php:8.4-cli AS base

            run apt-get update \
                && apt-get install -y   git \
                # a comment inside a continuation is skipped
                && rm -rf /var/lib/apt/lists/*
            CMD ["php", "-a"]
            DOCKERFILE;
            PHP, [
            ['label' => 'parsed', 'code' => <<<'PHP'
                return expect_same(learnp_dockerfile($GLOBALS['dockerfile']), [
                    ['instruction' => 'FROM', 'args' => 'php:8.4-cli AS base'],
                    ['instruction' => 'RUN', 'args' => 'apt-get update && apt-get install -y git && rm -rf /var/lib/apt/lists/*'],
                    ['instruction' => 'CMD', 'args' => '["php", "-a"]'],
                ]);
                PHP],
        ]);

        $this->assertSame([true], array_column($report['checks'], 'passed'), json_encode($report['checks']));
    }

    public function test_checks_can_split_shell_commands(): void
    {
        $report = $this->runHarness('<?php $command = "docker run -d \\\\\n  -e \'GREETING=hello world\' -e NOTE=\"say \\\\\"hi\\\\\"\" --name web nginx:1.27";', [
            ['label' => 'split', 'code' => <<<'PHP'
                return expect_same(learnp_shell_words($GLOBALS['command']), ['docker', 'run', '-d', '-e', 'GREETING=hello world', '-e', 'NOTE=say "hi"', '--name', 'web', 'nginx:1.27']);
                PHP],
            ['label' => 'empty quotes are a word', 'code' => "return expect_same(learnp_shell_words(\"echo '' a\\\\ b\"), ['echo', '', 'a b']);"],
        ]);

        $this->assertSame([true, true], array_column($report['checks'], 'passed'), json_encode($report['checks']));
    }

    public function test_checks_can_read_docker_run_commands(): void
    {
        $report = $this->runHarness('<?php $a = "docker run -d -p 8080:80 --name=web -e A=1 --env B=2 nginx:1.27"; $b = "docker container run --rm -it -v data:/data php:8.4-cli php -a"; $c = "docker ps";', [
            ['label' => 'long and short', 'code' => "return expect_same(learnp_docker_run(\$GLOBALS['a']), ['options' => ['publish' => ['8080:80'], 'name' => ['web'], 'env' => ['A=1', 'B=2']], 'flags' => ['detach'], 'image' => 'nginx:1.27', 'args' => []]);"],
            ['label' => 'combined flags and a command', 'code' => "return expect_same(learnp_docker_run(\$GLOBALS['b']), ['options' => ['volume' => ['data:/data']], 'flags' => ['rm', 'interactive', 'tty'], 'image' => 'php:8.4-cli', 'args' => ['php', '-a']]);"],
            ['label' => 'other commands', 'code' => "return expect_same(learnp_docker_run(\$GLOBALS['c']), null);"],
        ]);

        $this->assertSame([true, true, true], array_column($report['checks'], 'passed'), json_encode($report['checks']));
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
