## JSON in a column

The shop's app records what people do: searches, book views, items added to the cart and
checkouts. Each kind of event carries different details, so the details are stored as **JSON**
text in a single `payload` column:

```sql
SELECT id, customer_id, happened_at, payload
FROM events;
```

A search has a `query`, a view has a `book_id`, and a checkout has a list of `book_ids`. Every
event also has a `device` object with the phone's operating system. SQLite has functions to
read, filter, build and change JSON like this.

## Reading a value: -> and ->>

A **path** names a value inside JSON. `$` is the whole document, `.type` a key, and
`.device.os` a key inside a key. `->>` reads the value at a path as an ordinary SQL value:

```sql
SELECT id,
       payload ->> '$.type' AS type,
       payload ->> '$.device.os' AS os,
       payload ->> '$.book_id' AS book_id
FROM events;
```

A path that doesn't exist gives `NULL`. `->` reads the same path but returns it as **JSON text**:
`payload -> '$.device'` gives `{"os":"ios"}`, and a text value keeps its quotes. Use `->>` for
values you want to compare or show, and `->` to pass a piece of JSON on. `json_extract(payload,
'$.type')` is the older spelling of `->>`.

## Filtering and grouping by JSON values

A JSON value works anywhere a column does. The number of events of each type:

```sql
SELECT payload ->> '$.type' AS type, COUNT(*) AS events
FROM events
GROUP BY type
ORDER BY events DESC;
```

Watch the types. `book_id` is stored as a JSON number, so `->>` returns an integer:

```sql
SELECT id, payload ->> '$.type' AS type
FROM events
WHERE payload ->> '$.book_id' = 4;
```

Comparing with the text `'4'` instead finds nothing, because the number 4 and the text '4' aren't
equal.

## Arrays

Array positions start at 0. `json_array_length` counts the elements:

```sql
SELECT id,
       payload -> '$.book_ids' AS book_ids,
       payload ->> '$.book_ids[0]' AS first_book,
       json_array_length(payload, '$.book_ids') AS books
FROM events
WHERE payload ->> '$.type' = 'checkout';
```

## One row per element: json_each

`json_each` turns a JSON array (or object) into rows, one per element, with `key` and `value`
columns. Use it in `FROM` like a table, after the table it reads from:

```sql
SELECT e.id, j.key, j.value
FROM events AS e, json_each(e.payload, '$.book_ids') AS j;
```

Now the elements can be joined like any other column. The books bought in each checkout:

```sql
SELECT e.id, b.title
FROM events AS e
JOIN json_each(e.payload, '$.book_ids') AS j
JOIN books AS b ON b.id = j.value;
```

## Building JSON

`json_object` builds an object from pairs of keys and values, and `json_array` builds an array.
They can be nested:

```sql
SELECT json_object(
           'id', id,
           'title', title,
           'price', price,
           'tags', json_array(genre, published_year)
       ) AS book
FROM books
LIMIT 3;
```

## Aggregating into JSON

`json_group_array` is an aggregate: it collects a group's values into one array.
`json_group_object` collects pairs into one object. The order of the elements isn't guaranteed
unless you add `ORDER BY` inside the brackets:

```sql
SELECT genre, json_group_array(title ORDER BY published_year) AS titles
FROM books
GROUP BY genre;
```

```sql
SELECT json_group_object(name, city) AS where_priyas_team_works
FROM staff
WHERE manager_id = 3;
```

## Changing JSON

`json_set` sets a value at a path, adding it if it's missing. `json_insert` adds a value only if
the path is missing, and `json_remove` removes one. They return new JSON, so store it with an
`UPDATE`:

```sql
UPDATE events
SET payload = json_set(payload, '$.reviewed', 1)
WHERE id = 1;
SELECT payload FROM events WHERE id = 1;
```

`json_valid` returns 1 for valid JSON and 0 otherwise. A `CHECK (json_valid(payload))` keeps
broken JSON out of a column.

## When to use JSON

JSON suits details whose shape varies, such as event payloads, settings, or data from another
system. Values that every row has, and that you filter, join or index often, are better as
ordinary columns: they're checked by the table's rules, and simpler to query.

## Putting it together

Copies added to carts per book, with each book's title, from the JSON payloads:

```sql
SELECT b.title, SUM(e.payload ->> '$.quantity') AS copies_added
FROM events AS e
JOIN books AS b ON b.id = e.payload ->> '$.book_id'
WHERE e.payload ->> '$.type' = 'add_to_cart'
GROUP BY b.id
ORDER BY copies_added DESC, b.title;
```

Next, try the quiz to check your understanding, then solve the challenges.
