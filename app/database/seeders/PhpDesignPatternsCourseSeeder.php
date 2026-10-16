<?php

namespace Database\Seeders;

class PhpDesignPatternsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-design-patterns',
            'title' => 'Design patterns in PHP',
            'language' => 'php',
            'description' => 'Proven shapes for everyday design problems: factories, builders, adapters, decorators, composites, strategies, observers, commands, states and visitors, and when not to use them.',
            'position' => 6,
        ];
    }

    protected function directory(): string
    {
        return 'php-design-patterns';
    }

    protected function lessons(): array
    {
        return [
            '01-patterns-and-factories',
            '02-builder-and-singleton',
            '03-adapter-and-facade',
            '04-decorator-and-proxy',
            '05-composite',
            '06-strategy-and-template-method',
            '07-observer-and-command',
            '08-state-and-chain-of-responsibility',
            '09-visitor-and-choosing-patterns',
        ];
    }
}
