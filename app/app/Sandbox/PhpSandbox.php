<?php

namespace App\Sandbox;

/**
 * Runs learner PHP on the server. The web app runs PHP in the browser instead
 * (resources/js/lib/php); these results use the same shapes as runScript()
 * and checkChallenge() there.
 */
interface PhpSandbox
{
    public const MAX_CODE_LENGTH = 20_000;

    /**
     * @return array{output: string, fatal: bool, durationMs: int}
     */
    public function run(string $code): array;

    /**
     * Runs the code, then the challenge's checks, with resources/php/check-harness.php.
     *
     * @param  list<array{label: string, code: string}>  $checks
     * @return array{passed: bool, loaded: bool, error: ?string, output: string, checks: list<array{label: string, passed: bool, message: ?string}>}
     */
    public function check(string $code, array $checks): array;
}
