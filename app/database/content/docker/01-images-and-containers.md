## "It works on my machine"

An app needs more than its code: a particular PHP version, extensions, a web server, system
libraries, settings. Set that up by hand on every laptop and server and they drift apart, until
something works in one place and not another. **Docker** packages an app together with everything it
needs into an **image**, and runs that image the same way everywhere as a **container**.

- An **image** is a read-only template: a filesystem (an operating system's basics, PHP, your code)
  plus settings such as which command to start. Images are built from a `Dockerfile` (next lesson).
- A **container** is a running instance of an image, with its own writable layer, processes and
  network. You can start many containers from one image, and delete them without touching the image.
- A **registry** stores images. Docker Hub is the default; GitHub, GitLab and every cloud have their own.

Containers aren't virtual machines: they share the host's kernel and start in a fraction of a second.

## About this course

The sandbox can't run Docker, so in this course you **write** the commands and files, and PHP checks
them. In the challenges, a command goes in a string, like this:

```php
$command = 'docker run -d -p 8080:80 --name web nginx:1.27';

$words = explode(' ', $command);
echo count($words) . ' words, the image is ' . end($words);
```

Fenced blocks marked `bash`, `dockerfile` or `yaml` are for reading; try them on a machine with Docker
installed.

## Running a container

`docker run` creates a container from an image and starts it, downloading the image first if needed:

```bash
docker run -d -p 8080:80 --name web nginx:1.27
```

| Option | Meaning |
|---|---|
| `-d` (`--detach`) | run in the background and print the container's id |
| `-p 8080:80` (`--publish`) | send the host's port 8080 to port 80 inside the container |
| `--name web` | a name to refer to it by, instead of a random one |
| `-e KEY=value` (`--env`) | set an environment variable inside the container |
| `--rm` | delete the container when it stops |
| `-it` | interactive with a terminal, for a shell or a REPL |
| `-v source:/path` (`--volume`) | mount storage (lesson 5) |

Anything after the image name is the command to run instead of the image's default:
`docker run --rm -it php:8.4-cli php -a` starts PHP's interactive shell and cleans up afterwards.

## Image names and tags

An image name has up to four parts: `registry/namespace/repository:tag`. Most are left out:

- No registry means Docker Hub (`docker.io`).
- Official images on Docker Hub live in the `library` namespace, so `nginx` is `docker.io/library/nginx`.
- No tag means **`latest`**, which is just a name, not "the newest". It changes without warning, so the
  same command can give you a different image next month.

Always **pin a tag** you chose, like `nginx:1.27` or `php:8.4-cli`. For complete certainty, pin a
**digest**, `@sha256:...`, which identifies one exact image forever.

## Looking after containers

```bash
docker ps                   # running containers (add -a for stopped ones too)
docker logs -f web          # follow a container's output
docker exec -it web sh      # open a shell inside a running container
docker stop web             # stop it
docker rm web               # delete it
docker images               # images on this machine
docker pull php:8.4-cli     # download an image without running it
```

Containers are meant to be **disposable**: anything written inside one is lost when it's removed. Keep
data in volumes and configuration in environment variables, and you can replace a container at any
time, which is what makes updates and scaling easy.
