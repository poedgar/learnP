## What a deploy does

Deploying a Laravel app is the same short list of steps every time. Write them down once (in a script,
a CI workflow or your host's deploy settings) and never do them by hand:

```bash
git fetch && git checkout v1.4.0                       # 1. the exact code to release
composer install --no-dev --optimize-autoloader        # 2. PHP dependencies, without dev tools
npm ci && npm run build                                # 3. front-end assets, built and versioned
php artisan migrate --force                            # 4. database changes
php artisan optimize                                   # 5. cache config, routes, views and events
php artisan queue:restart                              # 6. workers pick up the new code
```

- `--no-dev` leaves out PHPUnit, Pest and friends; `--optimize-autoloader` builds a class map so classes
  load faster.
- `npm ci` installs exactly what `package-lock.json` says (unlike `npm install`, which may update it).
- `migrate --force` is needed because Laravel asks for confirmation in production.
- `php artisan optimize` runs `config:cache`, `route:cache`, `view:cache` and `event:cache`.
- Queue workers are long-running processes that loaded the old code; `queue:restart` tells them to
  finish their current job and exit, and the process manager starts fresh ones (lesson 3).

## Versioned assets

`npm run build` (Vite) writes files like `app-3f2a91c4.js`, with a hash of the contents in the name, and
a manifest that `@vite` uses to find them. Change the code and the name changes, so browsers and CDNs can
cache assets **forever** and still never serve an old file after a deploy:

```php
$manifest = [];
foreach (['app.js' => 'console.log("v2")', 'app.css' => 'body{margin:0}'] as $name => $contents) {
    [$base, $extension] = explode('.', $name);
    $manifest[$name] = $base . '-' . substr(md5($contents), 0, 8) . '.' . $extension;
}

print_r($manifest);
```

## Zero-downtime deploys

If you update files in place, there's a moment where half the files are new and half are old, and
`composer install` can take a minute. Visitors see errors. Instead, build each release in its **own
directory**, and switch a symbolic link once it's ready:

```text
/var/www/cornerbooks/
├── current -> releases/20261021-093000     ← the web server serves current/public
├── releases/
│   ├── 20261020-170500
│   └── 20261021-093000
└── shared/
    ├── .env
    └── storage/                            ← uploads, logs and sessions survive releases
```

1. Clone into a new `releases/<timestamp>` directory, and link `shared/.env` and `shared/storage` into it.
2. Install, build and cache in there, while the old release keeps serving.
3. Run migrations.
4. Point `current` at the new release (a symlink switch is atomic: one moment old, the next new).
5. Reload PHP-FPM and restart the queue workers.
6. Delete old releases, keeping the last few for rollbacks.

**Rolling back** is pointing `current` at the previous release. Laravel Forge, Envoyer, Deployer and
Laravel Cloud all work this way.

## Migrations that don't break the running app

During a deploy, the **old** code runs against the **new** database for a moment (and during a rollback,
the reverse). So a migration must not break the code that's still running. Renaming or dropping a
column in one step breaks it. Use **expand and contract**:

1. **Expand**: add the new column (nullable, or with a default), and deploy code that writes both.
2. Backfill the old rows.
3. Deploy code that only uses the new column.
4. **Contract**: drop the old column in a later release.

Adding tables, adding nullable columns and adding indexes are safe; dropping, renaming and making a
column required are the ones to split up. On very large tables, even adding an index can lock the table
for a long time, so check how your database handles it.

## Order matters

Some steps depend on others: you can't cache config before the dependencies are installed, and you
shouldn't switch the symlink before migrations have run. Deploy tools describe steps with their
dependencies and run them in an order that respects them, stopping at the first failure so the old
release keeps serving.
