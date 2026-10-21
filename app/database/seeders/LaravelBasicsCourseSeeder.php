<?php

namespace Database\Seeders;

class LaravelBasicsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'laravel-basics',
            'title' => 'Laravel basics',
            'language' => 'php',
            'description' => 'The everyday Laravel toolkit: routes, controllers, Blade, migrations, Eloquent models and relationships, forms and validation, and logging users in, each practised in plain PHP.',
            'position' => 12,
        ];
    }

    protected function directory(): string
    {
        return 'laravel-basics';
    }

    protected function lessons(): array
    {
        return [
            '01-routes',
            '02-controllers-and-responses',
            '03-blade-templates',
            '04-migrations',
            '05-eloquent-models',
            '06-relationships',
            '07-forms-and-validation',
            '08-authentication-and-authorization',
        ];
    }
}
