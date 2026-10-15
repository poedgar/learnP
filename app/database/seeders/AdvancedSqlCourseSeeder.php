<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class AdvancedSqlCourseSeeder extends Seeder
{
    use SyncsLessonContent;

    /**
     * The lessons, in order. Each is a Markdown file (the theory) and a PHP
     * file (title, summary, starter code, quiz and challenges) in
     * database/content/advanced-sql, sharing this basename.
     */
    private const LESSONS = [
        '01-stacking-and-pairing',
        '02-the-truth-about-null',
        '03-trees-and-hierarchies',
        '04-everyday-query-patterns',
        '05-upsert-and-returning',
        '06-working-with-json',
        '07-designing-a-schema',
        '08-indexes-and-query-plans',
        '09-views-triggers-and-generated-columns',
        '10-full-text-search',
    ];

    public function run(): void
    {
        $course = Course::updateOrCreate(['slug' => 'advanced-sql'], [
            'title' => 'Advanced SQL',
            'language' => 'sql',
            'description' => 'Go beyond the basics: set operations, NULL logic, trees, everyday query patterns, JSON, indexes, views, triggers and full-text search.',
            'position' => 3,
        ]);

        foreach (self::LESSONS as $index => $name) {
            $definition = require database_path("content/advanced-sql/{$name}.php");

            $lesson = $course->lessons()->updateOrCreate(['slug' => $definition['slug']], [
                'title' => $definition['title'],
                'summary' => $definition['summary'],
                'content' => file_get_contents(database_path("content/advanced-sql/{$name}.md")),
                'dataset' => 'bookshop_plus',
                'starter_code' => $definition['starter_code'],
                'position' => $index + 1,
            ]);

            $this->syncByPosition($lesson->quizQuestions(), $this->positioned($definition['quiz']));
            $this->syncByPosition($lesson->challenges(), $this->positioned($definition['challenges']));
        }
    }
}
