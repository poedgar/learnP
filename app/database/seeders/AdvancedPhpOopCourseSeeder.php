<?php

namespace Database\Seeders;

class AdvancedPhpOopCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-oop-advanced',
            'title' => 'Advanced OOP in PHP',
            'language' => 'php',
            'description' => 'The tools behind modern PHP frameworks: magic methods, closures, generators, property hooks, attributes and reflection, namespaces, SOLID and dependency injection.',
            'position' => 5,
        ];
    }

    protected function directory(): string
    {
        return 'php-oop-advanced';
    }

    protected function lessons(): array
    {
        return [
            '01-magic-methods',
            '02-object-identity',
            '03-closures-and-callables',
            '04-late-static-binding-and-fluent-interfaces',
            '05-iterators-and-generators',
            '06-objects-that-act-like-arrays',
            '07-property-hooks-and-asymmetric-visibility',
            '08-attributes-and-reflection',
            '09-namespaces-and-autoloading',
            '10-solid-and-a-tiny-container',
        ];
    }
}
