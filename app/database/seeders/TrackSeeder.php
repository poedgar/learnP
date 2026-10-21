<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Track;
use Illuminate\Database\Seeder;

/** Learning paths. Runs after the course seeders; courses that don't exist yet are skipped. */
class TrackSeeder extends Seeder
{
    public const TRACKS = [
        [
            'slug' => 'php-developer',
            'title' => 'PHP developer',
            'description' => 'From your first variable to production Laravel apps: the language, objects, testing, clean code, security, the framework and shipping it.',
            'courses' => ['php-basics', 'php-oop', 'php-oop-advanced', 'php-testing', 'php-clean-code', 'php-security', 'laravel-basics', 'laravel-advanced', 'laravel-testing', 'rest-apis', 'caching-and-redis', 'docker', 'deploying'],
        ],
        [
            'slug' => 'computer-science',
            'title' => 'Thinking like a programmer',
            'description' => 'The ideas that outlast any framework: design patterns, algorithms and data structures, and functional programming.',
            'courses' => ['php-design-patterns', 'php-algorithms', 'functional-php'],
        ],
        [
            'slug' => 'front-end',
            'title' => 'Front end',
            'description' => 'Build what people see and use: HTML and CSS, JavaScript, the DOM, asynchronous code, TypeScript and regular expressions.',
            'courses' => ['html-css', 'js-basics', 'js-async', 'js-dom', 'typescript', 'regex'],
        ],
        [
            'slug' => 'data',
            'title' => 'Data',
            'description' => 'Ask questions of data with SQL, from the first SELECT to window functions, then practise on realistic problems.',
            'courses' => ['sql', 'advanced-sql', 'sql-practice-analytics', 'sql-practice-shop', 'sql-practice-library', 'sql-interview-questions'],
        ],
        [
            'slug' => 'tools',
            'title' => 'Developer tools',
            'description' => 'The tools every developer uses every day: Git for history and teamwork, Docker for running apps anywhere.',
            'courses' => ['git', 'docker'],
        ],
    ];

    public function run(): void
    {
        $courses = Course::pluck('id', 'slug');

        foreach (self::TRACKS as $position => $definition) {
            $track = Track::updateOrCreate(['slug' => $definition['slug']], [
                'title' => $definition['title'],
                'description' => $definition['description'],
                'position' => $position + 1,
            ]);

            $sync = [];
            foreach ($definition['courses'] as $order => $slug) {
                if (isset($courses[$slug])) {
                    $sync[$courses[$slug]] = ['position' => $order + 1];
                }
            }
            $track->courses()->sync($sync);
        }
    }
}
