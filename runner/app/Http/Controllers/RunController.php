<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunRequest;
use App\Runner\PhpRunner;
use Illuminate\Http\JsonResponse;

class RunController extends Controller
{
    public function __invoke(RunRequest $request, PhpRunner $runner): JsonResponse
    {
        $result = $runner->run($request->validated('files'), $request->validated('entry'));

        // Learner output can be any bytes; don't let one bad byte turn into a 500.
        return response()->json($result, options: JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
