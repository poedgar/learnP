## One file for the whole app

A real app is several containers: the PHP app, a database, maybe Redis, a queue worker, a mail catcher
for development. Starting each with a long `docker run` (plus a network and volumes) is tedious and easy
to get wrong. **Docker Compose** describes them all in one file, `compose.yaml`, and starts them together:

```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      DB_HOST: db
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: shop
      MYSQL_ROOT_PASSWORD: change-me
    volumes:
      - db-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 10

volumes:
  db-data:
```

Each entry under `services` is a container, with the same settings `docker run` takes: `image` (or
`build` to build one from a Dockerfile), `ports`, `environment`, `volumes`, and more. Compose creates a
network for the project automatically, and every service can reach the others **by service name**: the
app connects to the host `db`.

## YAML is (also) JSON

Compose files are YAML, and YAML is a superset of JSON, so the same file can be written as JSON, and
`docker compose -f compose.json up` reads it happily. The sandbox can decode JSON but not YAML, so in the
challenges you'll write compose files as JSON:

```php
$compose = <<<'JSON'
{
  "services": {
    "app": { "build": ".", "ports": ["8000:8000"], "environment": { "DB_HOST": "db" } },
    "db": { "image": "mysql:8.4" }
  }
}
JSON;

$config = json_decode($compose, true);
echo implode(', ', array_keys($config['services'])) . PHP_EOL;
echo 'The app talks to ' . $config['services']['app']['environment']['DB_HOST'];
```

Lists become arrays (`["8000:8000"]`) and nested settings become objects.

## Volumes and start-up order

A **named volume** used by a service must also be declared under the top-level `volumes` key (an empty
object is enough). Bind mounts (`./src:/var/www`) don't need declaring.

`depends_on` controls start-up order. On its own it only waits for the other container to **start**,
which isn't the same as being ready: MySQL takes a few seconds before it accepts connections. With
`condition: service_healthy`, Compose waits until the dependency's `healthcheck` passes.

## Everyday commands

```bash
docker compose up -d                          # build if needed, create and start everything
docker compose ps                             # what's running
docker compose logs -f app                    # follow one service's output
docker compose exec app php artisan migrate   # run a command in a running service
docker compose down                           # stop and remove containers and the network
docker compose down -v                        # ...and the named volumes too (deletes the data!)
```

Laravel's **Sail** is exactly this: a ready-made `compose.yaml` for Laravel and a small script around
these commands (`sail up`, `sail artisan migrate`).

## Development and production

Compose is at its best on a developer's machine and in CI. A common pattern keeps the shared settings in
`compose.yaml` and development-only extras (bind-mounting the code, exposing the database port, a mail
catcher) in `compose.override.yaml`, which Compose merges in automatically when it's there.
