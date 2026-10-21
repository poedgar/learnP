<?php

namespace Database\Seeders;

class LaravelAdvancedCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'laravel-advanced',
            'title' => 'Advanced Laravel',
            'language' => 'php',
            'description' => 'How Laravel works inside, rebuilt in plain PHP: the service container, providers, facades, the middleware pipeline, routing, collections, Eloquent, events, queues and validation.',
            'position' => 13,
        ];
    }

    protected function directory(): string
    {
        return 'laravel-advanced';
    }

    protected function lessons(): array
    {
        return [
            '01-the-service-container',
            '02-service-providers',
            '03-facades',
            '04-the-middleware-pipeline',
            '05-routing-and-model-binding',
            '06-collections-and-macros',
            '07-inside-eloquent',
            '08-events-and-observers',
            '09-queues-and-jobs',
            '10-validation-and-form-requests',
        ];
    }
}
