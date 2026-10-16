<?php

namespace Database\Seeders;

class PhpCleanCodeCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-clean-code',
            'title' => 'Clean code and refactoring',
            'language' => 'php',
            'description' => 'Good names and small functions, code smells and the refactorings that fix them, simpler conditionals, better data, and changing legacy code safely, ending with the Gilded Rose kata.',
            'position' => 10,
        ];
    }

    protected function directory(): string
    {
        return 'php-clean-code';
    }

    protected function lessons(): array
    {
        return [
            '01-names-and-functions',
            '02-smells-and-refactoring',
            '03-simplifying-conditionals',
            '04-better-data',
            '05-working-with-legacy-code',
        ];
    }
}
