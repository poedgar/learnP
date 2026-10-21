<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'language', 'label', 'position'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class)->withPivot('position');
    }

    /** JavaScript, TypeScript, HTML and Git (a simulated one) run on the learner's device, never on the server. */
    public function runsOnDevice(): bool
    {
        return in_array($this->language, ['javascript', 'typescript', 'html', 'git'], true);
    }
}
