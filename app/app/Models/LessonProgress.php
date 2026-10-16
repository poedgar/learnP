<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One lesson's progress for a signed-in learner. `data` has the shape the apps keep
 * on the device: { read, theoryPage?, sandboxCode, quiz: { [questionId]: … }, challenges: { [challengeId]: { passed, code } } }.
 */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = ['user_id', 'lesson_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Combines two independent histories of one lesson, as when a device that was used
     * signed out joins an account. Nothing passed on either side is lost; where both have
     * something, the device's own copy wins.
     *
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function combine(array $stored, array $incoming): array
    {
        $challenges = $stored['challenges'];
        foreach ($incoming['challenges'] as $id => $challenge) {
            $keepStored = ($challenges[$id]['passed'] ?? false) && ! $challenge['passed'];
            $challenges[$id] = $keepStored ? $challenges[$id] : $challenge;
        }

        return [
            'read' => $stored['read'] || $incoming['read'],
            'theoryPage' => max($stored['theoryPage'], $incoming['theoryPage']),
            'sandboxCode' => $incoming['sandboxCode'] ?? $stored['sandboxCode'],
            'quiz' => $incoming['quiz'] + $stored['quiz'],
            'challenges' => $challenges,
        ];
    }

    /**
     * The data as the apps expect it: maps stay JSON objects even when empty.
     *
     * @return array<string, mixed>
     */
    public function toClient(): array
    {
        return array_filter([
            ...$this->data,
            'quiz' => (object) $this->data['quiz'],
            'challenges' => (object) $this->data['challenges'],
        ], fn ($value, $key) => $key !== 'theoryPage' || $value !== null, ARRAY_FILTER_USE_BOTH);
    }
}
