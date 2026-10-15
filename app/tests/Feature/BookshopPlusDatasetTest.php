<?php

namespace Tests\Feature;

use App\Sandbox\SqlSandbox;
use Tests\TestCase;

/**
 * The Advanced SQL course quotes results from this data. If a test here fails, the lesson
 * text, quiz answers or challenges that depend on it need checking too.
 */
class BookshopPlusDatasetTest extends TestCase
{
    /**
     * @return list<list<mixed>>
     */
    private function rows(string $sql, string $dataset = 'bookshop_plus'): array
    {
        $run = app(SqlSandbox::class)->run($dataset, $sql);
        $this->assertNull($run['error'], $run['error']['message'] ?? '');

        return $run['results'][0]['rows'];
    }

    private function value(string $sql): mixed
    {
        return $this->rows($sql)[0][0];
    }

    public function test_the_bookshop_tables_are_the_same_as_in_the_sql_course(): void
    {
        foreach (['books', 'customers', 'orders', 'order_items'] as $table) {
            $this->assertSame(
                $this->rows("SELECT * FROM $table ORDER BY rowid", 'bookshop'),
                $this->rows("SELECT * FROM $table ORDER BY rowid"),
                $table,
            );
        }
    }

    public function test_it_has_the_rows_the_lessons_count_on(): void
    {
        $this->assertSame(10, $this->value('SELECT COUNT(*) FROM staff'));
        $this->assertSame(8, $this->value('SELECT COUNT(DISTINCT role) FROM staff'));
        $this->assertSame(23, $this->value('SELECT COUNT(*) FROM reviews'));
        $this->assertSame(4, $this->value('SELECT COUNT(*) FROM reviews WHERE rating IS NULL'));
        $this->assertSame(9, $this->value("SELECT COUNT(*) FROM orders WHERE shipped_on > '2026-03-01'"));
        $this->assertSame(7, $this->value("SELECT COUNT(*) FROM orders WHERE NOT (shipped_on > '2026-03-01')"));
        $this->assertSame(10, $this->value('SELECT COUNT(*) FROM supplier_prices'));
    }

    public function test_set_operations_see_the_cities_the_lessons_describe(): void
    {
        $this->assertSame(20, count($this->rows('SELECT city FROM customers UNION ALL SELECT city FROM staff')));
        $this->assertSame([[null], ['Leeds'], ['London']], $this->rows('SELECT city FROM customers INTERSECT SELECT city FROM staff'));
        $this->assertSame([['Bristol']], $this->rows('SELECT city FROM staff EXCEPT SELECT city FROM customers'));
    }

    public function test_the_staff_tree_has_one_root_and_no_loops(): void
    {
        $this->assertSame([['Margaret Lin']], $this->rows('SELECT name FROM staff WHERE manager_id IS NULL'));
        $this->assertSame(10, $this->value(
            'WITH RECURSIVE t (id) AS (SELECT id FROM staff WHERE manager_id IS NULL UNION ALL SELECT s.id FROM staff AS s JOIN t ON s.manager_id = t.id) SELECT COUNT(*) FROM t'
        ));
        $this->assertSame([['Lucy Grant']], $this->rows('SELECT s.name FROM staff AS s JOIN staff AS m ON m.id = s.manager_id WHERE s.salary > m.salary'));
    }

    public function test_the_feeds_have_the_duplicates_and_runs_the_lessons_use(): void
    {
        $this->assertSame(2, count($this->rows('SELECT 1 FROM supplier_prices GROUP BY book_id, price, sent_at HAVING COUNT(*) > 1')));
        $this->assertSame(2, count($this->rows('SELECT 1 FROM reviews GROUP BY customer_id, book_id, body HAVING COUNT(*) > 1')));
        $this->assertSame(5, $this->value(
            'SELECT MAX(days) FROM (SELECT COUNT(*) AS days FROM (SELECT customer_id, julianday(visited_on) - ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY visited_on) AS island FROM visits) GROUP BY customer_id, island)'
        ));
        $this->assertSame(0, $this->value("SELECT COUNT(*) FROM events WHERE json_valid(payload) = 0 OR payload ->> '$.device.os' IS NULL"));
    }
}
