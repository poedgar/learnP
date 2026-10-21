<?php

namespace Database\Seeders;

class JsAsyncCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'js-async',
            'title' => 'Async JavaScript',
            'language' => 'javascript',
            'description' => 'Callbacks and the event loop, promises, async and await, combining promises, and patterns such as async generators, cancellation, retries and queues.',
            'position' => 17,
        ];
    }

    protected function directory(): string
    {
        return 'js-async';
    }

    protected function lessons(): array
    {
        return [
            '01-callbacks-and-the-event-loop',
            '02-promises',
            '03-async-and-await',
            '04-combining-promises',
            '05-async-patterns',
        ];
    }
}
