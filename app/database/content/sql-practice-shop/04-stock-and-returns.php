<?php

// Lesson 4 of the SQL practice: online shop course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/shop.sql.

return [
    'slug' => 'stock-and-returns',
    'title' => 'Stock and returns',
    'summary' => 'Stock as a sum of movements, stock levels over time with a running sum, the value of stock, return rates, and orders still waiting to be delivered.',
    'starter_code' => "-- Stock per product from the movements. Try showing only products with fewer than 15.\nSELECT p.name, COALESCE(SUM(m.change), 0) AS stock\nFROM products AS p\nLEFT JOIN stock_movements AS m ON m.product_id = p.id\nGROUP BY p.id\nORDER BY stock;",

    'quiz' => [
        [
            'question' => 'Why store stock as a list of movements instead of a single "quantity" column?',
            'options' => [
                'It uses less space',
                'Every change is kept, so you can see how stock got to its level and at any point in time',
                'Because SQLite can\'t update rows',
                'Movements are faster to read',
            ],
            'correct_option' => 1,
            'explanation' => 'The current level is just `SUM(change)`; nothing is ever overwritten.',
        ],
        [
            'question' => 'Which product has the least stock (that has ever had any)?',
            'options' => ['A4 Lined Notebook', 'Black Ink Bottle', 'Fineliner Set', 'Canvas Pencil Case'],
            'correct_option' => 1,
            'explanation' => 'Only 4 Black Ink Bottles are left; the Desk Organiser has never been stocked.',
        ],
        [
            'question' => 'Why order the running-sum window by `moved_on, id` and not just `moved_on`?',
            'options' => [
                'Because id is a number',
                'Two movements on the same day would otherwise be "tied", and the running total for them wouldn\'t be step by step',
                'It is required syntax',
                'To skip duplicates',
            ],
            'correct_option' => 1,
            'explanation' => 'With ties, the default window frame includes all tied rows at once.',
        ],
        [
            'question' => 'What does `COALESCE(SUM(m.change), 0)` give for the Desk Organiser, which has no movements?',
            'options' => ['NULL', '0', '1', 'An error'],
            'correct_option' => 1,
            'explanation' => '`SUM` of nothing is `NULL`; `COALESCE` turns it into 0.',
        ],
        [
            'question' => 'What was the most common reason for a return?',
            'options' => ['wrong item', 'damaged', 'changed mind', 'late delivery'],
            'correct_option' => 1,
            'explanation' => '4 of the 5 returns were damaged.',
        ],
        [
            'question' => 'Why compare returns with units sold instead of just counting returns?',
            'options' => [
                'It is the same thing',
                'A best seller naturally has more returns; the rate shows whether a product is really a problem',
                'Because returns have no product',
                'To make the numbers bigger',
            ],
            'correct_option' => 1,
            'explanation' => 'Two returns out of 100 sold is fine; two out of three isn\'t.',
        ],
        [
            'question' => 'How many orders are still open (pending or shipped)?',
            'options' => ['2', '3', '5', '7'],
            'correct_option' => 2,
            'explanation' => '3 pending and 2 shipped.',
        ],
        [
            'question' => 'Damaged returns don\'t go back into stock. Where does that show in the data?',
            'options' => [
                'They are deleted from returns',
                'Only returns that weren\'t damaged have a matching `return` movement in stock_movements',
                'Their quantity is negative',
                'It doesn\'t show anywhere',
            ],
            'correct_option' => 1,
            'explanation' => 'There is just one `return` movement: the item sent back as the wrong item.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Running low',
            'prompt' => 'Show the `name` and `stock` of active products with fewer than 10 in stock.',
            'starter_code' => null,
            'solution' => 'SELECT p.name, COALESCE(SUM(m.change), 0) AS stock FROM products AS p LEFT JOIN stock_movements AS m ON m.product_id = p.id WHERE p.active = 1 GROUP BY p.id HAVING COALESCE(SUM(m.change), 0) < 10;',
            'hint' => 'Sum movements per active product and filter with `HAVING`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Value by category',
            'prompt' => 'For each category, show `category` and `stock_value`: the stock on hand valued at cost, in pounds.',
            'starter_code' => null,
            'solution' => 'SELECT category, SUM(stock * cost_cents) / 100.0 AS stock_value FROM (SELECT p.category, p.cost_cents, COALESCE(SUM(m.change), 0) AS stock FROM products AS p LEFT JOIN stock_movements AS m ON m.product_id = p.id GROUP BY p.id) GROUP BY category;',
            'hint' => 'Work out stock per product in a subquery, then multiply by cost and group by category.',
            'order_matters' => false,
        ],
        [
            'title' => 'Black ink over time',
            'prompt' => 'For the Black Ink Bottle (product 10), show each movement\'s `moved_on`, `change` and the `stock` after it, ordered by `moved_on` and then `id`.',
            'starter_code' => null,
            'solution' => 'SELECT moved_on, change, SUM(change) OVER (ORDER BY moved_on, id) AS stock FROM stock_movements WHERE product_id = 10 ORDER BY moved_on, id;',
            'hint' => 'A running `SUM(change) OVER (ORDER BY moved_on, id)` for that product.',
            'order_matters' => true,
        ],
        [
            'title' => 'Return rates',
            'prompt' => 'For each product with at least one return, show its `name`, units `sold` (orders not cancelled), units `returned` and `return_pct` (returned as a percentage of sold, rounded to 1 decimal place).',
            'starter_code' => null,
            'solution' => "SELECT p.name, s.sold, r.returned, ROUND(100.0 * r.returned / s.sold, 1) AS return_pct FROM (SELECT product_id, SUM(quantity) AS returned FROM returns GROUP BY product_id) AS r JOIN (SELECT oi.product_id, SUM(oi.quantity) AS sold FROM order_items AS oi JOIN orders AS o ON o.id = oi.order_id WHERE o.status <> 'cancelled' GROUP BY oi.product_id) AS s ON s.product_id = r.product_id JOIN products AS p ON p.id = r.product_id;",
            'hint' => 'Sum returns per product and sales per product in two separate subqueries, then join them. Joining returns and order lines directly would multiply the rows.',
            'order_matters' => false,
        ],
        [
            'title' => 'Waiting customers',
            'prompt' => 'For orders still `pending` or `shipped`, show the order `id`, the customer\'s `name`, the `status` and `days_waiting` from the date it was placed until 2026-07-01.',
            'starter_code' => null,
            'solution' => "SELECT o.id, c.name, o.status, CAST(julianday('2026-07-01') - julianday(date(o.placed_at)) AS INTEGER) AS days_waiting FROM orders AS o JOIN customers AS c ON c.id = o.customer_id WHERE o.status IN ('pending', 'shipped');",
            'hint' => 'Join orders to customers, keep the open statuses and subtract the dates.',
            'order_matters' => false,
        ],
        [
            'title' => 'Weeks of cover',
            'prompt' => 'For each active product that sold at least one unit (orders not cancelled), show its `name`, current `stock`, units `sold` over the 26 weeks of data, and `weeks_of_cover`: stock divided by the weekly sales rate (`sold / 26.0`), rounded to 1 decimal place.',
            'starter_code' => null,
            'solution' => "SELECT p.name, st.stock, s.sold, ROUND(st.stock / (s.sold / 26.0), 1) AS weeks_of_cover FROM products AS p JOIN (SELECT product_id, SUM(change) AS stock FROM stock_movements GROUP BY product_id) AS st ON st.product_id = p.id JOIN (SELECT oi.product_id, SUM(oi.quantity) AS sold FROM order_items AS oi JOIN orders AS o ON o.id = oi.order_id WHERE o.status <> 'cancelled' GROUP BY oi.product_id) AS s ON s.product_id = p.id WHERE p.active = 1;",
            'hint' => 'Two aggregates in two subqueries (stock and sales), joined to products. Then `stock / (sold / 26.0)`.',
            'order_matters' => false,
        ],
    ],
];
