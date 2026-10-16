<?php

namespace Database\Seeders;

class PhpBasicsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-basics',
            'title' => 'PHP basics',
            'language' => 'php',
            'description' => 'Start programming in PHP: values and variables, strings, arrays, loops, functions, types, dates, regular expressions and handling errors.',
            'position' => 2,
        ];
    }

    protected function directory(): string
    {
        return 'php-basics';
    }

    protected function lessons(): array
    {
        return [
            '01-values-and-variables',
            '02-strings-and-formatting',
            '03-comparisons-and-type-juggling',
            '04-control-flow',
            '05-arrays',
            '06-array-functions',
            '07-functions',
            '08-dates',
            '09-types-and-null-safety',
            '10-regular-expressions',
            '11-handling-errors',
        ];
    }
}
