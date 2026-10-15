-- Dataset for the Advanced SQL course: the bookshop from SQL lessons 2-10 (books, customers,
-- orders and order_items are unchanged), plus staff, categories, wishlists, reviews, site visits,
-- app events, a supplier price feed, sales targets, stock levels and orders taken on paper.
-- Loaded into a fresh in-memory SQLite database before every sandbox run.

CREATE TABLE books (
    id             INTEGER PRIMARY KEY,
    title          TEXT    NOT NULL,
    author         TEXT    NOT NULL,
    genre          TEXT    NOT NULL,
    published_year INTEGER NOT NULL,
    pages          INTEGER NOT NULL,
    price          REAL    NOT NULL,
    in_stock       INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE customers (
    id        INTEGER PRIMARY KEY,
    name      TEXT NOT NULL,
    email     TEXT NOT NULL,
    city      TEXT,
    joined_on TEXT NOT NULL
);

CREATE TABLE orders (
    id          INTEGER PRIMARY KEY,
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    ordered_on  TEXT    NOT NULL,
    shipped_on  TEXT
);

CREATE TABLE order_items (
    order_id   INTEGER NOT NULL REFERENCES orders (id),
    book_id    INTEGER NOT NULL REFERENCES books (id),
    quantity   INTEGER NOT NULL,
    unit_price REAL    NOT NULL,
    PRIMARY KEY (order_id, book_id)
);

INSERT INTO books (id, title, author, genre, published_year, pages, price, in_stock) VALUES
    (1,  'Pride and Prejudice',                   'Jane Austen',          'Romance',         1813, 432, 9.99,  1),
    (2,  'Frankenstein',                          'Mary Shelley',         'Horror',          1818, 280, 8.50,  1),
    (3,  'Nineteen Eighty-Four',                  'George Orwell',        'Science Fiction', 1949, 328, 12.99, 1),
    (4,  'The Hobbit',                            'J.R.R. Tolkien',       'Fantasy',         1937, 310, 14.99, 1),
    (5,  'The Fellowship of the Ring',            'J.R.R. Tolkien',       'Fantasy',         1954, 423, 18.99, 0),
    (6,  'Dune',                                  'Frank Herbert',        'Science Fiction', 1965, 612, 19.99, 1),
    (7,  'The Left Hand of Darkness',             'Ursula K. Le Guin',    'Science Fiction', 1969, 304, 15.50, 1),
    (8,  'A Wizard of Earthsea',                  'Ursula K. Le Guin',    'Fantasy',         1968, 183, 11.25, 1),
    (9,  'Beloved',                               'Toni Morrison',        'Literary Fiction', 1987, 324, 16.00, 0),
    (10, 'The Name of the Wind',                  'Patrick Rothfuss',     'Fantasy',         2007, 662, 21.50, 1),
    (11, 'Dracula',                               'Bram Stoker',          'Horror',          1897, 418, 7.99,  1),
    (12, 'The Remains of the Day',                'Kazuo Ishiguro',       'Literary Fiction', 1989, 258, 13.75, 1),
    (13, 'Neuromancer',                           'William Gibson',       'Science Fiction', 1984, 271, 14.00, 0),
    (14, 'Jonathan Strange & Mr Norrell',         'Susanna Clarke',       'Fantasy',         2004, 782, 22.00, 1),
    (15, 'The Haunting of Hill House',            'Shirley Jackson',      'Horror',          1959, 246, 12.50, 1),
    (16, 'Circe',                                 'Madeline Miller',      'Fantasy',         2018, 393, 13.99, 1);

INSERT INTO customers (id, name, email, city, joined_on) VALUES
    (1,  'Amara Okafor',     'amara@example.com',       'Leeds',      '2025-11-02'),
    (2,  'Ben Hartley',      'ben.h@example.com',       'London',     '2025-12-15'),
    (3,  'Siobhan O''Brien', 'siobhan@example.com',     'Dublin',     '2026-01-05'),
    (4,  'Daniel Kim',       'dkim@example.com',        NULL,         '2026-01-20'),
    (5,  'Elena Rossi',      'elena.rossi@example.com', 'London',     '2026-02-01'),
    (6,  'Farid Haddad',     'farid@example.com',       'Manchester', '2026-02-11'),
    (7,  'Grace Thompson',   'grace.t@example.com',     NULL,         '2026-02-28'),
    (8,  'Hiroshi Tanaka',   'hiroshi@example.com',     'Leeds',      '2026-03-03'),
    (9,  'Isla Murray',      'isla@example.com',        'Edinburgh',  '2026-03-19'),
    (10, 'Jonas Weber',      'jonas@example.com',       'London',     '2026-04-02');

INSERT INTO orders (id, customer_id, ordered_on, shipped_on) VALUES
    (1,  1, '2026-01-08', '2026-01-10'),
    (2,  2, '2026-01-14', '2026-01-15'),
    (3,  3, '2026-01-22', '2026-01-26'),
    (4,  1, '2026-02-03', '2026-02-05'),
    (5,  4, '2026-02-09', '2026-02-11'),
    (6,  5, '2026-02-14', '2026-02-16'),
    (7,  2, '2026-02-20', '2026-02-23'),
    (8,  6, '2026-02-27', '2026-03-02'),
    (9,  3, '2026-03-02', '2026-03-04'),
    (10, 7, '2026-03-07', '2026-03-09'),
    (11, 1, '2026-03-07', '2026-03-10'),
    (12, 8, '2026-03-14', '2026-03-16'),
    (13, 5, '2026-03-14', '2026-03-17'),
    (14, 2, '2026-03-14', '2026-03-18'),
    (15, 6, '2026-03-21', '2026-03-24'),
    (16, 4, '2026-03-29', '2026-04-01'),
    (17, 8, '2026-04-04', NULL),
    (18, 3, '2026-04-06', NULL),
    (19, 1, '2026-04-07', NULL),
    (20, 7, '2026-04-07', NULL);

INSERT INTO order_items (order_id, book_id, quantity, unit_price) VALUES
    (1,  4,  1, 14.99), (1,  1,  1, 9.99),
    (2,  6,  1, 19.99),
    (3,  10, 1, 21.50), (3,  8,  1, 11.25), (3,  2,  2, 8.50),
    (4,  16, 1, 13.99),
    (5,  3,  1, 12.99), (5,  11, 1, 7.99),
    (6,  4,  2, 11.99), (6,  5,  1, 18.99),
    (7,  14, 1, 22.00),
    (8,  7,  1, 15.50), (8,  6,  1, 19.99),
    (9,  1,  1, 9.99),  (9,  15, 1, 12.50),
    (10, 2,  1, 8.50),
    (11, 10, 1, 21.50), (11, 5,  1, 18.99), (11, 4,  1, 14.99),
    (12, 6,  1, 15.99), (12, 3,  1, 12.99),
    (13, 16, 2, 13.99),
    (14, 11, 3, 7.99),  (14, 2,  1, 8.50),
    (15, 8,  1, 11.25), (15, 7,  1, 15.50), (15, 14, 1, 22.00),
    (16, 15, 1, 12.50),
    (17, 6,  1, 19.99), (17, 1,  2, 9.99),
    (18, 3,  1, 12.99),
    (19, 16, 1, 13.99), (19, 10, 1, 21.50),
    (20, 4,  1, 14.99), (20, 8,  2, 11.25);

CREATE TABLE staff (
    id         INTEGER PRIMARY KEY,
    name       TEXT    NOT NULL,
    role       TEXT    NOT NULL,
    manager_id INTEGER REFERENCES staff (id),
    city       TEXT,
    hired_on   TEXT    NOT NULL,
    salary     INTEGER NOT NULL
);

CREATE TABLE categories (
    id        INTEGER PRIMARY KEY,
    name      TEXT    NOT NULL,
    parent_id INTEGER REFERENCES categories (id)
);

CREATE TABLE book_categories (
    book_id     INTEGER NOT NULL REFERENCES books (id),
    category_id INTEGER NOT NULL REFERENCES categories (id),
    PRIMARY KEY (book_id, category_id)
);

CREATE TABLE wishlist (
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    book_id     INTEGER NOT NULL REFERENCES books (id),
    added_on    TEXT    NOT NULL,
    PRIMARY KEY (customer_id, book_id)
);

CREATE TABLE reviews (
    id          INTEGER PRIMARY KEY,
    book_id     INTEGER NOT NULL REFERENCES books (id),
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    rating      INTEGER CHECK (rating BETWEEN 1 AND 5),
    body        TEXT    NOT NULL,
    reviewed_on TEXT    NOT NULL
);

CREATE TABLE visits (
    customer_id INTEGER NOT NULL REFERENCES customers (id),
    visited_on  TEXT    NOT NULL,
    PRIMARY KEY (customer_id, visited_on)
);

CREATE TABLE events (
    id          INTEGER PRIMARY KEY,
    customer_id INTEGER REFERENCES customers (id),
    happened_at TEXT    NOT NULL,
    payload     TEXT    NOT NULL
);

CREATE TABLE supplier_prices (
    book_id INTEGER NOT NULL,
    price   REAL    NOT NULL,
    sent_at TEXT    NOT NULL
);

CREATE TABLE sales_targets (
    genre TEXT PRIMARY KEY,
    jan   INTEGER NOT NULL,
    feb   INTEGER NOT NULL,
    mar   INTEGER NOT NULL,
    apr   INTEGER NOT NULL
);

CREATE TABLE stock_levels (
    book_id    INTEGER PRIMARY KEY REFERENCES books (id),
    quantity   INTEGER NOT NULL,
    updated_on TEXT    NOT NULL
);

CREATE TABLE paper_orders (
    order_ref      TEXT PRIMARY KEY,
    customer_name  TEXT NOT NULL,
    customer_email TEXT NOT NULL,
    customer_city  TEXT,
    book_1         TEXT NOT NULL,
    book_2         TEXT,
    book_3         TEXT
);

INSERT INTO staff (id, name, role, manager_id, city, hired_on, salary) VALUES
    (1,  'Margaret Lin',  'Owner',            NULL, 'London',  '2019-03-01', 62000),
    (2,  'Tom Becker',    'Shop Manager',     1,    'London',  '2020-06-15', 41000),
    (3,  'Priya Shah',    'Online Manager',   1,    'Leeds',   '2021-01-10', 43000),
    (4,  'Owen Price',    'Bookseller',       2,    'London',  '2022-04-04', 26000),
    (5,  'Chloe Martin',  'Bookseller',       2,    'London',  '2023-09-18', 25000),
    (6,  'Ravi Patel',    'Warehouse Lead',   3,    'Leeds',   '2021-08-02', 31000),
    (7,  'Nina Kowalski', 'Packer',           6,    'Leeds',   '2024-02-12', 22000),
    (8,  'Sam Doyle',     'Packer',           6,    'Leeds',   '2025-05-06', 22000),
    (9,  'Lucy Grant',    'Web Developer',    3,    'Bristol', '2022-11-21', 45000),
    (10, 'Ahmed Yusuf',   'Customer Support', 3,    NULL,      '2025-10-01', 24000);

INSERT INTO categories (id, name, parent_id) VALUES
    (1,  'Fiction',          NULL),
    (2,  'Fantasy',          1),
    (3,  'Epic Fantasy',     2),
    (4,  'Science Fiction',  1),
    (5,  'Horror',           1),
    (6,  'Gothic',           5),
    (7,  'Romance',          1),
    (8,  'Literary Fiction', 1),
    (9,  'Classics',         NULL),
    (10, 'Non-fiction',      NULL),
    (11, 'History',          10),
    (12, 'Myth Retellings',  2);

INSERT INTO book_categories (book_id, category_id) VALUES
    (1, 7), (1, 9),
    (2, 6), (2, 9),
    (3, 4), (3, 9),
    (4, 2),
    (5, 3),
    (6, 4),
    (7, 4),
    (8, 2),
    (9, 8),
    (10, 3),
    (11, 6), (11, 9),
    (12, 8),
    (13, 4),
    (14, 2),
    (15, 5),
    (16, 12);

INSERT INTO wishlist (customer_id, book_id, added_on) VALUES
    (1,  6,  '2026-01-20'),
    (1,  14, '2026-02-10'),
    (2,  6,  '2026-01-02'),
    (3,  13, '2026-02-01'),
    (4,  9,  '2026-02-15'),
    (5,  16, '2026-01-30'),
    (6,  12, '2026-03-01'),
    (7,  1,  '2026-03-01'),
    (9,  4,  '2026-03-20'),
    (9,  10, '2026-03-20'),
    (10, 13, '2026-04-03');

INSERT INTO reviews (id, book_id, customer_id, rating, body, reviewed_on) VALUES
    (1,  4,  1, 5,    'A warm, funny adventure. Perfect for reading aloud.',                         '2026-01-15'),
    (2,  1,  1, 4,    'Sharp and witty. The dialogue still sparkles.',                               '2026-01-16'),
    (3,  6,  2, 5,    'An epic of politics, religion and ecology on a desert planet.',               '2026-01-20'),
    (4,  10, 3, 4,    'Beautiful prose, slow start. The magic system is clever.',                    '2026-01-30'),
    (5,  2,  3, NULL, 'Read it in one sitting during a storm. Creepy.',                              '2026-01-31'),
    (6,  2,  3, NULL, 'Read it in one sitting during a storm. Creepy.',                              '2026-01-31'),
    (7,  16, 1, 5,    'A goddess finds her own voice. A gorgeous retelling of the myth.',            '2026-02-12'),
    (8,  3,  4, 3,    'Bleak and important, but hard going.',                                        '2026-02-14'),
    (9,  11, 4, 4,    'The original vampire story, told in letters and diary entries.',              '2026-02-15'),
    (10, 5,  5, 5,    'The journey begins. Long, but the world feels real.',                         '2026-02-25'),
    (11, 14, 2, 4,    'Magic returns to England, with footnotes. Long but delightful.',              '2026-02-28'),
    (12, 14, 2, 4,    'Magic returns to England, with footnotes. Long but delightful.',              '2026-02-28'),
    (13, 7,  6, 5,    'A thoughtful classic about gender and politics on an icy planet.',            '2026-03-06'),
    (14, 6,  6, 4,    'Dense at first, then impossible to put down. The desert feels alive.',        '2026-03-06'),
    (15, 15, 3, NULL, 'Not scary enough for me, but the house is a great character.',                '2026-03-08'),
    (16, 2,  7, 2,    'Too slow for a horror novel.',                                                '2026-03-12'),
    (17, 3,  2, 5,    'Finally read it. The politics are still relevant.',                           '2026-03-20'),
    (18, 3,  8, 4,    'Chilling. Big Brother is watching.',                                          '2026-03-21'),
    (19, 16, 5, 4,    'Lovely writing, a bit slow in the middle.',                                   '2026-03-22'),
    (20, 11, 2, 3,    'Dracula is creepy, but the letters drag on.',                                 '2026-03-25'),
    (21, 8,  6, 4,    'A school for wizards, long before it was fashionable. Short and magical.',    '2026-03-28'),
    (22, 10, 1, 5,    'The best fantasy I have read in years. Waiting for book three!',              '2026-04-01'),
    (23, 4,  7, NULL, 'My nephew loved the dragon.',                                                 '2026-04-08');

INSERT INTO visits (customer_id, visited_on) VALUES
    (1, '2026-03-01'), (1, '2026-03-02'), (1, '2026-03-03'),
    (1, '2026-03-05'), (1, '2026-03-06'),
    (1, '2026-03-10'), (1, '2026-03-11'), (1, '2026-03-12'), (1, '2026-03-13'),
    (2, '2026-03-01'),
    (2, '2026-03-03'), (2, '2026-03-04'), (2, '2026-03-05'),
    (2, '2026-03-09'),
    (5, '2026-03-02'), (5, '2026-03-03'), (5, '2026-03-04'), (5, '2026-03-05'), (5, '2026-03-06'),
    (8, '2026-03-07'), (8, '2026-03-08'),
    (8, '2026-03-14');

INSERT INTO events (id, customer_id, happened_at, payload) VALUES
    (1,  1,    '2026-04-07 09:12', '{"type": "search", "query": "name of the wind", "device": {"os": "ios"}}'),
    (2,  1,    '2026-04-07 09:13', '{"type": "view", "book_id": 10, "device": {"os": "ios"}}'),
    (3,  1,    '2026-04-07 09:15', '{"type": "add_to_cart", "book_id": 10, "quantity": 1, "device": {"os": "ios"}}'),
    (4,  1,    '2026-04-07 09:16', '{"type": "add_to_cart", "book_id": 16, "quantity": 1, "device": {"os": "ios"}}'),
    (5,  1,    '2026-04-07 09:20', '{"type": "checkout", "order_id": 19, "book_ids": [16, 10], "device": {"os": "ios"}}'),
    (6,  7,    '2026-04-07 18:02', '{"type": "search", "query": "earthsea", "device": {"os": "android"}}'),
    (7,  7,    '2026-04-07 18:03', '{"type": "view", "book_id": 8, "device": {"os": "android"}}'),
    (8,  7,    '2026-04-07 18:05', '{"type": "add_to_cart", "book_id": 8, "quantity": 2, "device": {"os": "android"}}'),
    (9,  7,    '2026-04-07 18:06', '{"type": "add_to_cart", "book_id": 4, "quantity": 1, "device": {"os": "android"}}'),
    (10, 7,    '2026-04-07 18:09', '{"type": "checkout", "order_id": 20, "book_ids": [4, 8], "device": {"os": "android"}}'),
    (11, 9,    '2026-04-08 10:30', '{"type": "search", "query": "hobbit", "device": {"os": "web"}}'),
    (12, 9,    '2026-04-08 10:31', '{"type": "view", "book_id": 4, "device": {"os": "web"}}'),
    (13, 9,    '2026-04-08 10:33', '{"type": "add_to_cart", "book_id": 4, "quantity": 1, "device": {"os": "web"}}'),
    (14, 10,   '2026-04-08 11:00', '{"type": "search", "query": "cyberpunk", "device": {"os": "android"}}'),
    (15, 10,   '2026-04-08 11:01', '{"type": "search", "query": "neuromancer", "device": {"os": "android"}}'),
    (16, 10,   '2026-04-08 11:02', '{"type": "view", "book_id": 13, "device": {"os": "android"}}'),
    (17, NULL, '2026-04-08 12:00', '{"type": "search", "query": "dune", "device": {"os": "web"}}'),
    (18, NULL, '2026-04-08 12:01', '{"type": "view", "book_id": 6, "device": {"os": "web"}}');

INSERT INTO supplier_prices (book_id, price, sent_at) VALUES
    (4,  15.49, '2026-04-01 08:00'),
    (4,  15.49, '2026-04-01 08:00'),
    (6,  19.99, '2026-04-01 08:00'),
    (16, 13.99, '2026-04-02 08:00'),
    (10, 21.50, '2026-04-02 08:00'),
    (13, 13.50, '2026-04-03 08:00'),
    (13, 13.50, '2026-04-03 08:00'),
    (4,  15.99, '2026-04-05 08:00'),
    (6,  20.99, '2026-04-06 08:00'),
    (16, 14.49, '2026-04-06 08:00');

INSERT INTO sales_targets (genre, jan, feb, mar, apr) VALUES
    ('Fantasy',          60, 60, 80, 80),
    ('Science Fiction',  40, 40, 50, 50),
    ('Horror',           20, 20, 30, 30),
    ('Romance',          10, 10, 15, 15),
    ('Literary Fiction', 10, 10, 10, 15);

INSERT INTO stock_levels (book_id, quantity, updated_on) VALUES
    (1,  4, '2026-04-01'),
    (2,  6, '2026-04-01'),
    (3,  3, '2026-04-01'),
    (4,  8, '2026-04-01'),
    (6,  2, '2026-04-01'),
    (7,  5, '2026-04-01'),
    (8,  7, '2026-04-01'),
    (10, 1, '2026-04-01'),
    (11, 9, '2026-04-01'),
    (14, 2, '2026-04-01'),
    (15, 4, '2026-04-01'),
    (16, 6, '2026-04-01');

INSERT INTO paper_orders (order_ref, customer_name, customer_email, customer_city, book_1, book_2, book_3) VALUES
    ('F1', 'Amara Okafor', 'amara@example.com', 'Leeds',   'Circe',        'Dune',  NULL),
    ('F2', 'Paul Mensah',  'paul@example.com',  'York',    'The Hobbit',   NULL,    NULL),
    ('F3', 'Amara Okafor', 'amara@example.com', 'Leeds',   'Dracula',      NULL,    NULL),
    ('F4', 'Rosa Diaz',    'rosa@example.com',  'Bristol', 'Beloved',      'Circe', 'Neuromancer'),
    ('F5', 'Paul Mensah',  'paul@example.com',  'Leeds',   'Frankenstein', NULL,    NULL);
