## Images are stacks of layers

Each `RUN`, `COPY` and `ADD` instruction adds a **layer**: a snapshot of the files it changed. An image
is those layers stacked on top of the base image's own. Layers are shared, so ten images built
`FROM php:8.4-cli` store PHP once, and pushing or pulling an image only transfers layers the other side
doesn't have yet.

Layers are also **permanent**: a file added in one layer and deleted in a later one is hidden, but still
inside the image, taking up space and visible to anyone who unpacks it.

## The build cache

Docker remembers every layer it builds. On the next build it walks the Dockerfile from the top, and as
long as an instruction and its inputs are unchanged, it reuses the cached layer instantly. For `COPY`,
"inputs" means the **contents of the files copied**. The first instruction whose inputs changed is
rebuilt, and so is **everything after it**, because each layer sits on top of the previous one.

That makes the **order** of instructions matter enormously:

```dockerfile
FROM php:8.4-cli
WORKDIR /app
COPY . .
RUN composer install --no-dev
```

Every change to any file, even a typo fix in a template, invalidates `COPY . .`, so `composer install`
runs again from scratch, downloading every package. Copy the dependency files first:

```dockerfile
FROM php:8.4-cli
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts
COPY . .
```

Now `composer install` only reruns when `composer.json` or `composer.lock` changes, which is rarely. The
same goes for `package.json` and `npm ci`. A rule of thumb: **things that change least go first**.

```php
$steps = ['FROM php:8.4-cli', 'COPY composer.json composer.lock ./', 'RUN composer install', 'COPY . .', 'RUN php artisan view:cache'];
$changed = 'resources/views/home.blade.php';

foreach ($steps as $number => $step) {
    $copiesIt = str_starts_with($step, 'COPY . ') || str_contains($step, " $changed");
    if ($copiesIt) {
        echo "Rebuilt from step " . ($number + 1) . ": $step" . PHP_EOL;
        break;
    }
}
```

## .dockerignore

`COPY . .` copies the whole **build context**, the folder you built from. A `.dockerignore` file, with
patterns like `.gitignore`, keeps things out of it:

```text
.git
.env
node_modules
vendor
storage/logs/*
```

That makes builds faster (less to send and copy), keeps the cache stable (a new log file doesn't
invalidate `COPY . .`), and keeps secrets such as `.env` out of the image.

## Cleaning up in the same layer

Because deleting a file in a later layer doesn't shrink the image, cleanup must happen in the **same**
`RUN` that made the mess:

```dockerfile
# The package lists stay in the image, in the first layer:
RUN apt-get update && apt-get install -y git
RUN rm -rf /var/lib/apt/lists/*

# Gone for good:
RUN apt-get update && apt-get install -y git && rm -rf /var/lib/apt/lists/*
```

That's also why related commands are chained with `&&` into one `RUN`: fewer layers, and each layer
leaves nothing behind it doesn't need.

## Seeing the layers

`docker history shop:1.0` lists an image's layers, the instruction that made each and its size, which is
the quickest way to find what's making an image big. `docker build --no-cache` ignores the cache when you
need a completely fresh build (for example, to pick up security updates from `apt-get`).
