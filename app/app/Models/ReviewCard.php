<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A spaced-repetition schedule for one passed item ('quiz:12', 'challenge:21'); see resources/js/review.js. */
class ReviewCard extends Model
{
    public const FIELDS = ['due', 'interval', 'ease', 'reps', 'lapses'];

    protected $fillable = ['user_id', 'item_key', 'due', 'interval', 'ease', 'reps', 'lapses'];

    protected function casts(): array
    {
        return [
            'due' => 'integer',
            'interval' => 'integer',
            'ease' => 'float',
            'reps' => 'integer',
            'lapses' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Combines two schedules for one item by keeping the one due sooner, so joining
     * an account never pushes a review later than either device planned it.
     *
     * @param  array<string, int|float>  $stored
     * @param  array<string, int|float>  $incoming
     * @return array<string, int|float>
     */
    public static function combine(array $stored, array $incoming): array
    {
        return $stored['due'] < $incoming['due'] ? $stored : $incoming;
    }

    /**
     * @return array<string, int|float>
     */
    public function toClient(): array
    {
        return $this->only(self::FIELDS);
    }
}
