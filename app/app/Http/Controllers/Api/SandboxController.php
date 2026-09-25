<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunSqlRequest;
use App\Models\Lesson;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class SandboxController extends Controller
{
    /**
     * Runs learner SQL against a fresh copy of the lesson's dataset.
     */
    public function __invoke(RunSqlRequest $request, Lesson $lesson, SqlSandbox $sandbox): JsonResponse
    {
        abort_if($lesson->dataset === null, 404, 'This lesson has no SQL sandbox.');

        return response()->json($sandbox->run($lesson->dataset, $request->validated('sql')));
    }
}
