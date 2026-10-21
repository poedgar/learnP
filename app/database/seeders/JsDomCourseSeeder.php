<?php

namespace Database\Seeders;

class JsDomCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'js-dom',
            'title' => 'JavaScript and the DOM',
            'language' => 'javascript',
            'description' => 'Find and change things on a web page, respond to events, work with forms, and build a small app from state and a render function. The page you build appears as you code.',
            'position' => 18,
        ];
    }

    protected function directory(): string
    {
        return 'js-dom';
    }

    protected function lessons(): array
    {
        return [
            '01-finding-things-on-the-page',
            '02-changing-the-page',
            '03-events',
            '04-forms',
            '05-building-a-small-app',
        ];
    }
}
