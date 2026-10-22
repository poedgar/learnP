## Knowing your customers

Sales reports say how the shop is doing; customer queries say **who** keeps it going. Who are the best
customers? Who bought once and never came back? Who hasn't bought at all? Throughout this lesson,
"orders" means orders that weren't cancelled.

## Customers with no orders

A `LEFT JOIN` keeps every customer, with `NULL`s where there's no matching order. Put the "not
cancelled" condition in the `ON` clause, not the `WHERE`, or customers whose only orders were cancelled
disappear from the count instead of showing 0:

```sql
SELECT c.name, COUNT(o.id) AS orders
FROM customers AS c
LEFT JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled'
GROUP BY c.id
ORDER BY orders DESC;
```

`COUNT(o.id)` counts only matched orders; `COUNT(*)` would count the `NULL` row as 1.

## Lifetime value

What has each customer spent with us? Join through to the items and add them up:

```sql
SELECT c.name, SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS spent
FROM customers AS c
JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled'
JOIN order_items AS oi ON oi.order_id = o.id
GROUP BY c.id
ORDER BY spent DESC
LIMIT 5;
```

A handful of customers often bring a large share of the revenue. Knowing who they are is worth a lot.

## Recency and frequency

Marketers sort customers by **recency** (how long since the last order), **frequency** (how many
orders) and **monetary** value: RFM. Recency needs a reference date; with today as 2026-07-01:

```sql
SELECT c.name,
       COUNT(*) AS orders,
       date(MAX(o.placed_at)) AS last_order,
       CAST(julianday('2026-07-01') - julianday(date(MAX(o.placed_at))) AS INTEGER) AS days_since
FROM customers AS c
JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled'
GROUP BY c.id
ORDER BY days_since;
```

A frequent customer who has gone quiet is worth a nudge; a new customer with one order is worth a
welcome.

## The best in each group

"The best customer in each city" is a top-1-per-group question. Rank within each city with a window
partitioned by city, then keep rank 1:

```sql
WITH spend AS (
    SELECT c.city, c.name, SUM(oi.quantity * oi.unit_price_cents) AS spent
    FROM customers AS c
    JOIN orders AS o ON o.customer_id = c.id AND o.status <> 'cancelled'
    JOIN order_items AS oi ON oi.order_id = o.id
    GROUP BY c.id
)
SELECT city, name, spent / 100.0 AS spent,
       RANK() OVER (PARTITION BY city ORDER BY spent DESC) AS place
FROM spend
ORDER BY city, place;
```

`PARTITION BY city` restarts the ranking for each city. Note that the customer without a city forms
a group of its own: `NULL` cities are partitioned together.
