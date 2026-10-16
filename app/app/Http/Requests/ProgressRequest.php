<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** Lessons keyed by lesson id and review cards keyed by item, as the apps store them. */
class ProgressRequest extends FormRequest
{
    public const MAX_ENTRIES = 2000;

    public const MAX_CODE_LENGTH = 100_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $code = ['nullable', 'string', 'max:'.self::MAX_CODE_LENGTH];

        return [
            'lessons' => ['present', 'array', 'max:'.self::MAX_ENTRIES],
            'lessons.*' => ['array:read,theoryPage,sandboxCode,quiz,challenges'],
            'lessons.*.read' => ['required', 'boolean'],
            'lessons.*.theoryPage' => ['nullable', 'integer', 'min:1'],
            'lessons.*.sandboxCode' => $code,
            'lessons.*.quiz' => ['present', 'array'],
            'lessons.*.quiz.*' => ['array:option,correct,correct_option,explanation'],
            'lessons.*.quiz.*.option' => ['required', 'integer'],
            'lessons.*.quiz.*.correct' => ['required', 'boolean'],
            'lessons.*.quiz.*.correct_option' => ['required', 'integer'],
            'lessons.*.quiz.*.explanation' => ['nullable', 'string', 'max:10000'],
            'lessons.*.challenges' => ['present', 'array'],
            'lessons.*.challenges.*' => ['array:passed,code'],
            'lessons.*.challenges.*.passed' => ['required', 'boolean'],
            'lessons.*.challenges.*.code' => $code,
            'cards' => ['present', 'array', 'max:'.self::MAX_ENTRIES],
            'cards.*' => ['array:due,interval,ease,reps,lapses'],
            'cards.*.due' => ['required', 'integer', 'min:0'],
            'cards.*.interval' => ['required', 'integer', 'min:0'],
            'cards.*.ease' => ['required', 'numeric', 'min:1'],
            'cards.*.reps' => ['required', 'integer', 'min:0'],
            'cards.*.lapses' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Each lesson with every field present, keyed by lesson id. Ids that aren't
     * whole numbers are dropped; the controller drops ids of lessons that don't exist.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lessons(): array
    {
        $lessons = [];
        foreach ($this->validated('lessons') as $id => $lesson) {
            if (! ctype_digit((string) $id)) {
                continue;
            }
            $lessons[(int) $id] = [
                'read' => (bool) $lesson['read'],
                'theoryPage' => isset($lesson['theoryPage']) ? (int) $lesson['theoryPage'] : null,
                'sandboxCode' => $lesson['sandboxCode'] ?? null,
                'quiz' => $lesson['quiz'],
                'challenges' => $lesson['challenges'],
            ];
        }

        return $lessons;
    }

    /**
     * Cards keyed by item ('quiz:12', 'challenge:21'); other keys are dropped.
     *
     * @return array<string, array<string, int|float>>
     */
    public function cards(): array
    {
        return collect($this->validated('cards'))
            ->filter(fn ($card, $key) => preg_match('/^(quiz|challenge):\d+$/', (string) $key) === 1)
            ->map(fn (array $card) => [
                'due' => (int) $card['due'],
                'interval' => (int) $card['interval'],
                'ease' => (float) $card['ease'],
                'reps' => (int) $card['reps'],
                'lapses' => (int) $card['lapses'],
            ])
            ->all();
    }
}
