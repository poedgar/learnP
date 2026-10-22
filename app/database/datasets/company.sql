-- Dataset for the SQL interview questions course: a small software company, Northwind Labs.
-- Small on purpose, with the edge cases interview questions love: ties, NULLs, duplicates and gaps.
-- Loaded into a fresh in-memory SQLite database before every sandbox run.

CREATE TABLE departments (
    id   INTEGER PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE employees (
    id            INTEGER PRIMARY KEY,
    name          TEXT    NOT NULL,
    email         TEXT    NOT NULL,
    department_id INTEGER REFERENCES departments (id),
    manager_id    INTEGER REFERENCES employees (id),
    salary        INTEGER NOT NULL,
    hired_on      TEXT    NOT NULL
);

CREATE TABLE projects (
    id            INTEGER PRIMARY KEY,
    name          TEXT    NOT NULL,
    department_id INTEGER NOT NULL REFERENCES departments (id)
);

CREATE TABLE assignments (
    employee_id INTEGER NOT NULL REFERENCES employees (id),
    project_id  INTEGER NOT NULL REFERENCES projects (id),
    hours       INTEGER NOT NULL,
    PRIMARY KEY (employee_id, project_id)
);

CREATE TABLE logins (
    id          INTEGER PRIMARY KEY,
    employee_id INTEGER NOT NULL REFERENCES employees (id),
    logged_in   TEXT    NOT NULL
);

INSERT INTO departments (id, name) VALUES
    (1, 'Engineering'),
    (2, 'Sales'),
    (3, 'Marketing'),
    (4, 'Finance'),
    (5, 'Legal');

INSERT INTO employees (id, name, email, department_id, manager_id, salary, hired_on) VALUES
    (1,  'Grace Okafor',   'grace.okafor@northwind.test',   NULL, NULL, 200000, '2015-03-02'),
    (2,  'Alan Brooks',    'alan.brooks@northwind.test',    1,    1,    140000, '2016-06-13'),
    (3,  'Ada Ferreira',   'ada.ferreira@northwind.test',   1,    2,    120000, '2018-01-08'),
    (4,  'Linus Berg',     'linus.berg@northwind.test',     1,    2,    120000, '2019-09-23'),
    (5,  'Priya Nair',     'priya.nair@northwind.test',     1,    3,    110000, '2020-02-17'),
    (6,  'Tom Walsh',      'tom.walsh@northwind.test',      1,    3,    95000,  '2021-07-05'),
    (7,  'Yuki Tanaka',    'yuki.tanaka@northwind.test',    1,    3,    95000,  '2021-07-05'),
    (8,  'Ken Adeyemi',    'ken.adeyemi@northwind.test',    1,    2,    145000, '2017-11-20'),
    (9,  'Sara Lindgren',  'sara.lindgren@northwind.test',  2,    1,    100000, '2016-09-01'),
    (10, 'Diego Morales',  'diego.morales@northwind.test',  2,    9,    70000,  '2022-04-11'),
    (11, 'Fatima Rahman',  'fatima.rahman@northwind.test',  2,    9,    85000,  '2019-05-27'),
    (12, 'Owen Price',     'owen.price@northwind.test',     2,    9,    85000,  '2020-10-12'),
    (13, 'Lena Vogel',     'lena.vogel@northwind.test',     2,    9,    105000, '2018-03-19'),
    (14, 'Marcus Reid',    'marcus.reid@northwind.test',    3,    1,    90000,  '2017-02-06'),
    (15, 'Chloe Dubois',   'chloe.dubois@northwind.test',   3,    14,   75000,  '2021-01-18'),
    (16, 'Ravi Kapoor',    'ravi.kapoor@northwind.test',    3,    13,   72000,  '2023-06-26'),
    (17, 'Helen Zhou',     'helen.zhou@northwind.test',     4,    1,    110000, '2016-11-14'),
    (18, 'Sam Osei',       'sam.osei@northwind.test',       4,    17,   80000,  '2020-08-03'),
    (19, 'Nora Quinn',     'nora.quinn@northwind.test',     4,    17,   80000,  '2022-02-21'),
    (20, 'Omar Farouk',    'omar.farouk@northwind.test',    2,    9,    62000,  '2024-01-15'),
    (21, 'Omar Farouk',    'Omar.Farouk@northwind.test',    2,    9,    62000,  '2024-01-15'),
    (22, 'Mei Lin',        'mei.lin@northwind.test',        3,    14,   68000,  '2024-09-09');

INSERT INTO projects (id, name, department_id) VALUES
    (1, 'Checkout rewrite', 1),
    (2, 'Mobile app',       1),
    (3, 'Data platform',    1),
    (4, 'Spring campaign',  3),
    (5, 'Partner deals',    2),
    (6, 'Annual audit',     4);

INSERT INTO assignments (employee_id, project_id, hours) VALUES
    (2, 1, 40), (2, 2, 30), (2, 3, 20),
    (3, 1, 120), (3, 2, 80), (3, 3, 60),
    (4, 1, 200),
    (5, 2, 150), (5, 3, 90),
    (6, 2, 160),
    (7, 3, 140),
    (8, 1, 60), (8, 2, 40), (8, 3, 100),
    (11, 5, 70), (12, 5, 90), (13, 5, 110),
    (14, 4, 50), (15, 4, 120), (16, 4, 80),
    (17, 6, 60), (18, 6, 150), (19, 6, 140),
    (3, 4, 10);

INSERT INTO logins (id, employee_id, logged_in) VALUES
    (1,  3,  '2026-10-01 08:55:00'), (2,  3,  '2026-10-02 09:02:00'), (3,  3,  '2026-10-03 08:47:00'),
    (4,  3,  '2026-10-03 13:30:00'), (5,  3,  '2026-10-04 10:15:00'), (6,  3,  '2026-10-06 09:00:00'),
    (7,  4,  '2026-10-01 09:30:00'), (8,  4,  '2026-10-03 09:10:00'), (9,  4,  '2026-10-05 09:05:00'),
    (10, 5,  '2026-10-02 08:40:00'), (11, 5,  '2026-10-03 08:41:00'), (12, 5,  '2026-10-04 08:39:00'),
    (13, 6,  '2026-10-01 10:00:00'), (14, 6,  '2026-10-01 15:20:00'), (15, 6,  '2026-10-02 10:05:00'),
    (16, 8,  '2026-10-04 07:58:00'), (17, 8,  '2026-10-05 08:01:00'), (18, 8,  '2026-10-06 08:03:00'),
    (19, 8,  '2026-10-07 07:59:00'), (20, 10, '2026-10-02 11:11:00'), (21, 11, '2026-10-01 09:45:00'),
    (22, 11, '2026-10-02 09:50:00'), (23, 13, '2026-10-05 08:30:00'), (24, 13, '2026-10-06 08:31:00'),
    (25, 13, '2026-10-07 08:29:00'), (26, 15, '2026-10-03 12:00:00'), (27, 17, '2026-10-01 07:30:00'),
    (28, 17, '2026-10-07 07:35:00'), (29, 2,  '2026-10-01 08:00:00'), (30, 2,  '2026-10-02 08:00:00');
