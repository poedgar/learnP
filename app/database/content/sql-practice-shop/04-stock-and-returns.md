## Behind the shop window

Selling is half of running a shop; the other half is having things to sell and dealing with what comes
back. This lesson works with `stock_movements` (every change to stock), `returns` and the orders still
on their way.

## Stock is a sum

Inkwell doesn't store "how many are on the shelf". It stores every **movement**: deliveries in (`+`),
sales out (`-`), returns back in, and stocktake corrections. Current stock is the sum, which means
history is never lost and you can always ask how stock got where it is:

```sql
SELECT p.name, COALESCE(SUM(m.change), 0) AS stock
FROM products AS p
LEFT JOIN stock_movements AS m ON m.product_id = p.id
GROUP BY p.id
ORDER BY stock;
```

`COALESCE` turns "no movements at all" into 0 rather than `NULL`. This ledger style is how accounting
works too: balances are sums of entries.

## Stock over time

A running sum, per product, shows the stock level after each movement:

```sql
SELECT moved_on,
       change,
       reason,
       SUM(change) OVER (ORDER BY moved_on, id) AS stock
FROM stock_movements
WHERE product_id = 2
ORDER BY moved_on, id;
```

Ordering the window by `moved_on, id` makes it well defined even when two movements share a date. Add
`PARTITION BY product_id` to do every product at once.

## What's it worth

Stock ties up money. Valued at cost:

```sql
SELECT SUM(stock * cost_cents) / 100.0 AS stock_value
FROM (
    SELECT p.cost_cents, COALESCE(SUM(m.change), 0) AS stock
    FROM products AS p
    LEFT JOIN stock_movements AS m ON m.product_id = p.id
    GROUP BY p.id
);
```

## Returns

Returns are expensive: shipping both ways, and a damaged item can't be sold again (only the returns
that aren't `damaged` went back into stock). The useful number is the **return rate**: units returned
compared with units sold, per product, so a best seller with a few returns isn't mistaken for a
problem:

```sql
SELECT reason, COUNT(*) AS returns
FROM returns
GROUP BY reason;
```

## Orders still open

Orders that are `pending` (not yet sent) or `shipped` (on the way) are promises not yet kept. How long
have customers been waiting? With today as 2026-07-01:

```sql
SELECT id, status, date(placed_at) AS placed,
       CAST(julianday('2026-07-01') - julianday(date(placed_at)) AS INTEGER) AS days_waiting
FROM orders
WHERE status IN ('pending', 'shipped')
ORDER BY days_waiting DESC;
```

A pending order three weeks old is worth an apology before the customer asks.
