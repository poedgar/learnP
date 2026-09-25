<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the main learnP API may run code here. It signs "timestamp.body" with
 * the shared RUNNER_SECRET; old timestamps are refused so requests can't be replayed.
 */
class VerifyRunnerSignature
{
    private const MAX_AGE_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('runner.secret');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        $valid = filled($secret)
            && is_string($timestamp) && ctype_digit($timestamp)
            && abs(now()->getTimestamp() - (int) $timestamp) <= self::MAX_AGE_SECONDS
            && is_string($signature)
            && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret), $signature);

        abort_unless($valid, 401, 'Invalid signature.');

        return $next($request);
    }
}
