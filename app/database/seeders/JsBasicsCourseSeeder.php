<?php

namespace Database\Seeders;

class JsBasicsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'js-basics',
            'title' => 'JavaScript basics',
            'language' => 'javascript',
            'description' => 'Values and variables, comparisons, control flow, functions and closures, arrays, objects and JSON, classes, errors, Map and Set. It runs in your browser.',
            'position' => 12,
        ];
    }

    protected function directory(): string
    {
        return 'js-basics';
    }

    protected function lessons(): array
    {
        return [
            '01-values-and-variables',
            '02-comparisons-and-logic',
            '03-control-flow',
            '04-functions',
            '05-arrays',
            '06-objects-and-json',
            '07-classes',
            '08-errors-maps-and-sets',
        ];
    }
}
