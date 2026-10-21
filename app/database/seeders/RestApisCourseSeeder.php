<?php

namespace Database\Seeders;

class RestApisCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'rest-apis',
            'title' => 'Building REST APIs',
            'language' => 'php',
            'description' => 'Design and build JSON APIs that clients can rely on: resources and status codes, API resources, predictable errors, tokens and rate limits, pagination, HTTP caching and versions, each practised in plain PHP.',
            'position' => 23,
        ];
    }

    protected function directory(): string
    {
        return 'rest-apis';
    }

    protected function lessons(): array
    {
        return [
            '01-resources-and-verbs',
            '02-shaping-json',
            '03-errors-and-validation',
            '04-authentication-and-rate-limits',
            '05-pagination-caching-and-versions',
        ];
    }
}
