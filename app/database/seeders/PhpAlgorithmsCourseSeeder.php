<?php

namespace Database\Seeders;

class PhpAlgorithmsCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'php-algorithms',
            'title' => 'Algorithms and data structures',
            'language' => 'php',
            'description' => 'Big-O, searching and sorting, recursion, stacks, queues, linked lists, hash maps, trees, heaps, graphs and dynamic programming, all in plain PHP.',
            'position' => 7,
        ];
    }

    protected function directory(): string
    {
        return 'php-algorithms';
    }

    protected function lessons(): array
    {
        return [
            '01-big-o-and-searching',
            '02-sorting',
            '03-recursion',
            '04-stacks-and-queues',
            '05-linked-lists',
            '06-hash-maps-and-sets',
            '07-trees-and-heaps',
            '08-graphs',
            '09-dynamic-programming-and-techniques',
        ];
    }
}
