<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Sandbox\SqlSandbox;

class LessonController extends Controller
{
    public function show(Course $course, Lesson $lesson, SqlSandbox $sandbox): LessonResource
    {
        $lesson->load(['course', 'quizQuestions', 'challenges']);

        $siblings = $course->lessons()->get(['id', 'slug', 'title', 'position']);
        $index = $siblings->search(fn (Lesson $sibling) => $sibling->is($lesson));
        $neighbour = fn (?Lesson $other) => $other?->only(['slug', 'title']);

        return (new LessonResource($lesson))->additional([
            'schema' => $lesson->dataset ? $sandbox->describe($lesson->dataset) : [],
            'navigation' => [
                'previous' => $neighbour($index > 0 ? $siblings[$index - 1] : null),
                'next' => $neighbour($siblings[$index + 1] ?? null),
            ],
        ]);
    }
}
