<?php

namespace Database\Seeders;

class SqlInterviewQuestionsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'sql-interview-questions',
            'title' => 'SQL interview questions',
            'language' => 'sql',
            'description' => 'The classic SQL interview problems and the traps in them: ranking and top N, self-joins and hierarchies, duplicates and streaks, and aggregates with a twist.',
            'position' => 29,
        ];
    }

    protected function directory(): string
    {
        return 'sql-interview-questions';
    }

    protected function lessons(): array
    {
        return [
            '01-ranking-and-top-n',
            '02-self-joins-and-hierarchies',
            '03-duplicates-and-sequences',
            '04-tricky-aggregates',
        ];
    }

    protected function dataset(): ?string
    {
        return 'company';
    }
}
