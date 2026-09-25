<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class SqlCourseSeeder extends Seeder
{
    use SyncsLessonContent;

    public function run(): void
    {
        $course = Course::updateOrCreate(['slug' => 'sql'], [
            'title' => 'SQL',
            'language' => 'sql',
            'description' => 'Ask questions of your data. Learn to read, filter, sort and combine tables with the language every relational database speaks.',
            'position' => 1,
        ]);

        $lesson = $course->lessons()->updateOrCreate(['slug' => 'your-first-queries'], [
            'title' => 'Your first queries',
            'summary' => 'Read data from a table with SELECT, filter it with WHERE, and sort and trim the result with ORDER BY and LIMIT.',
            'content' => file_get_contents(database_path('content/sql/01-your-first-queries.md')),
            'dataset' => 'bookstore',
            'starter_code' => "-- Try changing this query, then press Run (Ctrl+Enter).\nSELECT title, author, price\nFROM books\nORDER BY price DESC;",
            'position' => 1,
        ]);

        $this->syncByPosition($lesson->quizQuestions(), $this->quizQuestions());
        $this->syncByPosition($lesson->challenges(), $this->challenges());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function quizQuestions(): array
    {
        $questions = [
            [
                'question' => 'Which clause tells the database which table to read from?',
                'options' => ['`SELECT`', '`FROM`', '`WHERE`', '`TABLE`'],
                'correct_option' => 1,
                'explanation' => '`FROM` names the table. `SELECT` names the columns, and `WHERE` filters rows.',
            ],
            [
                'question' => 'What does `SELECT *` return?',
                'options' => ['Only the first column', 'Every column of the table', 'Every table in the database', 'The number of rows'],
                'correct_option' => 1,
                'explanation' => '`*` is a wildcard meaning "all columns". It still returns only the rows your query keeps.',
            ],
            [
                'question' => 'Which condition correctly finds fantasy books?',
                'options' => ['`WHERE genre = Fantasy`', "`WHERE genre = 'Fantasy'`", '`WHERE genre == "Fantasy"`', "`WHERE 'genre' = Fantasy`"],
                'correct_option' => 1,
                'explanation' => 'Text values go in single quotes, and SQL uses a single `=` for comparison. Without quotes, the database would look for a column named Fantasy.',
            ],
            [
                'question' => 'How many rows does this query return for the bookstore data?',
                'code' => "SELECT title\nFROM books\nWHERE price > 20;",
                'options' => ['0', '1', '2', '16'],
                'correct_option' => 2,
                'explanation' => 'Only *The Name of the Wind* (21.50) and *Jonathan Strange & Mr Norrell* (22.00) cost more than 20. Try it in the sandbox.',
            ],
            [
                'question' => 'Which query returns the five longest books?',
                'options' => [
                    '`SELECT title FROM books LIMIT 5 ORDER BY pages DESC;`',
                    '`SELECT title FROM books ORDER BY pages LIMIT 5;`',
                    '`SELECT title FROM books ORDER BY pages DESC LIMIT 5;`',
                    '`SELECT TOP 5 title FROM books;`',
                ],
                'correct_option' => 2,
                'explanation' => 'Sort from longest to shortest with `ORDER BY pages DESC`, then keep the first five with `LIMIT 5`. `LIMIT` must come after `ORDER BY`.',
            ],
            [
                'question' => 'What does `DISTINCT` do in `SELECT DISTINCT genre FROM books`?',
                'options' => [
                    'Sorts the genres alphabetically',
                    'Returns each genre only once',
                    'Removes books without a genre',
                    'Counts how many genres there are',
                ],
                'correct_option' => 1,
                'explanation' => '`DISTINCT` removes duplicate rows from the result. It doesn\'t sort; add `ORDER BY` for that.',
            ],
            [
                'question' => 'Which books does this query return?',
                'code' => "SELECT title\nFROM books\nWHERE genre = 'Horror' OR genre = 'Fantasy' AND price < 10;",
                'options' => [
                    'Horror and fantasy books under 10',
                    'Every horror book, plus fantasy books under 10',
                    'Only books under 10',
                    'Nothing, because the query is invalid',
                ],
                'correct_option' => 1,
                'explanation' => '`AND` is evaluated before `OR`, so this reads as `genre = \'Horror\' OR (genre = \'Fantasy\' AND price < 10)`. Wrap the `OR` in parentheses if you mean "horror or fantasy, under 10".',
            ],
            [
                'question' => 'In which order must these clauses be written?',
                'options' => [
                    'FROM, SELECT, WHERE, LIMIT, ORDER BY',
                    'SELECT, WHERE, FROM, ORDER BY, LIMIT',
                    'SELECT, FROM, WHERE, ORDER BY, LIMIT',
                    'Any order works',
                ],
                'correct_option' => 2,
                'explanation' => 'Queries are written `SELECT … FROM … WHERE … ORDER BY … LIMIT`, even though the database processes `FROM` and `WHERE` first.',
            ],
        ];

        return $this->positioned($questions);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function challenges(): array
    {
        $challenges = [
            [
                'title' => 'Titles and authors',
                'prompt' => 'The shop wants a simple catalogue. List the `title` and `author` of every book.',
                'starter_code' => "SELECT\nFROM books;",
                'solution' => 'SELECT title, author FROM books;',
                'hint' => 'Put the two column names after SELECT, separated by a comma.',
                'order_matters' => false,
            ],
            [
                'title' => 'The oldest classics',
                'prompt' => 'Show the `title` and `published_year` of every book published **before 1900**, oldest first.',
                'starter_code' => null,
                'solution' => 'SELECT title, published_year FROM books WHERE published_year < 1900 ORDER BY published_year;',
                'hint' => 'Filter with WHERE published_year < 1900, then sort with ORDER BY published_year.',
                'order_matters' => true,
            ],
            [
                'title' => 'Every genre, once',
                'prompt' => 'List each `genre` the shop stocks, with no duplicates, in alphabetical order.',
                'starter_code' => null,
                'solution' => 'SELECT DISTINCT genre FROM books ORDER BY genre;',
                'hint' => 'SELECT DISTINCT removes duplicates. ORDER BY sorts text alphabetically.',
                'order_matters' => true,
            ],
            [
                'title' => 'Top three by price',
                'prompt' => 'Find the three most expensive books. Show their `title` and `price`, most expensive first.',
                'starter_code' => null,
                'solution' => 'SELECT title, price FROM books ORDER BY price DESC LIMIT 3;',
                'hint' => 'Sort with ORDER BY price DESC, then keep three rows with LIMIT.',
                'order_matters' => true,
            ],
            [
                'title' => 'Affordable fantasy',
                'prompt' => 'A customer wants a fantasy book that is **in stock** (`in_stock = 1`) and costs **less than 15**. Show the `title` and `price` of the matches, cheapest first.',
                'starter_code' => null,
                'solution' => "SELECT title, price FROM books WHERE genre = 'Fantasy' AND in_stock = 1 AND price < 15 ORDER BY price;",
                'hint' => "You need three conditions joined with AND. Remember the quotes around 'Fantasy'.",
                'order_matters' => true,
            ],
            [
                'title' => 'Science fiction sale',
                'prompt' => 'Science fiction is 20% off this week. For every science fiction book, show its `title` and its discounted price in a column named `sale_price`.',
                'starter_code' => null,
                'solution' => "SELECT title, price * 0.8 AS sale_price FROM books WHERE genre = 'Science Fiction';",
                'hint' => '20% off means paying 80% of the price: price * 0.8. Name the column with AS.',
                'order_matters' => false,
            ],
        ];

        return $this->positioned($challenges);
    }
}
