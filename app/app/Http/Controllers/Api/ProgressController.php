<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgressRequest;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\ReviewCard;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * A signed-in learner's progress and review schedules, so they follow the learner
 * between devices. The apps still keep a copy on the device and work without it.
 */
class ProgressController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->state($request->user()));
    }

    /** Saves the lessons and cards sent, each replacing what was stored. */
    public function update(ProgressRequest $request): Response
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $this->save($user, $this->existingLessons($request->lessons()), $request->cards());
        });

        return response()->noContent();
    }

    /**
     * Folds a device's progress into the account, as when signing in on a device that
     * was used signed out, and returns the combined progress for the device to keep.
     */
    public function merge(ProgressRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            $lessons = $this->existingLessons($request->lessons());
            $cards = $request->cards();

            $storedLessons = $user->lessonProgress()->whereIn('lesson_id', array_keys($lessons))->get()->keyBy('lesson_id');
            foreach ($lessons as $id => $lesson) {
                if ($stored = $storedLessons->get($id)) {
                    $lessons[$id] = LessonProgress::combine($stored->data, $lesson);
                }
            }

            $storedCards = $user->reviewCards()->whereIn('item_key', array_keys($cards))->get()->keyBy('item_key');
            foreach ($cards as $key => $card) {
                if ($stored = $storedCards->get($key)) {
                    $cards[$key] = ReviewCard::combine($stored->toClient(), $card);
                }
            }

            $this->save($user, $lessons, $cards);
        });

        return response()->json($this->state($user));
    }

    /**
     * @param  array<int, array<string, mixed>>  $lessons
     * @param  array<string, array<string, int|float>>  $cards
     */
    private function save(User $user, array $lessons, array $cards): void
    {
        foreach ($lessons as $id => $data) {
            $user->lessonProgress()->updateOrCreate(['lesson_id' => $id], ['data' => $data]);
        }

        foreach ($cards as $key => $card) {
            $user->reviewCards()->updateOrCreate(['item_key' => $key], $card);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lessons
     * @return array<int, array<string, mixed>>
     */
    private function existingLessons(array $lessons): array
    {
        $existing = Lesson::whereIn('id', array_keys($lessons))->pluck('id')->all();

        return array_intersect_key($lessons, array_flip($existing));
    }

    /**
     * @return array{lessons: object, cards: object}
     */
    private function state(User $user): array
    {
        return [
            'lessons' => (object) $user->lessonProgress()->get()
                ->mapWithKeys(fn (LessonProgress $progress) => [$progress->lesson_id => $progress->toClient()])
                ->all(),
            'cards' => (object) $user->reviewCards()->get()
                ->mapWithKeys(fn (ReviewCard $card) => [$card->item_key => $card->toClient()])
                ->all(),
        ];
    }
}
