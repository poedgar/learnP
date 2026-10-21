<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Anonymous counts of how learners get on: which challenges they fail, give up on or need
 * help with. Nothing about who; see php artisan stats:report.
 */
class LearningEventController extends Controller
{
    public function check(Request $request, Challenge $challenge): Response
    {
        $validated = $request->validate(['passed' => ['required', 'boolean'], 'attempt' => ['required', 'integer', 'min:1']]);
        $passed = (bool) $validated['passed'];

        self::bump('challenge_stats', 'challenge_id', $challenge->id, [
            'checks' => 1,
            'passes' => $passed ? 1 : 0,
            'first_try_passes' => $passed && (int) $validated['attempt'] === 1 ? 1 : 0,
        ]);

        return response()->noContent();
    }

    public function hint(Challenge $challenge): Response
    {
        self::bump('challenge_stats', 'challenge_id', $challenge->id, ['hint_views' => 1]);

        return response()->noContent();
    }

    /** The reference solution, offered after several failed attempts. Showing it is counted. */
    public function solution(Challenge $challenge): JsonResponse
    {
        self::bump('challenge_stats', 'challenge_id', $challenge->id, ['solution_views' => 1]);

        return response()->json(['solution' => $challenge->solution]);
    }

    public static function recordQuizAnswer(QuizQuestion $question, bool $correct): void
    {
        self::bump('quiz_question_stats', 'quiz_question_id', $question->id, ['answers' => 1, 'correct' => $correct ? 1 : 0]);
    }

    /**
     * Adds to a stats row's counters, creating the row the first time.
     *
     * @param  array<string, int>  $amounts
     */
    private static function bump(string $table, string $key, int $id, array $amounts): void
    {
        DB::table($table)->insertOrIgnore([$key => $id, 'created_at' => now(), 'updated_at' => now()]);

        $increments = array_filter($amounts);
        if ($increments !== []) {
            DB::table($table)->where($key, $id)->incrementEach($increments, ['updated_at' => now()]);
        }
    }
}
