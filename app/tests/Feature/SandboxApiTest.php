<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Sandbox\SqlSandbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SandboxApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function runSql(string $sql)
    {
        return $this->postJson('/api/lessons/'.Lesson::where('dataset', 'bookstore')->firstOrFail()->id.'/run', ['sql' => $sql]);
    }

    public function test_select_returns_columns_and_rows(): void
    {
        $this->runSql('SELECT title, price FROM books WHERE price > 20 ORDER BY price')
            ->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonPath('results.0.type', 'rows')
            ->assertJsonPath('results.0.columns', ['title', 'price'])
            ->assertJsonPath('results.0.rows', [['The Name of the Wind', 21.5], ['Jonathan Strange & Mr Norrell', 22]])
            ->assertJsonPath('results.0.truncated', false);
    }

    public function test_multiple_statements_each_produce_a_result(): void
    {
        $this->runSql("UPDATE books SET price = 1 WHERE genre = 'Horror'; SELECT COUNT(*) AS cheap FROM books WHERE price = 1;")
            ->assertOk()
            ->assertJsonPath('results.0.type', 'command')
            ->assertJsonPath('results.0.affected_rows', 3)
            ->assertJsonPath('results.1.rows', [[3]]);
    }

    public function test_every_run_starts_from_a_fresh_copy_of_the_data(): void
    {
        $this->runSql('DROP TABLE books')->assertJsonPath('error', null);

        $this->runSql('SELECT COUNT(*) FROM books')->assertJsonPath('results.0.rows', [[16]]);
    }

    public function test_sql_errors_are_reported_with_the_failing_statement(): void
    {
        $this->runSql('SELECT 1; SELECT nope FROM books')
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('error.statement', 1)
            ->assertJsonPath('error.message', 'No such column: nope');
    }

    #[DataProvider('forbiddenSql')]
    public function test_commands_that_reach_outside_the_sandbox_are_refused(string $sql): void
    {
        $this->runSql($sql)
            ->assertOk()
            ->assertJsonPath('results', [])
            ->assertJsonPath('error.statement', 0);
    }

    public static function forbiddenSql(): array
    {
        return [
            'attach' => ["ATTACH DATABASE '/tmp/learnp.db' AS other"],
            'vacuum into' => ["VACUUM INTO '/tmp/learnp.db'"],
            'writable pragma' => ['PRAGMA writable_schema = 1'],
            'load extension' => ["SELECT load_extension('evil')"],
        ];
    }

    public function test_read_only_pragmas_are_allowed(): void
    {
        $this->runSql('PRAGMA table_info(books)')
            ->assertOk()
            ->assertJsonPath('error', null)
            ->assertJsonCount(8, 'results.0.rows');
    }

    public function test_large_results_are_truncated(): void
    {
        $this->runSql('WITH RECURSIVE n(x) AS (SELECT 1 UNION ALL SELECT x + 1 FROM n LIMIT 2000) SELECT x FROM n')
            ->assertOk()
            ->assertJsonPath('results.0.truncated', true)
            ->assertJsonCount(500, 'results.0.rows');
    }

    public function test_queries_that_never_finish_are_stopped(): void
    {
        $this->app->instance(SqlSandbox::class, new SqlSandbox(database_path('datasets'), timeoutSeconds: 0.5));

        $this->runSql('WITH RECURSIVE n(x) AS (SELECT 1 UNION ALL SELECT x + 1 FROM n) SELECT COUNT(*) FROM n')
            ->assertOk()
            ->assertJsonPath('results', [])
            ->assertJsonPath('error.message', fn (string $message) => str_contains($message, 'was stopped'));
    }

    public function test_sql_is_required(): void
    {
        $this->runSql('')->assertUnprocessable()->assertJsonValidationErrors('sql');
    }

    public function test_sql_length_is_limited(): void
    {
        $this->runSql('SELECT '.str_repeat('1', SqlSandbox::MAX_SQL_LENGTH))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sql');
    }
}
