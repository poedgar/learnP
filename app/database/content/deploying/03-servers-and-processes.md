## What runs in production

`php artisan serve` is a development tool. In production, a Laravel app is usually several kinds of
process working together:

| Process | Job |
|---|---|
| A **web server** (nginx, Caddy) | accepts HTTPS connections, serves files from `public/` directly, passes PHP requests on |
| **PHP-FPM** | a pool of PHP workers that run Laravel for each request |
| **Queue workers** (`php artisan queue:work`) | run queued jobs, kept alive by a process manager |
| The **scheduler** (`php artisan schedule:run`) | started every minute by cron, runs the tasks that are due |
| A **database**, **Redis** | often on their own servers or managed services |

Platforms such as Laravel Cloud, Forge and Vapor set this up for you, but it helps to know the parts
when something goes wrong.

## nginx and PHP-FPM

The web server's **document root** is `public/`, never the project root, so `.env`, `vendor` and
`storage` can't be downloaded. Requests for a real file (an image, the built JavaScript) are served
directly; everything else goes to `public/index.php`:

```nginx
server {
    listen 443 ssl;
    server_name cornerbooks.test;
    root /var/www/cornerbooks/current/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}
```

`try_files` tries each option in turn: the file itself, a directory, and finally Laravel's front
controller. PHP-FPM keeps a pool of workers (`pm.max_children`): too few and requests queue up; too
many and the server runs out of memory. Divide the memory you can give PHP by what one worker uses.

```php
$memoryForPhpMb = 3072;      // leave the rest for the OS, database, Redis
$perWorkerMb = 60;           // measure it: ps or top during load

echo 'pm.max_children = ' . intdiv($memoryForPhpMb, $perWorkerMb);
```

## Keeping workers alive

Queue workers must run all the time, and restart if they crash or after a deploy. A process manager
does that: **Supervisor** on most servers, or **systemd**:

```ini
[program:cornerbooks-worker]
command=php /var/www/cornerbooks/current/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
numprocs=4
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/cornerbooks/shared/storage/logs/worker.log
```

`numprocs=4` runs four workers in parallel. `--max-time=3600` makes each one exit after an hour (and be
restarted), which keeps memory leaks in check. `stopwaitsecs` gives a running job time to finish before
the worker is killed.

## The scheduler

Instead of a cron entry per task, Laravel needs one, which runs every minute:

```bash
* * * * * cd /var/www/cornerbooks/current && php artisan schedule:run >> /dev/null 2>&1
```

and the tasks are defined in code:

```laravel
// routes/console.php
Schedule::command('reports:daily')->dailyAt('02:00');
Schedule::job(new PruneOldCarts)->hourly();
Schedule::command('backup:run')->cron('30 3 * * 1-5')->onOneServer();
```

A cron expression has five fields: minute, hour, day of month, month and day of week. Each field is `*`
(any), a number, a list `1,15`, a range `1-5` or a step `*/15`. `onOneServer()` uses a cache lock so a
task runs once even when every server runs the scheduler.

## Health checks

New Laravel apps answer `/up` with a 200 when the app boots. Load balancers and uptime monitors call it
to decide whether a server should get traffic. A deeper check also tests the database, cache and queue,
but keep it fast and never let it expose details.

## Logs go somewhere

On a single server, logs go to `storage/logs/laravel.log` (use the `daily` channel so files rotate). On
containers and platforms, log to **stderr** (`LOG_CHANNEL=stderr`) and let the platform collect them.
Either way, someone has to look at them: lesson 5 covers monitoring.
