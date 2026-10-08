<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Lesson;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeCheckApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seeder = DatabaseSeeder::class;

    protected $seed = true;

    private function check(string $title, string $sql)
    {
        $challenge = Challenge::where('title', $title)->firstOrFail();

        return $this->postJson("/api/challenges/{$challenge->id}/check", ['sql' => $sql]);
    }

    public function test_every_seeded_solution_passes_its_own_challenge(): void
    {
        foreach (Challenge::whereNull('checks')->get() as $challenge) {
            $this->postJson("/api/challenges/{$challenge->id}/check", ['sql' => $challenge->solution])
                ->assertOk()
                ->assertJsonPath('passed', true);
        }
    }

    public function test_an_equivalent_query_passes(): void
    {
        $this->check('Science fiction sale', "select title, price - price * 0.2 as discounted from books where genre = 'Science Fiction' order by title")
            ->assertJsonPath('passed', true);
    }

    public function test_wrong_number_of_columns_is_explained(): void
    {
        $this->check('Titles and authors', 'SELECT title FROM books')
            ->assertJsonPath('passed', false)
            ->assertJsonPath('message', 'The result should have 2 columns, yours has 1.')
            ->assertJsonPath('expected.columns', ['title', 'author']);
    }

    public function test_wrong_number_of_rows_is_explained(): void
    {
        $this->check('Top three by price', 'SELECT title, price FROM books ORDER BY price DESC')
            ->assertJsonPath('passed', false)
            ->assertJsonPath('message', 'The result should have 3 rows, yours has 16.');
    }

    public function test_row_order_is_checked_when_it_matters(): void
    {
        $this->check('The oldest classics', 'SELECT title, published_year FROM books WHERE published_year < 1900 ORDER BY published_year DESC')
            ->assertJsonPath('passed', false)
            ->assertJsonPath('message', 'You have the right rows, but in the wrong order. Check your ORDER BY.');
    }

    public function test_row_order_is_ignored_when_it_does_not_matter(): void
    {
        $this->check('Titles and authors', 'SELECT title, author FROM books ORDER BY author DESC')
            ->assertJsonPath('passed', true);
    }

    public function test_sql_errors_fail_the_check_and_include_the_error(): void
    {
        $this->check('Titles and authors', 'SELECT title, writer FROM books')
            ->assertJsonPath('passed', false)
            ->assertJsonPath('run.error.message', 'No such column: writer');
    }

    public function test_the_last_result_table_is_the_one_checked(): void
    {
        $this->check('Titles and authors', 'SELECT 1; SELECT title, author FROM books;')
            ->assertJsonPath('passed', true);
    }

    public function test_counting_rows_instead_of_orders_after_a_left_join_fails(): void
    {
        // COUNT(*) counts the NULL row a LEFT JOIN adds, so customers with no orders get 1, not 0.
        $this->check('Orders per customer', 'SELECT c.name, COUNT(*) FROM customers AS c LEFT JOIN orders AS o ON o.customer_id = c.id GROUP BY c.id, c.name')
            ->assertJsonPath('passed', false);
        $this->check('Orders per customer', 'SELECT c.name, COUNT(o.id) FROM customers AS c LEFT JOIN orders AS o ON o.customer_id = c.id GROUP BY c.id, c.name')
            ->assertJsonPath('passed', true);
    }

    public function test_a_challenge_with_a_check_query_is_checked_by_it(): void
    {
        $challenge = Lesson::where('slug', 'combining-tables')->firstOrFail()->challenges()->create([
            'title' => 'Ship order 17', 'prompt' => 'Mark order 17 as shipped on 2026-04-08.',
            'solution' => "UPDATE orders SET shipped_on = '2026-04-08' WHERE id = 17;",
            'check_sql' => 'SELECT id, shipped_on FROM orders ORDER BY id',
            'hint' => '', 'order_matters' => false, 'position' => 99,
        ]);

        $this->postJson("/api/challenges/{$challenge->id}/check", ['sql' => "UPDATE orders SET shipped_on = '2026-04-08' WHERE id = 17"])
            ->assertOk()
            ->assertJsonPath('passed', true);
        $this->postJson("/api/challenges/{$challenge->id}/check", ['sql' => "SELECT 17, '2026-04-08'"])
            ->assertJsonPath('passed', false);
    }

    public function test_the_check_query_is_never_sent_to_the_app(): void
    {
        Lesson::where('slug', 'combining-tables')->firstOrFail()->challenges()->create([
            'title' => 'Ship order 17', 'prompt' => '', 'solution' => 'SELECT 1;',
            'check_sql' => 'SELECT 1', 'hint' => '', 'order_matters' => false, 'position' => 99,
        ]);

        foreach ($this->getJson('/api/courses/sql/lessons/combining-tables')->json('data.challenges') as $challenge) {
            $this->assertArrayNotHasKey('check_sql', $challenge);
        }
    }
}
