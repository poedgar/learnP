<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

/**
 * Seeds a course whose lessons all live in database/content/{directory}: for
 * each basename in lessons(), a Markdown file (the theory) and a PHP file
 * (slug, title, summary, starter code, quiz and challenges).
 */
abstract class ContentCourseSeeder extends Seeder
{
    use SyncsLessonContent;

    /**
     * @return array{slug: string, title: string, language: string, label?: string, description: string, position: int}
     */
    abstract protected function course(): array;

    /** The folder under database/content holding the lesson files. */
    abstract protected function directory(): string;

    /**
     * The lesson basenames, in order.
     *
     * @return list<string>
     */
    abstract protected function lessons(): array;

    /** The SQL dataset every lesson runs on, or null for PHP courses. */
    protected function dataset(): ?string
    {
        return null;
    }

    public function run(): void
    {
        $details = $this->course();
        $course = Course::updateOrCreate(['slug' => $details['slug']], $details);

        foreach ($this->lessons() as $index => $name) {
            $definition = require database_path("content/{$this->directory()}/{$name}.php");

            $lesson = $course->lessons()->updateOrCreate(['slug' => $definition['slug']], [
                'title' => $definition['title'],
                'summary' => $definition['summary'],
                'content' => file_get_contents(database_path("content/{$this->directory()}/{$name}.md")),
                'dataset' => $this->dataset(),
                'starter_code' => $definition['starter_code'],
                'position' => $index + 1,
            ]);

            $this->syncByPosition($lesson->quizQuestions(), $this->positioned($definition['quiz']));
            $this->syncByPosition($lesson->challenges(), $this->positioned($definition['challenges']));
        }
    }
}
