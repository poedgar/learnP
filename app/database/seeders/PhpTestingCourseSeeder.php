<?php

namespace Database\Seeders;

class PhpTestingCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-testing',
            'title' => 'Testing without frameworks',
            'language' => 'php',
            'description' => 'Write your own assertions and test runner, write tests that catch real bugs, replace dependencies with hand-made test doubles, and practise test-driven development on classic katas.',
            'position' => 9,
        ];
    }

    protected function directory(): string
    {
        return 'php-testing';
    }

    protected function lessons(): array
    {
        return [
            '01-writing-assertions',
            '02-a-tiny-test-runner',
            '03-writing-good-tests',
            '04-test-doubles',
            '05-test-driven-development',
        ];
    }
}
