## A recipe for an image

A **Dockerfile** is a text file of instructions that build an image, one step at a time. Each line
starts with an instruction in capitals, followed by its arguments:

```dockerfile
FROM php:8.4-cli

WORKDIR /app

COPY . .

CMD ["php", "index.php"]
```

`docker build -t shop:1.0 .` runs it in the current folder (the **build context**) and tags the result
`shop:1.0`; `docker run shop:1.0` then starts it.

## The instructions you'll use most

| Instruction | What it does |
|---|---|
| `FROM image:tag` | the base image to start from; always first |
| `WORKDIR /app` | the folder later instructions (and the container) work in, created if needed |
| `COPY src dest` | copy files from the build context into the image |
| `RUN command` | run a command while building, such as installing packages |
| `ENV KEY=value` | set an environment variable for the build and the container |
| `ARG NAME=default` | a variable available only during the build, set with `--build-arg` |
| `EXPOSE 8000` | document which port the app listens on (it doesn't publish it) |
| `USER name` | which user later instructions and the container run as |
| `CMD [...]` | the default command when a container starts |
| `ENTRYPOINT [...]` | a fixed command that `CMD` (or `docker run` arguments) are appended to |

## Exec form and shell form

`CMD`, `ENTRYPOINT` and `RUN` can be written two ways:

- **Exec form**, a JSON array: `CMD ["php", "artisan", "serve"]`. The program runs directly as the
  container's main process.
- **Shell form**, plain text: `CMD php artisan serve`. Docker wraps it in `/bin/sh -c`, so the shell is
  the main process, and it doesn't pass on the signal Docker sends to stop the container; your app gets
  killed instead of shutting down cleanly.

Use exec form for `CMD` and `ENTRYPOINT`. The array must be valid JSON, with **double** quotes:

```php
foreach (['["php", "index.php"]', "['php', 'index.php']", 'php index.php'] as $args) {
    $decoded = json_decode($args, true);
    echo str_pad($args, 22) . (is_array($decoded) ? 'exec form' : 'shell form (or broken JSON)') . PHP_EOL;
}
```

The middle one looks like an array but isn't valid JSON, so Docker quietly treats it as a shell command.

## Installing packages

Official PHP images come with helper scripts for extensions. System libraries come from the
distribution's package manager, in the **same** `RUN` as the update, so the package list is never stale:

```dockerfile
FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev \
    && docker-php-ext-install zip pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
```

`--no-install-recommends` skips optional extras, and removing the package lists in the same step keeps
them out of the image (why it must be the same step is next lesson's subject). A backslash at the end of
a line continues the instruction on the next line.

## Build arguments

`ARG` lets one Dockerfile build variations, like different PHP versions. An `ARG` before `FROM` can
even be used in the `FROM` line:

```dockerfile
ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli
```

`docker build --build-arg PHP_VERSION=8.3 .` builds the 8.3 variant. Build arguments are visible in the
image's history, so they're **not** for secrets (lesson 4 shows what to use instead).
