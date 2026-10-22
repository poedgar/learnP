## What goes in a basket

Looking at what people buy **together** helps a shop suggest the next item ("customers also bought"),
bundle products, and decide where to set the free-shipping threshold. This lesson works at the level of
order lines. As before, cancelled orders don't count.

## Basket size

How many different products are in a typical order, and how many items in all?

```sql
SELECT o.id,
       COUNT(*) AS lines,
       SUM(oi.quantity) AS items
FROM orders AS o
JOIN order_items AS oi ON oi.order_id = o.id
WHERE o.status <> 'cancelled'
GROUP BY o.id
ORDER BY items DESC
LIMIT 5;
```

## Bought together

To find pairs of products in the same order, join `order_items` **to itself** on the order. Keeping
only pairs where the second product id is bigger than the first counts each pair once (not as both
A+B and B+A) and never pairs a product with itself:

```sql
SELECT a.name AS first, b.name AS second, COUNT(*) AS orders
FROM order_items AS x
JOIN order_items AS y ON y.order_id = x.order_id AND y.product_id > x.product_id
JOIN orders AS o ON o.id = x.order_id AND o.status <> 'cancelled'
JOIN products AS a ON a.id = x.product_id
JOIN products AS b ON b.id = y.product_id
GROUP BY x.product_id, y.product_id
HAVING COUNT(*) >= 3;
```

With thousands of orders you'd also compare against how often each product sells on its own, so that
"everyone buys ink" doesn't look like a special connection.

## Products nobody bought

Products with no sales are a `LEFT JOIN` with nothing on the right (or a `NOT EXISTS`):

```sql
SELECT p.name, p.active
FROM products AS p
LEFT JOIN order_items AS oi ON oi.product_id = p.id
WHERE oi.product_id IS NULL;
```

Here `WHERE ... IS NULL` is exactly right: it keeps the products for which no line matched.

## Prices change

`products.price_cents` is today's price, and each order line keeps the price paid then. Comparing them
shows price changes over time:

```sql
SELECT p.name, p.price_cents / 100.0 AS price_now, oi.unit_price_cents / 100.0 AS price_paid, COUNT(*) AS lines
FROM order_items AS oi
JOIN products AS p ON p.id = oi.product_id
WHERE oi.unit_price_cents <> p.price_cents
GROUP BY p.id, oi.unit_price_cents;
```

## Discounts and shipping

Two business levers show up in the `orders` table. `coupon_code` records discount codes (`SPRING10`,
`WELCOME5`), so you can compare orders with and without one. `shipping_cents` is 3.95, or 0 for orders
whose items come to £30 or more. Orders just under the threshold are interesting: those customers came
close to free shipping, and a small nudge ("add £3 more for free delivery") might have tipped them over.
