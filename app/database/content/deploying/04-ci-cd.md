## Let a machine check every change

**Continuous integration** (CI) runs your tests, linters and builds on a fresh machine for every push
and pull request. Nobody has to remember to run them, "works on my machine" stops being an excuse, and
a red cross on a pull request stops a broken change before it's merged.

**Continuous delivery/deployment** (CD) takes the next step: when `main` is green, deploy it, either
automatically or with one click. Small, frequent deploys are *safer* than big rare ones: each changes
little, so when something breaks you know where to look, and rolling back loses little.

## A GitHub Actions workflow

Workflows are YAML files in `.github/workflows/`. This one tests every push and pull request:

```yaml
name: tests

on:
  push:
    branches: [main]
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          coverage: none
      - uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: vendor/bin/pint --test
      - run: php artisan test --parallel
```

- `on` says when it runs; `jobs` run in parallel unless one `needs` another.
- **Caching** `vendor` keyed on the lock file's hash makes later runs much faster: the cache is reused
  until dependencies change.
- Check formatting (`pint --test`) and static analysis (PHPStan/Larastan) as well as tests: cheap checks
  that catch whole classes of bugs.
- A **matrix** (`strategy.matrix.php: ['8.3', '8.4']`) runs the same job for several versions.
- Secrets (deploy keys, tokens) go in the repository's encrypted secrets and are read as
  `${{ secrets.DEPLOY_KEY }}`, never written into the YAML.

This project has a workflow like this, too: it runs the PHP tests, the in-browser PHP checks and the
mobile app's tests and lint.

## A pipeline

A typical pipeline is a series of **stages**, each running only if the previous one passed:

1. **Lint and test** on every pull request.
2. **Build** the release (dependencies, assets) once, as an artifact.
3. **Deploy to staging** and run a quick smoke test there.
4. **Deploy to production**, perhaps after a manual approval.

Build once and deploy the **same** artifact to staging and production, so what you tested is exactly
what ships.

## Versions

Tag releases so you can tell exactly what's running and go back to it. **Semantic versioning**
(`MAJOR.MINOR.PATCH`) says what kind of change each release is: bump `PATCH` for fixes, `MINOR` for new
features that don't break anything, `MAJOR` for breaking changes. It matters most for packages and APIs
that others depend on.

```php
$versions = ['1.10.0', '1.2.3', '1.9.12', '2.0.0'];
usort($versions, 'version_compare');

echo implode(' < ', $versions);
```

Note that `1.10.0` is newer than `1.9.12`: versions compare part by part, as numbers, never as text.
Many teams write commit messages in the **Conventional Commits** style (`feat: add search`,
`fix: rounding in totals`, `feat!: drop the v1 API`), so tools can work out the next version and write
the changelog.

## Releasing gradually

Deploying the code and releasing the feature don't have to be the same moment:

- **Feature flags** ship code switched off, then turn it on for staff, then 10% of users, then everyone,
  without another deploy. Laravel Pennant manages flags.
- **Canary releases** send a small share of traffic to the new version first and watch its error rate.
- **Blue-green deploys** run the new version alongside the old one and switch all traffic at once,
  keeping the old one ready to switch back.

To put "10% of users" in a stable bucket, hash the user id: the same user always lands in the same
bucket, so their experience doesn't flicker between requests.

```php
foreach ([17, 42, 1001] as $userId) {
    $bucket = crc32("new-checkout:{$userId}") % 100;
    echo "user {$userId}: bucket {$bucket}, " . ($bucket < 10 ? 'new checkout' : 'old checkout') . PHP_EOL;
}
```
