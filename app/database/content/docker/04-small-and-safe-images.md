## Why size and safety go together

Everything in an image is something to download, store, scan and keep patched. Compilers, package
managers and build tools are useful while building and a liability while running: more code for an
attacker to use and more vulnerabilities to fix. A good production image holds **only what the app needs
to run**, runs as an ordinary user, and contains no secrets.

## Smaller bases

Most official images come in flavours:

- `php:8.4-cli` / `php:8.4-fpm`: based on Debian, larger, with the usual tools.
- `php:8.4-fpm-alpine`: based on Alpine Linux, often a fraction of the size. It uses `apk` instead of
  `apt-get` and a different C library, which occasionally matters for extensions.
- `-slim` variants (for example of `node` and `python`): Debian with less installed.

## Multi-stage builds

A Dockerfile can have several `FROM` lines, each starting a new **stage**. Earlier stages do the heavy
work with all the tools they need; the final stage starts fresh from a small image and copies in only
the results with `COPY --from=stage`. Everything else is left behind:

```dockerfile
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
```

The final image has PHP, the app, its Composer dependencies and the built assets, but not Composer,
Node or `node_modules`. Only the **last** stage becomes the image.

## Don't run as root

Containers run as `root` unless told otherwise. If an attacker gets code execution in your app, root in
the container makes everything after that easier. Create an ordinary user and switch to it with `USER`,
after the steps that need root (installing packages) and before the app starts:

```dockerfile
RUN addgroup -S app && adduser -S app -G app
COPY --chown=app:app . .
USER app
```

`--chown` on `COPY` makes the copied files belong to that user.

## No secrets in images

Anything in an image can be read by anyone who can pull it, including values in `ENV` and `ARG`, which
`docker history` and `docker inspect` show plainly, and files you copied and deleted in a later layer.
So: no API keys, passwords or private tokens in the Dockerfile or the build context.

- **Runtime** secrets (database passwords, `APP_KEY`) come from the environment when the container
  starts (lesson 5), not from the image.
- **Build-time** secrets, like a token for private Composer packages, use BuildKit secret mounts, which
  make a file available to one `RUN` without storing it in any layer:

```dockerfile
RUN --mount=type=secret,id=composer_auth,target=/root/.composer/auth.json \
    composer install --no-dev
```

```bash
docker build --secret id=composer_auth,src=auth.json .
```

## Pinning and health checks

Pin base images to a version (`php:8.4-fpm-alpine`), or to a **digest** (`@sha256:...`) for builds
that must be exactly reproducible, and rebuild regularly to pick up security fixes.

A `HEALTHCHECK` lets Docker test whether the app actually works, not just whether the process is
alive; orchestrators use it to restart broken containers and to wait before sending traffic:

```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
    CMD wget -qO- http://localhost:8000/up || exit 1
```

Laravel apps have a `/up` route for exactly this.

Checking Dockerfiles for these mistakes is easy to automate, which is what linters such as hadolint do:

```php
$lines = ['FROM php:latest', 'ENV DB_PASSWORD=hunter2', 'COPY . .', 'CMD ["php-fpm"]'];

foreach ($lines as $line) {
    if (preg_match('/^FROM \S+:latest\b/', $line)) echo "Pin the base image: $line" . PHP_EOL;
    if (preg_match('/^(ENV|ARG) \S*(PASSWORD|SECRET|TOKEN)/i', $line)) echo "Secret in the image: $line" . PHP_EOL;
}
```
