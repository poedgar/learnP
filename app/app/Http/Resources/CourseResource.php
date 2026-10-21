<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Course */
class CourseResource extends JsonResource
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
            'description' => $this->description,
            'language' => $this->language,
            'label' => $this->label,
            'lessons_count' => $this->whenCounted('lessons'),
            'lessons' => LessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
