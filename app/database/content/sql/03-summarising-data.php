<?php

// Lesson 3 of the SQL course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/bookshop.sql.

return [
    'slug' => 'summarising-data',
    'title' => 'Summarising data',
    'summary' => 'Count, add up and average your data with aggregate functions, split it into groups with GROUP BY, and filter those groups with HAVING.',
    'starter_code' => "-- How many books does each genre have? Try AVG(price) instead of COUNT(*).\nSELECT genre, COUNT(*) AS books\nFROM books\nGROUP BY genre;",

    'quiz' => [
        [
            'question' => 'What does this query return?',
            'code' => "SELECT COUNT(*), COUNT(city)\nFROM customers;",
            'options' => ['10 and 10', '10 and 8', '8 and 8', '2 and 10'],
            'correct_option' => 1,
            'explanation' => '`COUNT(*)` counts all 10 rows. `COUNT(city)` skips the two customers whose city is `NULL`.',
        ],
        [
            'question' => 'Which expression gives the price of the cheapest book?',
            'options' => ['`MIN(price)`', '`MAX(price)`', '`LOW(price)`', '`FIRST(price)`'],
            'correct_option' => 0,
            'explanation' => '`MIN` returns the smallest value and `MAX` the largest. `LOW` and `FIRST` aren\'t SQL functions.',
        ],
        [
            'question' => 'How many different customers have placed an order?',
            'code' => "SELECT COUNT(DISTINCT customer_id)\nFROM orders;",
            'options' => ['8', '10', '20', '36'],
            'correct_option' => 0,
            'explanation' => 'There are 20 orders, but `DISTINCT` counts each `customer_id` once: 8 customers. Two customers have never ordered.',
        ],
        [
            'question' => 'What does this query return?',
            'code' => "SELECT ROUND(AVG(price), 2)\nFROM books\nWHERE genre = 'Horror';",
            'options' => ['9.66', '9.67', '28.99', '3'],
            'correct_option' => 0,
            'explanation' => 'The three horror books cost 8.50, 7.99 and 12.50. They add up to 28.99, and 28.99 / 3 = 9.6633…, which rounds to 9.66.',
        ],
        [
            'question' => 'SQLite runs this query. What is wrong with it?',
            'code' => "SELECT genre, title, COUNT(*)\nFROM books\nGROUP BY genre;",
            'options' => [
                'Nothing, it lists every title with its genre',
                '`COUNT(*)` has to come first',
                '`title` is neither grouped nor aggregated, so each row shows one arbitrary title from its group',
                'It counts titles instead of books',
            ],
            'correct_option' => 2,
            'explanation' => 'Each result row stands for a whole genre, so a single `title` makes no sense. SQLite picks one; most databases reject the query. Select only grouped columns and aggregates.',
        ],
        [
            'question' => 'Why does this query fail?',
            'code' => "SELECT genre, COUNT(*)\nFROM books\nWHERE COUNT(*) > 3\nGROUP BY genre;",
            'options' => [
                '`COUNT` needs a column name',
                '`>` can\'t compare counts',
                '`GROUP BY` must come before `WHERE`',
                '`WHERE` runs before the rows are grouped, so there are no counts yet. Use `HAVING`',
            ],
            'correct_option' => 3,
            'explanation' => '`WHERE` filters rows before grouping. To filter on a group\'s total, put the condition in `HAVING COUNT(*) > 3`, after `GROUP BY`.',
        ],
        [
            'question' => 'Which books does this query count?',
            'code' => "SELECT genre, COUNT(*)\nFROM books\nWHERE in_stock = 1\nGROUP BY genre;",
            'options' => [
                'Every book',
                'Only books that are in stock',
                'Only genres where every book is in stock',
                'None, because it is an error',
            ],
            'correct_option' => 1,
            'explanation' => '`WHERE` runs first and removes the books that are out of stock; then the remaining books are grouped and counted.',
        ],
        [
            'question' => 'In which order must these clauses be written?',
            'options' => [
                'SELECT, FROM, GROUP BY, WHERE, HAVING, ORDER BY',
                'SELECT, FROM, WHERE, HAVING, GROUP BY, ORDER BY',
                'SELECT, FROM, WHERE, GROUP BY, HAVING, ORDER BY',
                'FROM, WHERE, GROUP BY, HAVING, SELECT, ORDER BY',
            ],
            'correct_option' => 2,
            'explanation' => 'You write `SELECT … FROM … WHERE … GROUP BY … HAVING … ORDER BY`. The last option is the order the database *runs* them in, but you can\'t write them that way.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Books per author',
            'prompt' => 'For each `author`, show how many books the shop stocks by them.',
            'starter_code' => "SELECT author, COUNT(*)\nFROM books;",
            'solution' => 'SELECT author, COUNT(*) AS books FROM books GROUP BY author;',
            'hint' => 'You want one row per author, so group by `author`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Average price per genre',
            'prompt' => 'Show each `genre` with the average price of its books, rounded to **2 decimal places**.',
            'starter_code' => null,
            'solution' => 'SELECT genre, ROUND(AVG(price), 2) AS average_price FROM books GROUP BY genre;',
            'hint' => 'Group by `genre` and wrap the average in `ROUND(…, 2)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Regular customers',
            'prompt' => 'Using only the `orders` table, list the `customer_id` of every customer who has placed **at least 3 orders**, with how many orders they placed.',
            'starter_code' => null,
            'solution' => 'SELECT customer_id, COUNT(*) AS orders FROM orders GROUP BY customer_id HAVING COUNT(*) >= 3;',
            'hint' => 'Group by `customer_id`, then keep the groups with `HAVING COUNT(*) >= 3`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Order totals',
            'prompt' => 'For every order in `order_items`, show the `order_id` and the order\'s total value: the sum of `quantity * unit_price`, rounded to 2 decimal places.',
            'starter_code' => null,
            'solution' => 'SELECT order_id, ROUND(SUM(quantity * unit_price), 2) AS total FROM order_items GROUP BY order_id;',
            'hint' => 'Group by `order_id` and add up `quantity * unit_price` with `SUM`.',
            'order_matters' => false,
        ],
        [
            'title' => 'The busiest day',
            'prompt' => 'Which date had the most orders? Show that one `ordered_on` date and the number of orders placed on it.',
            'starter_code' => null,
            'solution' => 'SELECT ordered_on, COUNT(*) AS orders FROM orders GROUP BY ordered_on ORDER BY COUNT(*) DESC LIMIT 1;',
            'hint' => 'Count orders per `ordered_on` date, sort the biggest count first, and keep one row with `LIMIT 1`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Pricey genres in stock',
            'prompt' => 'Looking only at books that are **in stock**, find the genres whose average price is **above 14**. Show the `genre` and the average price rounded to 2 places, highest average first.',
            'starter_code' => null,
            'solution' => 'SELECT genre, ROUND(AVG(price), 2) AS average_price FROM books WHERE in_stock = 1 GROUP BY genre HAVING AVG(price) > 14 ORDER BY average_price DESC;',
            'hint' => '`WHERE in_stock = 1` before grouping, `HAVING AVG(price) > 14` after, then `ORDER BY` the average, descending.',
            'order_matters' => true,
        ],
    ],
];
