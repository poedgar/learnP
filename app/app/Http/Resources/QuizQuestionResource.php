<?php

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The correct answer stays on the server; learners submit answers to
 * QuizAnswerController to find out whether they were right.
 *
 * @mixin QuizQuestion
 */
class QuizQuestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'code' => $this->code,
            'options' => $this->options,
        ];
    }
}
