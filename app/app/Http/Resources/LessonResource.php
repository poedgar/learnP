<?php

namespace App\Http\Resources;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'summary' => $this->summary,
            'position' => $this->position,
            'quiz_questions_count' => $this->whenCounted('quizQuestions'),
            'challenges_count' => $this->whenCounted('challenges'),
            'content' => $this->whenHas('content'),
            'starter_code' => $this->whenHas('starter_code'),
            'course' => new CourseResource($this->whenLoaded('course')),
            'quiz_questions' => QuizQuestionResource::collection($this->whenLoaded('quizQuestions')),
            'challenges' => ChallengeResource::collection($this->whenLoaded('challenges')),
        ];
    }
}
