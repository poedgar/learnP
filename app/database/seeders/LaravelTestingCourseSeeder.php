<?php

namespace Database\Seeders;

class LaravelTestingCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'laravel-testing',
            'title' => 'Testing Laravel apps',
            'language' => 'php',
            'description' => 'Feature tests that drive the whole app: HTTP assertions, factories and a fresh database, fakes for mail, queues and HTTP, Pest, logged-in users, frozen time and mocks, each practised in plain PHP.',
            'position' => 22,
        ];
    }

    protected function directory(): string
    {
        return 'laravel-testing';
    }

    protected function lessons(): array
    {
        return [
            '01-http-tests',
            '02-databases-and-factories',
            '03-fakes',
            '04-pest',
            '05-users-time-and-mocks',
        ];
    }
}
