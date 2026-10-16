<?php

namespace Database\Seeders;

class AdvancedSqlCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'advanced-sql',
            'title' => 'Advanced SQL',
            'language' => 'sql',
            'description' => 'Go beyond the basics: set operations, NULL logic, trees, everyday query patterns, JSON, indexes, views, triggers and full-text search.',
            'position' => 4,
        ];
    }

    protected function directory(): string
    {
        return 'advanced-sql';
    }

    protected function lessons(): array
    {
        return [
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
    }

    protected function dataset(): ?string
    {
        return 'bookshop_plus';
    }
}
