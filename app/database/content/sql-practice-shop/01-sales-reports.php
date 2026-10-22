<?php

// Lesson 1 of the SQL practice: online shop course. The theory is in the .md file with the same name.
// Each challenge's solution is compared with the learner's result. Quiz answers that quote numbers
// were checked against database/datasets/shop.sql.

return [
    'slug' => 'sales-reports',
    'title' => 'Sales reports',
    'summary' => 'Meet the Inkwell shop data, then build the reports every shop needs: revenue without cancelled orders, sales and average order value per month, best sellers and gross margin.',
    'starter_code' => "-- Revenue from orders that weren't cancelled. Try splitting it by month.\nSELECT SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS revenue\nFROM order_items AS oi\nJOIN orders AS o ON o.id = oi.order_id\nWHERE o.status <> 'cancelled';",

    'quiz' => [
        [
            'question' => 'Why use `order_items.unit_price_cents` rather than `products.price_cents` to work out past revenue?',
            'options' => [
                'It is shorter',
                'Prices change; the order line records what the customer actually paid then',
                'products.price_cents is in pounds',
                'They are always the same',
            ],
            'correct_option' => 1,
            'explanation' => 'The Fountain Pen went up in April, for example; older orders were cheaper.',
        ],
        [
            'question' => 'How many orders were cancelled?',
            'options' => ['2', '4', '5', '7'],
            'correct_option' => 1,
            'explanation' => '`SELECT COUNT(*) FROM orders WHERE status = \'cancelled\'` gives 4.',
        ],
        [
            'question' => 'What was Inkwell\'s revenue from January to June, without cancelled orders?',
            'options' => ['£1,222.00', '£1,357.00', '£12,220.00', '£122.20'],
            'correct_option' => 0,
            'explanation' => '122,200 pence of items in orders that weren\'t cancelled.',
        ],
        [
            'question' => 'To get the average order value, why first total each order and then average?',
            'options' => [
                'Because AVG can\'t be used with JOIN',
                'Averaging order lines would give the average line, not the average basket',
                'It is faster',
                'Because orders have no prices',
            ],
            'correct_option' => 1,
            'explanation' => 'Decide the grain (one row per order) before aggregating again.',
        ],
        [
            'question' => 'Which month had the highest revenue?',
            'options' => ['January', 'February', 'April', 'June'],
            'correct_option' => 1,
            'explanation' => 'February: £285.50, just ahead of April\'s £267.00.',
        ],
        [
            'question' => 'Which product sold the most units?',
            'options' => ['Gel Pen 3-pack', 'Ink Cartridges 10-pack', 'Black Ink Bottle', 'A5 Dotted Notebook'],
            'correct_option' => 2,
            'explanation' => '12 Black Ink Bottles, ahead of 11 packs of cartridges.',
        ],
        [
            'question' => 'What is the gross margin of a line?',
            'options' => [
                '`quantity * unit_price_cents`',
                '`quantity * (unit_price_cents - cost_cents)`',
                '`unit_price_cents - shipping_cents`',
                '`price_cents - unit_price_cents`',
            ],
            'correct_option' => 1,
            'explanation' => 'What the customer paid minus what the goods cost.',
        ],
        [
            'question' => 'Which category earned the most revenue?',
            'options' => ['Notebooks', 'Pens', 'Ink', 'Paper'],
            'correct_option' => 1,
            'explanation' => 'Pens took £357.00, Notebooks £356.00: close enough that rounding could fool you.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Orders by status',
            'prompt' => 'Show each order `status` and how many `orders` have it.',
            'starter_code' => null,
            'solution' => 'SELECT status, COUNT(*) AS orders FROM orders GROUP BY status;',
            'hint' => 'Group the orders by status.',
            'order_matters' => false,
        ],
        [
            'title' => 'Order totals',
            'prompt' => 'For every order that wasn\'t cancelled, show its `id`, the customer\'s `name` and the order `total` in pounds (items only, no shipping).',
            'starter_code' => null,
            'solution' => "SELECT o.id, c.name, SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS total FROM orders AS o JOIN customers AS c ON c.id = o.customer_id JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id;",
            'hint' => 'Join orders to customers and items, keep non-cancelled orders and group by the order.',
            'order_matters' => false,
        ],
        [
            'title' => 'Revenue by product',
            'prompt' => 'For each product that has sold (in orders that weren\'t cancelled), show its `name`, `units` sold and `revenue` in pounds.',
            'starter_code' => null,
            'solution' => "SELECT p.name, SUM(oi.quantity) AS units, SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS revenue FROM order_items AS oi JOIN orders AS o ON o.id = oi.order_id JOIN products AS p ON p.id = oi.product_id WHERE o.status <> 'cancelled' GROUP BY p.id;",
            'hint' => 'Like the best sellers query, with a revenue column and without the `LIMIT`.',
            'order_matters' => false,
        ],
        [
            'title' => 'With shipping',
            'prompt' => 'For each month, show the `month` (`YYYY-MM`) and `takings`: the items **plus** shipping of the orders that weren\'t cancelled, in pounds. Count each order\'s shipping once.',
            'starter_code' => null,
            'solution' => "WITH order_totals AS (SELECT o.id, substr(o.placed_at, 1, 7) AS month, SUM(oi.quantity * oi.unit_price_cents) + o.shipping_cents AS total FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id) SELECT month, SUM(total) / 100.0 AS takings FROM order_totals GROUP BY month;",
            'hint' => 'Add `shipping_cents` after totalling each order\'s items. Joining items first and summing shipping would count it once per line.',
            'order_matters' => false,
        ],
        [
            'title' => 'Big orders',
            'prompt' => 'Show the `id` and `total` (in pounds) of orders that weren\'t cancelled and whose items came to at least £50.',
            'starter_code' => null,
            'solution' => "SELECT o.id, SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS total FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id HAVING SUM(oi.quantity * oi.unit_price_cents) >= 5000;",
            'hint' => 'Group by order and filter with `HAVING` on the total in pence (5000).',
            'order_matters' => false,
        ],
        [
            'title' => 'Margin per product',
            'prompt' => 'For each product sold in orders that weren\'t cancelled, show its `name` and `margin`: the gross margin in pounds (what customers paid minus what the products cost).',
            'starter_code' => null,
            'solution' => "SELECT p.name, SUM(oi.quantity * (oi.unit_price_cents - p.cost_cents)) / 100.0 AS margin FROM order_items AS oi JOIN orders AS o ON o.id = oi.order_id JOIN products AS p ON p.id = oi.product_id WHERE o.status <> 'cancelled' GROUP BY p.id;",
            'hint' => 'Sum `quantity * (unit_price_cents - cost_cents)` per product.',
            'order_matters' => false,
        ],
    ],
];
