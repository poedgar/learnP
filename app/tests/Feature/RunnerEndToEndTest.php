<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Sandbox\HttpPhpSandbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Runs the seeded PHP challenges on a real runner, with all its restrictions.
 * Skipped unless RUNNER_E2E_URL and RUNNER_E2E_SECRET are set; see ../runner/README.md.
 */
#[Group('runner')]
class RunnerEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function sandbox(): HttpPhpSandbox
    {
        $url = env('RUNNER_E2E_URL');
        $secret = env('RUNNER_E2E_SECRET');

        if (blank($url) || blank($secret)) {
            $this->markTestSkipped('Set RUNNER_E2E_URL and RUNNER_E2E_SECRET to run against a real runner.');
        }

        return new HttpPhpSandbox($url, $secret, resource_path('php/check-harness.php'));
    }

    public function test_a_plain_run_returns_output(): void
    {
        $result = $this->sandbox()->run('echo "hello";');

        $this->assertSame('hello', $result['output']);
        $this->assertFalse($result['fatal']);
    }

    public function test_blocked_functions_are_reported_as_fatal_errors(): void
    {
        $result = $this->sandbox()->run('echo exec("id");');

        $this->assertTrue($result['fatal']);
        $this->assertStringContainsString('undefined function exec()', $result['output']);
    }

    public function test_every_php_challenge_solution_passes_on_the_runner(): void
    {
        $sandbox = $this->sandbox();

        foreach (Challenge::whereNotNull('checks')->whereRelation('lesson.course', 'language', 'php')->get() as $challenge) {
            $result = $sandbox->check($challenge->solution, $challenge->checks);

            $this->assertTrue($result['passed'], "{$challenge->title}: ".json_encode($result));
        }
    }

    public function test_every_php_challenge_starter_fails_on_the_runner(): void
    {
        $sandbox = $this->sandbox();

        foreach (Challenge::whereNotNull('checks')->whereRelation('lesson.course', 'language', 'php')->get() as $challenge) {
            $this->assertFalse($sandbox->check($challenge->starter_code, $challenge->checks)['passed'], $challenge->title);
        }
    }
}
