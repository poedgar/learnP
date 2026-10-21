<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        // Each lesson's id and counts are enough for the apps to show progress per course.
        return CourseResource::collection(
            Course::query()
                ->withCount('lessons')
                ->with(['lessons' => fn ($query) => $query
                    ->select(['id', 'course_id', 'slug', 'title', 'position'])
                    ->withCount(['quizQuestions', 'challenges']),
                ])
                ->orderBy('position')
                ->get()
        );
    }

    public function show(Course $course): CourseResource
    {
        return new CourseResource(
            $course->load(['lessons' => fn ($query) => $query
                ->select(['id', 'course_id', 'slug', 'title', 'summary', 'position'])
                ->withCount(['quizQuestions', 'challenges']),
            ])
        );
    }
}
