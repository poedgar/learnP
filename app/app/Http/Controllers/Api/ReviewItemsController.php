<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewItemsRequest;
use App\Http\Resources\ChallengeResource;
use App\Http\Resources\QuizQuestionResource;
use App\Models\Challenge;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;

class ReviewItemsController extends Controller
{
    /**
     * The quiz questions and challenges a learner has passed, by id, for spaced
     * repetition. Progress lives on the device, so the app sends the ids it has;
     * ids that no longer exist are left out. Answers and solutions stay on the server.
     */
    public function __invoke(ReviewItemsRequest $request): JsonResponse
    {
        $questions = QuizQuestion::whereIn('id', $request->validated('quiz_questions'))->get();
        $challenges = Challenge::whereIn('id', $request->validated('challenges'))->get();

        $lessons = Lesson::with('course')
            ->whereIn('id', $questions->pluck('lesson_id')->merge($challenges->pluck('lesson_id'))->unique())
            ->get()
            ->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'position' => $lesson->position,
                'course' => $lesson->course->only(['slug', 'title', 'language', 'position']),
            ]);

        return response()->json([
            'quiz_questions' => $questions->map(fn (QuizQuestion $question) => [
                ...(new QuizQuestionResource($question))->resolve($request),
                'lesson_id' => $question->lesson_id,
            ]),
            'challenges' => $challenges->map(fn (Challenge $challenge) => [
                ...(new ChallengeResource($challenge))->resolve($request),
                'lesson_id' => $challenge->lesson_id,
            ]),
            'lessons' => $lessons,
        ]);
    }
}
