<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RunApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['runner.secret' => 'test-secret']);
    }

    private function signedPost(array $body, ?int $timestamp = null, string $secret = 'test-secret', ?string $sentBody = null): TestResponse
    {
        $json = json_encode($body);
        $timestamp ??= now()->getTimestamp();

        return $this->call('POST', '/run', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIGNATURE' => hash_hmac('sha256', $timestamp.'.'.$json, $secret),
        ], content: $sentBody ?? $json);
    }

    private function runBody(string $code): array
    {
        return ['files' => ['main.php' => $code], 'entry' => 'main.php'];
    }

    public function test_signed_requests_run_the_code(): void
    {
        $this->signedPost($this->runBody('<?php echo "hi";'))
            ->assertOk()
            ->assertExactJson(['stdout' => 'hi', 'exitCode' => 0, 'timedOut' => false, 'truncated' => false]);
    }

    public function test_output_that_is_not_valid_utf8_is_replaced_instead_of_failing(): void
    {
        $this->signedPost($this->runBody('<?php echo "a\xffb";'))
            ->assertOk()
            ->assertJsonPath('stdout', "a\u{FFFD}b");
    }

    public function test_unsigned_requests_are_rejected(): void
    {
        $this->postJson('/run', $this->runBody('<?php echo 1;'))->assertUnauthorized();
    }

    public function test_requests_signed_with_another_secret_are_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), secret: 'wrong')->assertUnauthorized();
    }

    public function test_old_requests_are_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), timestamp: now()->getTimestamp() - 61)->assertUnauthorized();
    }

    public function test_a_body_changed_after_signing_is_rejected(): void
    {
        $this->signedPost($this->runBody('<?php echo 1;'), sentBody: json_encode($this->runBody('<?php echo 2;')))->assertUnauthorized();
    }

    public function test_nothing_is_accepted_when_no_secret_is_configured(): void
    {
        config(['runner.secret' => null]);

        $this->signedPost($this->runBody('<?php echo 1;'), secret: '')->assertUnauthorized();
    }

    public function test_file_names_must_be_plain(): void
    {
        $this->signedPost(['files' => ['../main.php' => '<?php'], 'entry' => '../main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_file_names_cannot_start_with_a_dash(): void
    {
        $this->signedPost(['files' => ['-n.php' => '<?php echo 1;'], 'entry' => '-n.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_the_entry_must_be_one_of_the_files(): void
    {
        $this->signedPost(['files' => ['main.php' => '<?php'], 'entry' => 'other.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entry');
    }

    public function test_the_number_of_files_is_limited(): void
    {
        $files = collect(range(1, 6))->mapWithKeys(fn (int $i) => ["f$i.php" => '<?php'])->all();

        $this->signedPost(['files' => $files, 'entry' => 'f1.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_file_contents_must_be_strings(): void
    {
        $this->signedPost(['files' => ['main.php' => ['nested']], 'entry' => 'main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }

    public function test_the_total_size_is_limited(): void
    {
        $this->signedPost(['files' => ['main.php' => '<?php '.str_repeat('x', 100_000)], 'entry' => 'main.php'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('files');
    }
}
