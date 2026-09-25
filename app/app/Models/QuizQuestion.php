<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = ['question', 'code', 'options', 'correct_option', 'explanation', 'position'];

    protected $hidden = ['correct_option', 'explanation'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
