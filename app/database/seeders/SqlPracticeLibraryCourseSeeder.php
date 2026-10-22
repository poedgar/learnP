<?php

namespace Database\Seeders;

class SqlPracticeLibraryCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'sql-practice-library',
            'title' => 'SQL practice: a library',
            'language' => 'sql',
            'description' => 'Help a town library with three branches: finding and lending books, overdue loans and fines, members and their reading, reservations and demand.',
            'position' => 28,
        ];
    }

    protected function directory(): string
    {
        return 'sql-practice-library';
    }

    protected function lessons(): array
    {
        return [
            '01-finding-books',
            '02-loans-and-fines',
            '03-members',
            '04-reservations-and-demand',
        ];
    }

    protected function dataset(): ?string
    {
        return 'library';
    }
}
