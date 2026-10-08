-- Dataset for SQL lessons 2-4: the bookstore from lesson 1, now with customers and their orders.
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
