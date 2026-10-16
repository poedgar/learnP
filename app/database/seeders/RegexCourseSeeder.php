<?php

namespace Database\Seeders;

class RegexCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'regex',
            'title' => 'Regular expressions',
            'language' => 'javascript',
            'description' => 'Patterns and character classes, quantifiers and anchors, groups, searching and replacing, lookarounds and Unicode, and regular expressions on real tasks, using JavaScript.',
            'position' => 16,
        ];
    }

    protected function directory(): string
    {
        return 'regex';
    }

    protected function lessons(): array
    {
        return [
            '01-patterns-and-character-classes',
            '02-quantifiers-and-anchors',
            '03-groups-and-alternation',
            '04-searching-and-replacing',
            '05-lookarounds-and-unicode',
            '06-regex-in-practice',
        ];
    }
}
