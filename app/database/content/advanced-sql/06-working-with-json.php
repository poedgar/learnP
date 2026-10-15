<?php

// Lesson 6 of the Advanced SQL course. The theory is in the .md file with the same name.
// Each challenge's check_sql runs after the learner's SQL and after the solution;
// their results are compared. Quiz answers that quote numbers were checked against
// database/datasets/bookshop_plus.sql.

return [
    'slug' => 'working-with-json',
    'title' => 'Working with JSON',
    'summary' => 'Read values out of JSON with -> and ->>, turn arrays into rows with json_each, build and aggregate JSON, and change it with json_set.',
    'starter_code' => "-- Read values out of each event's JSON payload. Try '$.query' and '$.book_ids[0]'.\nSELECT id,\n       payload ->> '$.type' AS type,\n       payload ->> '$.device.os' AS os\nFROM events;",

    'quiz' => [
        [
            'question' => 'For a payload of `{"device": {"os": "ios"}}`, which path reads the operating system?',
            'options' => ["`'$.os'`", "`'$.device.os'`", "`'device/os'`", "`'$[device][os]'`"],
            'correct_option' => 1,
            'explanation' => '`$` is the whole document and each `.key` goes one level deeper.',
        ],
        [
            'question' => "What is the difference between `payload -> '$.query'` and `payload ->> '$.query'` when the query is the text `dune`?",
            'options' => [
                'There is none',
                '`->` gives JSON text, `"dune"` with quotes; `->>` gives the SQL text `dune`',
                '`->` gives the SQL text; `->>` gives JSON',
                '`->>` reads two levels deep',
            ],
            'correct_option' => 1,
            'explanation' => '`->` returns a piece of JSON, so text keeps its quotes. `->>` returns an ordinary SQL value, ready to compare or show.',
        ],
        [
            'question' => "What does `payload ->> '$.colour'` return for an event whose payload has no `colour` key?",
            'options' => ['An error', "`''`", '`NULL`', '0'],
            'correct_option' => 2,
            'explanation' => 'A path that doesn\'t exist gives `NULL`, so missing keys behave like any other missing value.',
        ],
        [
            'question' => "In the payloads, `book_id` is stored as a JSON number, like `{\"book_id\": 4}`. How many events does `WHERE payload ->> '$.book_id' = '4'` find?",
            'options' => ['All the events for book 4', 'None', 'Every event', 'Only the first one'],
            'correct_option' => 1,
            'explanation' => '`->>` returns the integer 4, and the integer 4 isn\'t equal to the text \'4\'. Compare with `= 4`.',
        ],
        [
            'question' => "A checkout's payload contains `\"book_ids\": [16, 10]`. How many rows does `json_each(payload, '$.book_ids')` produce for it?",
            'options' => ['1', '2', '3', '26'],
            'correct_option' => 1,
            'explanation' => '`json_each` returns one row per array element, with the element in its `value` column.',
        ],
        [
            'question' => "What does `SELECT payload ->> '$.book_ids[0]'` give for the payload `{\"book_ids\": [16, 10]}`?",
            'options' => ['10', '16', '`NULL`', "`'[16, 10]'`"],
            'correct_option' => 1,
            'explanation' => 'Array positions start at 0, so `[0]` is the first element.',
        ],
        [
            'question' => 'In which order does `json_group_array(title)` put the titles?',
            'options' => [
                'Alphabetical order',
                'The order of the `GROUP BY`',
                'No guaranteed order, unless you write `json_group_array(title ORDER BY …)`',
                'The order of the primary key, always',
            ],
            'correct_option' => 2,
            'explanation' => 'Without an `ORDER BY` inside the brackets, the order is whatever the database happens to produce.',
        ],
        [
            'question' => "A payload already has `\"os\": \"ios\"`. What does `json_insert(payload, '$.os', 'android')` return?",
            'options' => [
                'The payload with `os` changed to `android`',
                'The payload unchanged',
                'An error',
                'The payload with two `os` keys',
            ],
            'correct_option' => 1,
            'explanation' => '`json_insert` only adds values at paths that don\'t exist yet. `json_set` would overwrite it.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'What people searched for',
            'prompt' => 'For every search event, show the customer\'s `name` (empty for anonymous searches) and the search text in a column named `query`.',
            'starter_code' => null,
            'solution' => "SELECT c.name, e.payload ->> '$.query' AS query FROM events AS e LEFT JOIN customers AS c ON c.id = e.customer_id WHERE e.payload ->> '$.type' = 'search';",
            'hint' => "Filter with `payload ->> '$.type' = 'search'` and read `'$.query'`. A `LEFT JOIN` to `customers` keeps the anonymous searches.",
            'order_matters' => false,
        ],
        [
            'title' => 'Events by device',
            'prompt' => 'For each operating system in the payloads (`$.device.os`), show the `os`, its number of `events`, and the number of different known `customers` who used it.',
            'starter_code' => null,
            'solution' => "SELECT payload ->> '$.device.os' AS os, COUNT(*) AS events, COUNT(DISTINCT customer_id) AS customers FROM events GROUP BY os;",
            'hint' => "Group by `payload ->> '$.device.os'`. `COUNT(DISTINCT customer_id)` skips the anonymous events, whose `customer_id` is `NULL`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Abandoned carts',
            'prompt' => 'Show the `name` of every customer who added something to their cart but never checked out.',
            'starter_code' => null,
            'solution' => "SELECT name FROM customers WHERE id IN (SELECT customer_id FROM events WHERE payload ->> '$.type' = 'add_to_cart' EXCEPT SELECT customer_id FROM events WHERE payload ->> '$.type' = 'checkout');",
            'hint' => 'Customers with an `add_to_cart` event `EXCEPT` customers with a `checkout` event (lesson 1), then look up their names.',
            'order_matters' => false,
        ],
        [
            'title' => 'What each checkout bought',
            'prompt' => 'For every checkout event, list its `order_id` (from the payload) and the `title` of each book in its `book_ids`, one row per book.',
            'starter_code' => null,
            'solution' => "SELECT e.payload ->> '$.order_id' AS order_id, b.title FROM events AS e JOIN json_each(e.payload, '$.book_ids') AS j JOIN books AS b ON b.id = j.value WHERE e.payload ->> '$.type' = 'checkout';",
            'hint' => "`json_each(e.payload, '$.book_ids')` gives one row per book id in `value`. Join `books` on it, and read `'$.order_id'`.",
            'order_matters' => false,
        ],
        [
            'title' => 'Catalogue as JSON',
            'prompt' => 'For each `genre`, show a JSON array of its book titles in alphabetical order, in a column named `titles`.',
            'starter_code' => null,
            'solution' => 'SELECT genre, json_group_array(title ORDER BY title) AS titles FROM books GROUP BY genre;',
            'hint' => 'Group by genre and use `json_group_array(title ORDER BY title)`.',
            'order_matters' => false,
        ],
        [
            'title' => 'Fix the OS name',
            'prompt' => 'The app wrote the iPhone operating system as `ios`, but it should be `iOS`. Update every event whose `$.device.os` is `ios`, changing only that value inside the JSON.',
            'starter_code' => null,
            'solution' => "UPDATE events SET payload = json_set(payload, '$.device.os', 'iOS') WHERE payload ->> '$.device.os' = 'ios';",
            'check_sql' => "SELECT id, payload ->> '$.type', payload ->> '$.device.os' FROM events ORDER BY id",
            'hint' => "`SET payload = json_set(payload, '$.device.os', 'iOS')`, only for rows `WHERE payload ->> '$.device.os' = 'ios'`.",
            'order_matters' => false,
        ],
    ],
];
