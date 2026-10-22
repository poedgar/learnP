<?php

namespace Tests\Feature;

use App\Sandbox\SqlSandbox;
use Tests\TestCase;

/**
 * The SQL practice and interview courses quote numbers from these datasets in their lessons and
 * quiz answers. If a test here fails, the lesson text, quiz answers or challenges that depend on it
 * need checking too.
 */
class PracticeDatasetsTest extends TestCase
{
    private function value(string $dataset, string $sql): mixed
    {
        $run = app(SqlSandbox::class)->run($dataset, $sql);
        $this->assertNull($run['error'], $run['error']['message'] ?? '');

        return $run['results'][0]['rows'][0][0];
    }

    public function test_the_analytics_numbers_the_lessons_quote(): void
    {
        $this->assertSame(30, $this->value('analytics', 'SELECT COUNT(*) FROM users'));
        $this->assertSame(15, $this->value('analytics', "SELECT COUNT(*) FROM users WHERE signed_up_on < '2026-04-01'"));
        $this->assertSame('25,20,7', $this->value('analytics', "SELECT COUNT(DISTINCT user_id) FILTER (WHERE event = 'view_book') || ',' || COUNT(DISTINCT user_id) FILTER (WHERE event = 'start_trial') || ',' || COUNT(DISTINCT user_id) FILTER (WHERE event = 'subscribe') FROM events"));
        $this->assertSame(15980, $this->value('analytics', "SELECT SUM(amount_cents) FROM payments WHERE status = 'paid'"));
        $this->assertSame(8, $this->value('analytics', 'SELECT COUNT(*) FROM users WHERE referrer IS NULL'));
        $this->assertSame(147, $this->value('analytics', 'SELECT COUNT(*) FROM sessions'));
        $this->assertSame(8, $this->value('analytics', "SELECT COUNT(*) FROM users AS u WHERE NOT EXISTS (SELECT 1 FROM sessions AS s WHERE s.user_id = u.id AND s.started_at >= '2026-06-01')"));
    }

    public function test_the_shop_numbers_the_lessons_quote(): void
    {
        $this->assertSame(4, $this->value('shop', "SELECT COUNT(*) FROM orders WHERE status = 'cancelled'"));
        $this->assertSame(122200, $this->value('shop', "SELECT SUM(oi.quantity * oi.unit_price_cents) FROM order_items AS oi JOIN orders AS o ON o.id = oi.order_id WHERE o.status <> 'cancelled'"));
        $this->assertSame(4, $this->value('shop', "SELECT COUNT(*) FROM customers AS c WHERE NOT EXISTS (SELECT 1 FROM orders AS o WHERE o.customer_id = c.id AND o.status <> 'cancelled')"));
        $this->assertSame(4, $this->value('shop', 'SELECT COALESCE(SUM(change), 0) FROM stock_movements WHERE product_id = 10'));
        $this->assertSame(5, $this->value('shop', "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'shipped')"));
    }

    public function test_the_library_numbers_the_lessons_quote(): void
    {
        $this->assertSame(11, $this->value('library', 'SELECT COUNT(*) FROM loans WHERE returned_on IS NULL'));
        $this->assertSame(5, $this->value('library', "SELECT COUNT(*) FROM loans WHERE returned_on IS NULL AND due_on < '2026-10-21'"));
        $this->assertSame(24.75, $this->value('library', 'SELECT SUM(julianday(returned_on) - julianday(due_on)) * 0.25 FROM loans WHERE returned_on > due_on'));
        $this->assertSame(4, $this->value('library', 'SELECT COUNT(*) FROM reservations WHERE fulfilled_on IS NULL'));
        $this->assertSame(0, $this->value('library', 'SELECT COUNT(*) FROM loans AS l JOIN members AS m ON m.id = l.member_id WHERE l.borrowed_on < m.joined_on'));
    }

    public function test_the_company_numbers_the_lessons_quote(): void
    {
        $this->assertSame(145000, $this->value('company', 'SELECT DISTINCT salary FROM employees ORDER BY salary DESC LIMIT 1 OFFSET 1'));
        $this->assertSame(2, $this->value('company', 'SELECT COUNT(*) FROM employees AS e JOIN employees AS m ON m.id = e.manager_id WHERE e.salary > m.salary'));
        $this->assertSame(1, $this->value('company', 'SELECT COUNT(*) FROM (SELECT lower(email) FROM employees GROUP BY lower(email) HAVING COUNT(*) > 1)'));
        $this->assertSame(11, $this->value('company', 'SELECT COUNT(DISTINCT employee_id) FROM logins'));
        $this->assertSame(92500.0, $this->value('company', 'WITH o AS (SELECT salary, ROW_NUMBER() OVER (ORDER BY salary) AS p, COUNT(*) OVER () AS n FROM employees) SELECT AVG(salary) FROM o WHERE p IN ((n + 1) / 2, (n + 2) / 2)'));
    }
}
