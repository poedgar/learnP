<?php

use App\Models\Lesson;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

Artisan::command('stats:report {--min=20 : Ignore items with fewer attempts than this} {--limit=10 : How many to list}', function () {
    $min = (int) $this->option('min');
    $limit = (int) $this->option('limit');
    $where = fn ($row) => "{$row->course} / {$row->lesson}";

    $challenges = DB::table('challenge_stats')
        ->join('challenges', 'challenges.id', '=', 'challenge_stats.challenge_id')
        ->join('lessons', 'lessons.id', '=', 'challenges.lesson_id')
        ->join('courses', 'courses.id', '=', 'lessons.course_id')
        ->where('challenge_stats.checks', '>=', $min)
        ->select('challenges.title', 'lessons.slug as lesson', 'courses.slug as course', 'challenge_stats.checks', 'challenge_stats.passes', 'challenge_stats.first_try_passes', 'challenge_stats.hint_views', 'challenge_stats.solution_views')
        ->get();

    $this->info('Hardest challenges (lowest share of checks that pass)');
    $this->table(['Challenge', 'Where', 'Checks', 'Pass rate', 'First try', 'Hints', 'Solutions'], $challenges
        ->sortBy(fn ($row) => $row->passes / $row->checks)
        ->take($limit)
        ->map(fn ($row) => [$row->title, $where($row), $row->checks, round(100 * $row->passes / $row->checks).'%', $row->first_try_passes, $row->hint_views, $row->solution_views])
        ->all());

    $this->info('Most given up on (solution views per check)');
    $this->table(['Challenge', 'Where', 'Checks', 'Solutions'], $challenges
        ->filter(fn ($row) => $row->solution_views > 0)
        ->sortByDesc(fn ($row) => $row->solution_views / $row->checks)
        ->take($limit)
        ->map(fn ($row) => [$row->title, $where($row), $row->checks, $row->solution_views])
        ->all());

    $questions = DB::table('quiz_question_stats')
        ->join('quiz_questions', 'quiz_questions.id', '=', 'quiz_question_stats.quiz_question_id')
        ->join('lessons', 'lessons.id', '=', 'quiz_questions.lesson_id')
        ->join('courses', 'courses.id', '=', 'lessons.course_id')
        ->where('quiz_question_stats.answers', '>=', $min)
        ->select('quiz_questions.question', 'lessons.slug as lesson', 'courses.slug as course', 'quiz_question_stats.answers', 'quiz_question_stats.correct')
        ->get();

    $this->info('Most often answered wrongly');
    $this->table(['Question', 'Where', 'Answers', 'Correct'], $questions
        ->sortBy(fn ($row) => $row->correct / $row->answers)
        ->take($limit)
        ->map(fn ($row) => [Str::limit($row->question, 60), $where($row), $row->answers, round(100 * $row->correct / $row->answers).'%'])
        ->all());
})->purpose('List the challenges and quiz questions learners struggle with most');
