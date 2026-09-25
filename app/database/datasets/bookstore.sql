-- Dataset for SQL lesson 1: a small independent bookstore.
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
