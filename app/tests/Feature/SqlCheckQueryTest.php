<?php

namespace Tests\Feature;

use App\Sandbox\SqlSandbox;
use Tests\TestCase;

/**
 * Challenges that change data are checked by a follow-up query, run after the
 * learner's SQL and after the solution, whose results are then compared.
 */
class SqlCheckQueryTest extends TestCase
{
    private const SOLUTION = "UPDATE orders SET shipped_on = '2026-04-08' WHERE id = 17;";

    private const CHECK = 'SELECT id, shipped_on FROM orders ORDER BY id';

    private function check(string $sql, ?string $checkSql = self::CHECK): array
    {
        return app(SqlSandbox::class)->check('bookshop', $sql, self::SOLUTION, false, $checkSql);
    }

    public function test_the_right_change_passes(): void
    {
        $result = $this->check("update orders set shipped_on = '2026-04-08' where id = 17");

        $this->assertTrue($result['passed'], $result['message']);
    }

    public function test_a_wrong_change_fails(): void
    {
        $result = $this->check("UPDATE orders SET shipped_on = '2026-04-08' WHERE shipped_on IS NULL;");

        $this->assertFalse($result['passed']);
        $this->assertSame('The shape is right, but some values differ from the expected result. Compare the two tables below.', $result['message']);
    }

    public function test_a_select_that_changes_nothing_fails(): void
    {
        $result = $this->check("SELECT 17 AS id, '2026-04-08' AS shipped_on;");

        $this->assertFalse($result['passed']);
    }

    public function test_a_trailing_comment_does_not_swallow_the_check(): void
    {
        $result = $this->check("UPDATE orders SET shipped_on = '2026-04-08' WHERE id = 17; -- done");

        $this->assertTrue($result['passed'], $result['message']);
    }

    public function test_an_error_in_the_learners_sql_is_reported_as_theirs(): void
    {
        $result = $this->check("UPDATE orders SET shiped_on = '2026-04-08' WHERE id = 17;");

        $this->assertSame('Your SQL has an error. Fix it and check again.', $result['message']);
    }

    public function test_a_check_that_fails_says_so(): void
    {
        $result = app(SqlSandbox::class)->check(
            'bookshop',
            'SELECT 1;',
            'ALTER TABLE customers ADD COLUMN phone TEXT;',
            false,
            'SELECT id, phone FROM customers ORDER BY id',
        );

        $this->assertFalse($result['passed']);
        $this->assertSame(
            'Your SQL ran, but checking what it changed failed: No such column: phone. Make sure you made every change the challenge asks for.',
            $result['message'],
        );
    }

    public function test_without_a_check_the_learners_own_result_is_compared(): void
    {
        $result = app(SqlSandbox::class)->check('bookshop', 'SELECT id FROM orders WHERE id = 17', 'SELECT id FROM orders WHERE id = 17', false);

        $this->assertTrue($result['passed'], $result['message']);
        $this->assertSame([[17]], $result['run']['results'][0]['rows']);
    }
}
