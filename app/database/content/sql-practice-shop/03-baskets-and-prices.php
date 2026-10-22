<?php

// Lesson 3 of the SQL practice: online shop course. The theory is in the .md file with the same name.
// Quiz answers that quote numbers were checked against database/datasets/shop.sql.

return [
    'slug' => 'baskets-and-prices',
    'title' => 'Baskets and prices',
    'summary' => 'What people buy together: basket sizes, product pairs with a self-join, products that never sold, prices that changed, coupons and the free-shipping threshold.',
    'starter_code' => "-- Lines and items per order. Try finding the average basket instead.\nSELECT o.id, COUNT(*) AS lines, SUM(oi.quantity) AS items\nFROM orders AS o\nJOIN order_items AS oi ON oi.order_id = o.id\nWHERE o.status <> 'cancelled'\nGROUP BY o.id\nORDER BY items DESC;",

    'quiz' => [
        [
            'question' => 'In the bought-together query, why join on `y.product_id > x.product_id` rather than `<>`?',
            'options' => [
                'It is faster',
                'With `<>` each pair would be counted twice, as A+B and as B+A',
                'Because `<>` isn\'t allowed in a join',
                'To include products paired with themselves',
            ],
            'correct_option' => 1,
            'explanation' => '`>` keeps exactly one ordering of each pair, and excludes a product paired with itself.',
        ],
        [
            'question' => 'Which pairs were bought together in three orders?',
            'options' => [
                'Gel Pen 3-pack with Fineliner Set, Gel Pen 3-pack with Black Ink Bottle, and Brush Pen with Ink Cartridges 10-pack',
                'Only A4 Lined Notebook with Gel Pen 3-pack',
                'Blue and Black Ink Bottles',
                'None; no pair appears three times',
            ],
            'correct_option' => 0,
            'explanation' => 'Those are the pairs the lesson\'s `HAVING COUNT(*) >= 3` query returns.',
        ],
        [
            'question' => 'Which product has never been ordered at all?',
            'options' => ['Pocket Sketchbook', 'Desk Organiser', 'Watercolour Pad', 'Brush Pen'],
            'correct_option' => 1,
            'explanation' => 'The Desk Organiser has no order lines; it\'s also no longer active.',
        ],
        [
            'question' => 'Why is `WHERE oi.product_id IS NULL` right after a `LEFT JOIN` here, when the last lesson warned about filters in `WHERE`?',
            'options' => [
                'It isn\'t right',
                'Here we want exactly the rows the join found no match for, which are the ones with NULLs',
                'Because product_id can\'t be NULL',
                'Because it is in a different table',
            ],
            'correct_option' => 1,
            'explanation' => 'Filtering the right table\'s columns for NULL is the "anti-join" pattern.',
        ],
        [
            'question' => 'The Gel Pen 3-pack costs £6.00 now. What did it cost in some of the earlier order lines?',
            'options' => ['£5.00', '£5.50', '£6.50', '£7.00'],
            'correct_option' => 1,
            'explanation' => 'Three lines were sold at £5.50 in January and February.',
        ],
        [
            'question' => 'How many orders (not cancelled) paid nothing for shipping?',
            'options' => ['4', '17', '20', '37'],
            'correct_option' => 1,
            'explanation' => '17 of the 37 orders reached £30 and shipped free; 20 paid £3.95.',
        ],
        [
            'question' => 'Orders without a coupon averaged £37.07. What does that alone tell you about coupons?',
            'options' => [
                'That coupons lose money',
                'Not much: coupon orders differ in other ways (new customers, a spring sale), so you can\'t conclude the coupon caused the difference',
                'That coupons should be removed',
                'That the query is wrong',
            ],
            'correct_option' => 1,
            'explanation' => 'Comparing groups shows a difference, not its cause.',
        ],
        [
            'question' => 'How many order lines does the biggest basket have?',
            'options' => ['2', '3', '4', '6'],
            'correct_option' => 2,
            'explanation' => 'A few orders have four different products; most have one or two.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Basket sizes',
            'prompt' => 'For orders that weren\'t cancelled, show each number of `lines` (different products in the order) and how many `orders` have that many.',
            'starter_code' => null,
            'solution' => "SELECT lines, COUNT(*) AS orders FROM (SELECT o.id, COUNT(*) AS lines FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id) GROUP BY lines;",
            'hint' => 'Count lines per order in a subquery, then group by that count.',
            'order_matters' => false,
        ],
        [
            'title' => 'Goes with gel pens',
            'prompt' => 'Show the `name` of every other product that appears in an order (not cancelled) together with the Gel Pen 3-pack (product 6), and in how many such `orders`.',
            'starter_code' => null,
            'solution' => "SELECT p.name, COUNT(*) AS orders FROM order_items AS g JOIN order_items AS other ON other.order_id = g.order_id AND other.product_id <> g.product_id JOIN orders AS o ON o.id = g.order_id AND o.status <> 'cancelled' JOIN products AS p ON p.id = other.product_id WHERE g.product_id = 6 GROUP BY p.id;",
            'hint' => 'Self-join order_items: one side is the gel pen line, the other is any different product in the same order.',
            'order_matters' => false,
        ],
        [
            'title' => 'Slow movers',
            'prompt' => 'Show the `name` and `units` sold of every **active** product that sold fewer than 3 units in orders that weren\'t cancelled. Include active products that sold none, with 0 units.',
            'starter_code' => null,
            'solution' => "SELECT p.name, COALESCE(SUM(oi.quantity), 0) AS units FROM products AS p LEFT JOIN order_items AS oi ON oi.product_id = p.id AND oi.order_id IN (SELECT id FROM orders WHERE status <> 'cancelled') WHERE p.active = 1 GROUP BY p.id HAVING COALESCE(SUM(oi.quantity), 0) < 3;",
            'hint' => 'LEFT JOIN products to their lines, keeping the "not cancelled" condition in the `ON` clause, and use `COALESCE(SUM(...), 0)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Coupons',
            'prompt' => 'For orders that weren\'t cancelled, show `coupon` (the code, or `none`), the number of `orders` and `average_order` (the average items total in pounds, rounded to 2 decimals).',
            'starter_code' => null,
            'solution' => "SELECT COALESCE(coupon_code, 'none') AS coupon, COUNT(*) AS orders, ROUND(AVG(total) / 100.0, 2) AS average_order FROM (SELECT o.id, o.coupon_code, SUM(oi.quantity * oi.unit_price_cents) AS total FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' GROUP BY o.id) GROUP BY coupon_code;",
            'hint' => 'Total each order in a subquery, then group by the coupon code.',
            'order_matters' => false,
        ],
        [
            'title' => 'So close',
            'prompt' => 'Show the `id`, items `total` and `short_by` (both in pounds) of orders that weren\'t cancelled, paid for shipping, and whose items came to at least £25 (just short of the £30 free-shipping threshold).',
            'starter_code' => null,
            'solution' => "SELECT o.id, SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS total, (3000 - SUM(oi.quantity * oi.unit_price_cents)) / 100.0 AS short_by FROM orders AS o JOIN order_items AS oi ON oi.order_id = o.id WHERE o.status <> 'cancelled' AND o.shipping_cents > 0 GROUP BY o.id HAVING SUM(oi.quantity * oi.unit_price_cents) >= 2500;",
            'hint' => 'Group orders that paid shipping, keep totals of at least 2500 pence, and subtract from 3000.',
            'order_matters' => false,
        ],
        [
            'title' => 'Category per order',
            'prompt' => 'For each category, show `category` and `orders`: the number of different orders (not cancelled) containing at least one product from it.',
            'starter_code' => null,
            'solution' => "SELECT p.category, COUNT(DISTINCT oi.order_id) AS orders FROM order_items AS oi JOIN products AS p ON p.id = oi.product_id JOIN orders AS o ON o.id = oi.order_id WHERE o.status <> 'cancelled' GROUP BY p.category;",
            'hint' => 'Count `DISTINCT order_id` per category: an order with two pens counts once for Pens.',
            'order_matters' => false,
        ],
    ],
];
