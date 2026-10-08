<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunPhpRequest;
use App\Http\Requests\RunSqlRequest;
use App\Models\Challenge;
use App\Sandbox\PhpSandbox;
use App\Sandbox\SqlSandbox;
use Illuminate\Http\JsonResponse;

class ChallengeCheckController extends Controller
{
    /**
     * SQL answers are compared with the reference solution's result; PHP
     * answers are run against the challenge's checks.
     */
    public function __invoke(Challenge $challenge, SqlSandbox $sqlSandbox, PhpSandbox $phpSandbox): JsonResponse
    {
        if ($challenge->lesson->course->language === 'php') {
            return response()->json($phpSandbox->check(app(RunPhpRequest::class)->validated('code'), $challenge->checks));
        }

        abort_if($challenge->lesson->dataset === null, 404, 'This challenge has no SQL sandbox.');

        return response()->json($sqlSandbox->check(
            $challenge->lesson->dataset,
            app(RunSqlRequest::class)->validated('sql'),
            $challenge->solution,
            $challenge->order_matters,
            $challenge->check_sql,
        ));
    }
}
