<?php

namespace Tests\Feature;

use App\Sandbox\SqlSandbox;
use Tests\TestCase;

/**
 * Lessons 2-4 quote results from this data. If a test here fails, the lesson
 * text, quiz answers or challenges that depend on it need checking too.
 */
class BookshopDatasetTest extends TestCase
{
    /**
     * @return list<list<mixed>>
     */
    private function rows(string $sql, string $dataset = 'bookshop'): array
    {
        $run = app(SqlSandbox::class)->run($dataset, $sql);
        $this->assertNull($run['error'], $run['error']['message'] ?? '');

        return $run['results'][0]['rows'];
    }

    private function value(string $sql): mixed
    {
        return $this->rows($sql)[0][0];
    }

    public function test_books_are_the_same_as_in_lesson_one(): void
    {
        $this->assertSame(
            $this->rows('SELECT * FROM books ORDER BY id', 'bookstore'),
            $this->rows('SELECT * FROM books ORDER BY id'),
        );
    }

    public function test_it_has_the_rows_the_lessons_count_on(): void
    {
        $this->assertSame(10, $this->value('SELECT COUNT(*) FROM customers'));
        $this->assertSame(20, $this->value('SELECT COUNT(*) FROM orders'));
        $this->assertSame(36, $this->value('SELECT COUNT(*) FROM order_items'));
        $this->assertSame(2, $this->value('SELECT COUNT(*) FROM customers WHERE city IS NULL'));
        $this->assertSame(4, $this->value('SELECT COUNT(*) FROM orders WHERE shipped_on IS NULL'));
        $this->assertSame(8, $this->value("SELECT COUNT(*) FROM orders WHERE ordered_on BETWEEN '2026-03-01' AND '2026-03-31'"));
        $this->assertSame(1, $this->value("SELECT COUNT(*) FROM customers WHERE name = 'Siobhan O''Brien'"));
    }

    public function test_some_customers_never_ordered_and_some_books_never_sold(): void
    {
        $this->assertSame(
            [['Isla Murray'], ['Jonas Weber']],
            $this->rows('SELECT c.name FROM customers c LEFT JOIN orders o ON o.customer_id = c.id WHERE o.id IS NULL ORDER BY c.name'),
        );
        $this->assertSame(
            [['Beloved'], ['Neuromancer'], ['The Remains of the Day']],
            $this->rows('SELECT b.title FROM books b LEFT JOIN order_items oi ON oi.book_id = b.id WHERE oi.book_id IS NULL ORDER BY b.title'),
        );
    }

    public function test_the_busiest_day_is_unique(): void
    {
        $this->assertSame(
            [['2026-03-14', 3], ['2026-03-07', 2]],
            $this->rows('SELECT ordered_on, COUNT(*) AS n FROM orders GROUP BY ordered_on ORDER BY n DESC, ordered_on LIMIT 2'),
        );
    }

    public function test_two_order_lines_were_sold_below_the_list_price(): void
    {
        $this->assertSame(2, $this->value('SELECT COUNT(*) FROM order_items oi JOIN books b ON b.id = oi.book_id WHERE oi.unit_price <> b.price'));
    }

    public function test_every_order_and_order_line_points_at_a_real_row(): void
    {
        $this->assertSame(0, $this->value('SELECT COUNT(*) FROM orders o LEFT JOIN customers c ON c.id = o.customer_id WHERE c.id IS NULL'));
        $this->assertSame(0, $this->value('SELECT COUNT(*) FROM order_items oi LEFT JOIN orders o ON o.id = oi.order_id WHERE o.id IS NULL'));
        $this->assertSame(0, $this->value('SELECT COUNT(*) FROM order_items oi LEFT JOIN books b ON b.id = oi.book_id WHERE b.id IS NULL'));
    }
}
