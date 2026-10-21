<?php

namespace Database\Seeders;

class TypeScriptCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'typescript',
            'title' => 'TypeScript',
            'language' => 'typescript',
            'description' => 'Types and inference, object types and interfaces, unions and narrowing, generics, classes and utility types, and advanced types, checked by the real TypeScript compiler.',
            'position' => 19,
        ];
    }

    protected function directory(): string
    {
        return 'typescript';
    }

    protected function lessons(): array
    {
        return [
            '01-types-and-inference',
            '02-object-types',
            '03-unions-and-narrowing',
            '04-functions-and-generics',
            '05-classes-and-utility-types',
            '06-type-level-tools',
        ];
    }
}
