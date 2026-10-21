## Deployed is not done

Once the app is live, you need to know when it breaks before your users tell you, and have what you
need to fix it. This lesson covers the four things that make that possible: **logs**, **error
tracking**, **metrics** with **alerts**, and **backups**, plus what to do when something goes wrong.

## Logs

Laravel's `Log` facade follows the PSR-3 levels, from least to most severe: `debug`, `info`, `notice`,
`warning`, `error`, `critical`, `alert`, `emergency`. A channel's `level` setting is the minimum it
records, so `LOG_LEVEL=warning` drops `debug`, `info` and `notice`.

```laravel
Log::info('Order placed', ['order_id' => $order->id, 'total' => $order->total]);
Log::warning('Payment retried', ['order_id' => $order->id, 'attempt' => 2]);
Log::error('Payment failed', ['order_id' => $order->id, 'reason' => $e->getMessage()]);

Log::withContext(['request_id' => $requestId]);   // added to every later log line in this request
```

Log **structured context** (ids, amounts, reasons) rather than building sentences, and send logs
somewhere searchable. A JSON line per entry is easy for log tools to index:

```php
$levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
$minimum = 'warning';

foreach ([['info', 'Order placed'], ['error', 'Payment failed']] as [$level, $message]) {
    if (array_search($level, $levels) >= array_search($minimum, $levels)) {
        echo json_encode(['level' => $level, 'message' => $message, 'order_id' => 981]) . PHP_EOL;
    }
}
```

Never log passwords, tokens, full card numbers or other secrets. Redact them before they're written.

## Errors and metrics

An **error tracker** (Sentry, Flare, Bugsnag, Laravel Nightwatch) collects every exception with its
stack trace, request and user, groups repeats together, and tells you when a new one appears. It's the
fastest way to find out what broke after a deploy.

**Metrics** are numbers over time: requests per minute, error rate, response times, queue length,
memory. For response times, look at **percentiles**, not the average: the p95 is the time 95% of
requests beat, so it shows what your slowest users experience, which an average hides.

```php
$timesMs = [80, 95, 102, 110, 120, 130, 140, 150, 900, 2400];
sort($timesMs);

$average = array_sum($timesMs) / count($timesMs);
$p95 = $timesMs[(int) ceil(0.95 * count($timesMs)) - 1];

echo "average {$average} ms, p95 {$p95} ms";
```

## Alerts

Nobody watches dashboards all day, so set **alerts** on the symptoms users feel: the site is down
(an uptime check on `/up` from outside), the error rate is high, pages are slow, the queue is backing
up. Good alerts need a sustained problem (say, error rate above 2% for 5 minutes in a row) so a single
blip doesn't wake anyone at 3 a.m., and every alert should need a human to act. Alerts that are
routinely ignored teach people to ignore all of them.

## Maintenance mode

For a risky migration or a big change, you can take the app offline briefly:

```bash
php artisan down --secret="b7a1c-staff-preview" --retry=60
php artisan up
```

Visitors get a 503 page with a `Retry-After` header. Visiting `/b7a1c-staff-preview` sets a cookie that
lets you (and only you) use the site to check it before bringing it back up. Most deploys shouldn't
need this at all if they're zero-downtime.

## Backups

A backup you've never restored is a hope, not a backup. Back up the **database** and **uploaded
files** automatically, store copies somewhere other than the server (another region or provider), and
**test restoring** them regularly. A common rule is 3-2-1: three copies, on two kinds of storage, one
off-site. Keep a range of ages, such as the last 7 daily, 4 weekly and 12 monthly backups, so you can
go back before a problem that went unnoticed for a while.

## When things go wrong

Incidents happen to everyone. What matters is the response:

1. **Mitigate first**: roll back, switch off the feature flag, scale up. Fix properly later.
2. **Communicate**: a status page and a short note to users beat silence.
3. **Write it up afterwards**, without blame: what happened, how it was noticed, how long it lasted,
   and what will stop it happening again (a test, an alert, a safer deploy step).

The write-up is how a team gets steadily more reliable.
