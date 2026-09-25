<?php

namespace Tests\Unit;

use App\Sandbox\StatementSplitter;
use PHPUnit\Framework\TestCase;

class StatementSplitterTest extends TestCase
{
    public function test_it_splits_statements_on_semicolons(): void
    {
        $statements = StatementSplitter::split("SELECT 1;\n\nSELECT 2 ; ");

        $this->assertSame(['SELECT 1', 'SELECT 2'], array_column($statements, 'sql'));
    }

    public function test_semicolons_inside_strings_identifiers_and_comments_do_not_split(): void
    {
        $sql = "SELECT 'a;b', \"c;d\" -- e;f\nFROM t /* g;h */ WHERE x = 'it''s;';";

        $statements = StatementSplitter::split($sql);

        $this->assertCount(1, $statements);
        $this->assertSame(rtrim($sql, ';'), $statements[0]['sql']);
    }

    public function test_keywords_ignore_string_contents_and_comments(): void
    {
        $statements = StatementSplitter::split("SELECT 'ATTACH' AS word -- VACUUM\nFROM books");

        $this->assertSame(['SELECT', 'AS', 'WORD', 'FROM', 'BOOKS'], $statements[0]['keywords']);
    }

    public function test_comment_only_statements_are_dropped(): void
    {
        $this->assertSame([], StatementSplitter::split("-- just a note\n/* and another */;"));
    }

    public function test_trigger_bodies_stay_in_one_statement(): void
    {
        $statements = StatementSplitter::split(
            'CREATE TRIGGER t AFTER INSERT ON books BEGIN UPDATE books SET price = 1; DELETE FROM books; END; SELECT 1;'
        );

        $this->assertCount(2, $statements);
        $this->assertStringEndsWith('END', $statements[0]['sql']);
    }
}
