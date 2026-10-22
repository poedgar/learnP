<?php

namespace Database\Seeders;

class SqlPracticeShopCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'sql-practice-shop',
            'title' => 'SQL practice: an online shop',
            'language' => 'sql',
            'description' => 'Run an online stationery shop with SQL: sales reports and margin, customer value and recency, baskets and prices, stock and returns.',
            'position' => 27,
        ];
    }

    protected function directory(): string
    {
        return 'sql-practice-shop';
    }

    protected function lessons(): array
    {
        return [
            '01-sales-reports',
            '02-customers',
            '03-baskets-and-prices',
            '04-stock-and-returns',
        ];
    }

    protected function dataset(): ?string
    {
        return 'shop';
    }
}
