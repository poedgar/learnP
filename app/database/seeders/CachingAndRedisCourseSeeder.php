<?php

namespace Database\Seeders;

class CachingAndRedisCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'caching-and-redis',
            'title' => 'Caching and Redis',
            'language' => 'php',
            'description' => 'Make apps fast without serving wrong data: cache-aside and TTLs, invalidation and stampedes, Redis data structures, locks, queues and sessions on Redis, memoization and eviction, each practised in plain PHP.',
            'position' => 24,
        ];
    }

    protected function directory(): string
    {
        return 'caching-and-redis';
    }

    protected function lessons(): array
    {
        return [
            '01-cache-basics',
            '02-invalidation',
            '03-redis-data-structures',
            '04-redis-in-laravel',
            '05-memoization-and-eviction',
        ];
    }
}
