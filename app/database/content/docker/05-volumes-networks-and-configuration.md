## Containers forget

A container's own files vanish when it's removed, and containers are removed all the time: on every
update, every restart with new settings. Three things therefore live **outside** the container:

- **Data** that must survive (a database's files, uploads) goes in **volumes**.
- **Connections** between containers go through **networks**.
- **Configuration** (hosts, passwords, feature switches) comes from **environment variables**.

## Volumes and bind mounts

`-v source:target` attaches storage at a path inside the container. What the source is decides the kind:

- A **named volume** (`-v shop-data:/var/lib/mysql`): storage Docker manages for you. It survives the
  container, can be reused by the next one, and is the right place for databases.
- A **bind mount** (`-v ./src:/app` or an absolute path): a folder from your own machine, shared with the
  container. Perfect in development, since edits on your machine appear inside immediately.

Add `:ro` to make a mount read-only inside the container. The longer `--mount` option spells the same
things out: `--mount type=volume,source=shop-data,target=/var/lib/mysql`.

```bash
docker volume create shop-data
docker run -d --name db -v shop-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4

docker run --rm -it -v "$(pwd)":/app -w /app -p 8000:8000 php:8.4-cli php -S 0.0.0.0:8000
```

The second command serves the current folder with PHP's built-in server: change a file and refresh.

## Networks

Containers on the same user-defined **network** can reach each other by **name**: Docker runs a small
DNS server, so the app connects to the host `db`, not to an IP address that changes every restart.

```bash
docker network create shop
docker run -d --name db --network shop -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4
docker run -d --name app --network shop -e DB_HOST=db -p 8000:8000 shop:1.0
```

Note what isn't there: the database publishes no port, so only containers on the network can reach it.
And `localhost` inside a container means **that container**, never the host or another container, which
is the most common reason "it can't connect to the database".

## Configuration from the environment

The same image should run in development, staging and production. What differs between them
(database host, credentials, debug mode) is passed in when the container starts, as **environment
variables**: one at a time with `-e`, or many from a file with `--env-file .env.production`. This is one
of the "twelve-factor app" rules, and Laravel follows it: `config/database.php` reads `env('DB_HOST')`.

Env files are lines of `KEY=value`, with `#` comments:

```php
$file = "# Database\nDB_HOST=db\nDB_PORT=3306\n\nAPP_NAME=\"Corner Books\"";

$env = [];
foreach (explode("\n", $file) as $line) {
    if (trim($line) === '' || str_starts_with(trim($line), '#')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \"'");
}

print_r($env);
```

Environment variables are always **strings**, so code that reads them must convert: `'3306'` to an
integer, `'false'` to a boolean (the string `'false'` is truthy in PHP!), and it should fail loudly when
something required, like `APP_KEY`, is missing, rather than starting up half-configured.

## Logs

Containers should write logs to **standard output**, not to files inside the container, so
`docker logs` and whatever collects logs in production can see them. In Laravel that's
`LOG_CHANNEL=stderr`.
