<?php

namespace Database\Seeders;

class SqlPracticeAnalyticsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'sql-practice-analytics',
            'title' => 'SQL practice: product analytics',
            'language' => 'sql',
            'description' => 'Answer the questions a product team asks of a reading app\'s data: growth and revenue, funnels and conversion, retention and cohorts, and engagement.',
            'position' => 26,
        ];
    }

    protected function directory(): string
    {
        return 'sql-practice-analytics';
    }

    protected function lessons(): array
    {
        return [
            '01-growth-and-revenue',
            '02-funnels-and-conversion',
            '03-retention-and-cohorts',
            '04-sessions-and-engagement',
        ];
    }

    protected function dataset(): ?string
    {
        return 'analytics';
    }
}
