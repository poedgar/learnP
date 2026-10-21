## From your laptop to the world

On your laptop the app runs with `php artisan serve`, a local database and error pages full of stack
traces. In **production** it runs on a server (or several) that real people reach, with a different
database, real email, and no debugging output. Deploying is getting the same code to behave correctly
in each of these **environments**. Most teams also have a **staging** environment: a copy of
production for a last check before release.

The rule that makes this work: **the code is the same everywhere; only the configuration differs.**

## .env

Everything that changes between environments lives in environment variables, which Laravel reads from
the `.env` file in development:

```env
APP_NAME="Corner Books"
APP_ENV=production
APP_KEY=base64:Q2Zm...
APP_DEBUG=false
APP_URL=https://cornerbooks.test

DB_CONNECTION=mysql
DB_HOST=10.0.1.5
DB_DATABASE=cornerbooks
DB_USERNAME=cornerbooks
DB_PASSWORD="s3cret pa55"

MAIL_MAILER=postmark
```

- `.env` is **never committed**: it holds secrets, and it's different on every machine. Commit
  `.env.example` with the names and safe defaults instead.
- On a server, the variables may come from the hosting platform's settings (Laravel Cloud, Forge,
  Docker, Kubernetes) rather than a file. Laravel doesn't mind where they come from.
- Values with spaces need quotes, `#` starts a comment, and `${OTHER}` reuses another variable.

```php
$lines = ['APP_NAME="Corner Books"', '# a comment', '', 'APP_DEBUG=false', 'DB_PORT=3306 # default'];

$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    [$name, $value] = explode('=', $line, 2);
    $value = trim($value);
    $value = str_starts_with($value, '"') ? trim($value, '"') : trim(explode(' #', $value)[0]);
    $env[$name] = $value;
}

print_r($env);
```

## The settings that matter most

| Variable | In production |
|---|---|
| `APP_ENV` | `production`. Some commands (like `migrate`) ask for confirmation in production. |
| `APP_DEBUG` | `false`, always. `true` shows stack traces, queries and environment values to anyone. |
| `APP_KEY` | A random 32-byte key (`php artisan key:generate`). It encrypts cookies and sessions; never change it casually, or every user is logged out and encrypted data becomes unreadable. |
| `APP_URL` | The real `https://` address, used in emails and generated links. |
| `LOG_LEVEL` | `info` or `warning`, not `debug`. |

## Config files, and caching them

`config/*.php` files read the environment with `env()`, giving a default:

```laravel
// config/services.php
'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
],
```

The rest of the app reads **config**, never `env()` directly: `config('services.postmark.token')`.
That matters because in production you run `php artisan config:cache`, which loads every config file
once and saves the result in a single fast file. After that, `.env` isn't read at all, and any
`env()` call outside a config file returns `null`. It's a classic "works locally, broken in production"
bug.

## Secrets

Database passwords, API tokens and `APP_KEY` are **secrets**. Keep them out of the repository (a leaked
repository is the most common way secrets leak), out of logs and out of error pages. Give each
environment its own: a staging token should never work on production. When one might have leaked,
**rotate** it (make a new one and revoke the old).

Laravel can also encrypt the env file itself, so it can be committed safely:
`php artisan env:encrypt` writes `.env.production.encrypted`, and the server decrypts it with a key
stored elsewhere.
