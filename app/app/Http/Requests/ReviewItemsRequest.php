<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewItemsRequest extends FormRequest
{
    /** More ids than anyone could have passed, but a cap on how much one request loads. */
    public const MAX_IDS = 2000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quiz_questions' => ['present', 'array', 'max:'.self::MAX_IDS],
            'quiz_questions.*' => ['integer'],
            'challenges' => ['present', 'array', 'max:'.self::MAX_IDS],
            'challenges.*' => ['integer'],
        ];
    }
}
