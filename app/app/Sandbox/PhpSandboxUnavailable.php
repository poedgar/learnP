<?php

namespace App\Sandbox;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * The runner could not be reached or answered with something unexpected. The
 * details are logged; learners get a message they can act on.
 */
class PhpSandboxUnavailable extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => 'The PHP sandbox is unavailable. Try again shortly.'], 503);
    }
}
