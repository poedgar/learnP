<?php

use App\Models\Lesson;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content:export {path : The JSON file to write} {--language=php : Only courses in this language}', function () {
    $lessons = Lesson::whereRelation('course', 'language', $this->option('language'))
        ->with(['course', 'quizQuestions', 'challenges'])
        ->orderBy('course_id')->orderBy('position')
        ->get()
        ->map(fn (Lesson $lesson) => [
            'name' => "{$lesson->course->slug}/{$lesson->slug}",
            'content' => $lesson->content,
            'starter' => $lesson->starter_code,
            'quiz' => $lesson->quizQuestions->pluck('code')->filter()->values(),
            'challenges' => $lesson->challenges->map(fn ($challenge) => [
                'title' => $challenge->title,
                'solution' => $challenge->solution,
                'starter' => $challenge->starter_code,
                'checks' => $challenge->checks,
            ]),
        ]);

    // Written to a file rather than printed, so nothing reformats the code on the way.
    file_put_contents($this->argument('path'), $lessons->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $this->info("Wrote {$lessons->count()} lessons to {$this->argument('path')}.");
})->purpose('Write the seeded lessons as JSON, for tests/js/verify-php-in-browser.mjs');
