<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunSqlRequest;
use App\Models\Challenge;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class ChallengeCheckController extends Controller
{
    /**
     * Runs the learner's answer and compares its result with the reference solution's.
     */
    public function __invoke(RunSqlRequest $request, Challenge $challenge, SqlSandbox $sandbox): JsonResponse
    {
        abort_if($challenge->lesson->dataset === null, 404, 'Only SQL challenges are checked on the server.');

        return response()->json($sandbox->check(
            $challenge->lesson->dataset,
            $request->validated('sql'),
            $challenge->solution,
            $challenge->order_matters,
        ));
    }
}
