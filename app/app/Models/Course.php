<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'language', 'position'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /** JavaScript, TypeScript and HTML run on the learner's device (a sandboxed frame), never on the server. */
    public function runsOnDevice(): bool
    {
        return in_array($this->language, ['javascript', 'typescript', 'html'], true);
    }
}
