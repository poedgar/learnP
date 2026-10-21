<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A learning path: courses in the order to take them. */
class Track extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'position'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class)->withPivot('position')->orderByPivot('position');
    }
}
