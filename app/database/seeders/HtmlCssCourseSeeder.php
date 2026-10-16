<?php

namespace Database\Seeders;

class HtmlCssCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'html-css',
            'title' => 'HTML & CSS',
            'language' => 'html',
            'description' => 'Structure pages with meaningful HTML, build forms, then style them with selectors and the cascade, the box model, Flexbox and Grid, and make them work on any screen, with a live preview.',
            'position' => 11,
        ];
    }

    protected function directory(): string
    {
        return 'html-css';
    }

    protected function lessons(): array
    {
        return [
            '01-structure-and-meaning',
            '02-lists-tables-and-figures',
            '03-forms',
            '04-selectors-and-the-cascade',
            '05-the-box-model',
            '06-flexbox',
            '07-grid-and-responsive-design',
        ];
    }
}
