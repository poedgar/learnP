## Meet Inkwell

This course is practice on a realistic online shop. **Inkwell** sells stationery: notebooks, pens, ink,
paper and bags. The data covers January to June 2026 (take 2026-07-01 as today), and all money is in
**pence**:

| Table | One row per |
|---|---|
| `customers` | customer: `name`, `email`, `city`, `joined_on` |
| `products` | product: `name`, `category`, current `price_cents`, `cost_cents` (what Inkwell pays), `active` |
| `orders` | order: `customer_id`, `placed_at`, `status` (`delivered`, `shipped`, `pending`, `cancelled`), `coupon_code`, `shipping_cents` |
| `order_items` | product in an order: `quantity` and `unit_price_cents`, the price **at the time** |
| `returns` | returned item: `order_id`, `product_id`, `quantity`, `reason`, `returned_on` |
| `stock_movements` | change in stock: `change` (+ in, - out) and `reason` |

Two things to notice: an order's value comes from its items (`quantity * unit_price_cents`), and
cancelled orders never happened as far as sales are concerned.

## Revenue

Revenue is the value of the items sold, leaving out cancelled orders:

```sql
SELECT SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS revenue
FROM order_items AS oi
JOIN orders AS o ON o.id = oi.order_id
WHERE o.status <> 'cancelled';
```

Use `unit_price_cents` from the order, not today's `price_cents` from `products`: prices change, and
history shouldn't.

## Per month, per order

Sales per month, with the **average order value** (AOV), take two steps: first each order's total,
then the months. A CTE keeps the steps apart:

```sql
WITH order_totals AS (
    SELECT o.id, substr(o.placed_at, 1, 7) AS month, SUM(oi.quantity * oi.unit_price_cents) AS total
    FROM orders AS o
    JOIN order_items AS oi ON oi.order_id = o.id
    WHERE o.status <> 'cancelled'
    GROUP BY o.id
)
SELECT month,
       COUNT(*) AS orders,
       SUM(total) / 100.0 AS revenue,
       ROUND(AVG(total) / 100.0, 2) AS average_order
FROM order_totals
GROUP BY month
ORDER BY month;
```

Averaging the *items* instead of the orders would answer a different question (the average line, not
the average basket). Getting the grain right, one row per what, is most of the work in reports.

## Best sellers

Top products by units sold:

```sql
SELECT p.name, SUM(oi.quantity) AS units
FROM order_items AS oi
JOIN orders AS o ON o.id = oi.order_id
JOIN products AS p ON p.id = oi.product_id
WHERE o.status <> 'cancelled'
GROUP BY p.id
ORDER BY units DESC
LIMIT 5;
```

Units and revenue can rank products quite differently: a fountain pen sells less often than gel pens
but earns far more each time.

## Margin

Revenue isn't profit. The **gross margin** of a line is what the customer paid minus what the product
cost Inkwell: `quantity * (unit_price_cents - cost_cents)`. As a percentage of revenue, it shows which
categories really pay the bills:

```sql
SELECT p.category,
       SUM(oi.quantity * oi.unit_price_cents) / 100.0 AS revenue,
       ROUND(100.0 * SUM(oi.quantity * (oi.unit_price_cents - p.cost_cents)) / SUM(oi.quantity * oi.unit_price_cents), 1) AS margin_pct
FROM order_items AS oi
JOIN orders AS o ON o.id = oi.order_id
JOIN products AS p ON p.id = oi.product_id
WHERE o.status <> 'cancelled'
GROUP BY p.category
ORDER BY revenue DESC;
```
