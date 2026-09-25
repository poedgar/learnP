<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Challenge extends Model
{
    protected $fillable = ['title', 'prompt', 'starter_code', 'solution', 'checks', 'hint', 'order_matters', 'position'];

    protected $hidden = ['solution'];

    protected function casts(): array
    {
        return [
            'order_matters' => 'boolean',
            'checks' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
