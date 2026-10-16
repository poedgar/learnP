<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunPhpRequest;
use App\Http\Requests\RunSqlRequest;
use App\Models\Lesson;
use App\Sandbox\PhpSandbox;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class SandboxController extends Controller
{
    /**
     * Runs learner code: SQL against a fresh copy of the lesson's dataset, PHP
     * on the runner. The form request is picked by the course's language.
     */
    public function __invoke(Lesson $lesson, SqlSandbox $sqlSandbox, PhpSandbox $phpSandbox): JsonResponse
    {
        abort_if($lesson->course->runsOnDevice(), 422, 'Code in this course runs on your device, not on the server.');

        if ($lesson->course->language === 'php') {
            return response()->json($phpSandbox->run(app(RunPhpRequest::class)->validated('code')));
        }

        abort_if($lesson->dataset === null, 404, 'This lesson has no SQL sandbox.');

        return response()->json($sqlSandbox->run($lesson->dataset, app(RunSqlRequest::class)->validated('sql')));
    }
}
