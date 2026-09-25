<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAnswerController extends Controller
{
    public function __invoke(Request $request, QuizQuestion $question): JsonResponse
    {
        $validated = $request->validate([
            'option' => ['required', 'integer', 'min:0', 'max:'.(count($question->options) - 1)],
        ]);

        return response()->json([
            'correct' => (int) $validated['option'] === $question->correct_option,
            'correct_option' => $question->correct_option,
            'explanation' => $question->explanation,
        ]);
    }
}
