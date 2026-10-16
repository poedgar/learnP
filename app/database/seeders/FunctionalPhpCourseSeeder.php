<?php

namespace Database\Seeders;

class FunctionalPhpCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'functional-php',
            'title' => 'Functional PHP',
            'language' => 'php',
            'description' => 'Pure functions and immutability, closures, map, filter and reduce, currying and partial application, pipelines and lazy collections with generators.',
            'position' => 8,
        ];
    }

    protected function directory(): string
    {
        return 'functional-php';
    }

    protected function lessons(): array
    {
        return [
            '01-pure-functions',
            '02-higher-order-functions',
            '03-currying-and-partial-application',
            '04-pipelines-and-lazy-collections',
        ];
    }
}
