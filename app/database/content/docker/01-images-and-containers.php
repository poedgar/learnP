<?php

// Lesson 1 of the Docker course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.
// Docker can't run in the sandbox: learners write commands and files as strings, and the
// checks read them with the harness helpers learnp_shell_words(), learnp_docker_run() and
// learnp_dockerfile().

return [
    'slug' => 'images-and-containers',
    'title' => 'Images and containers',
    'summary' => 'What Docker images, containers and registries are, `docker run` and its common options, image names, tags and digests, and the commands for looking after containers.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter). Try adding --rm or an -e option.
        $command = 'docker run -d -p 8080:80 --name web nginx:1.27';

        $words = explode(' ', $command);
        $image = end($words);
        [$repository, $tag] = explode(':', $image) + [1 => 'latest'];

        echo "Runs $repository, version $tag, in the background as 'web'," . PHP_EOL;
        echo 'reachable at http://localhost:8080';
        PHP,

    'quiz' => [
        [
            'question' => 'What is the difference between an image and a container?',
            'options' => [
                'They are the same thing',
                'An image is a read-only template; a container is a running instance of it, with its own writable layer',
                'A container is a compressed image',
                'Images run on servers, containers on laptops',
            ],
            'correct_option' => 1,
            'explanation' => 'Many containers can run from one image, and deleting a container leaves the image alone.',
        ],
        [
            'question' => 'What does `-p 8080:80` do in `docker run -p 8080:80 nginx:1.27`?',
            'options' => [
                'Limits the container to 80 processes',
                'Sends the host\'s port 8080 to port 80 inside the container',
                'Sends the container\'s port 8080 to the host\'s port 80',
                'Sets the PHP version',
            ],
            'correct_option' => 1,
            'explanation' => 'The format is `host:container`, so http://localhost:8080 reaches nginx listening on 80 inside.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $image = 'nginx';

                $withTag = str_contains($image, ':') ? $image : "$image:latest";
                $full = str_contains($withTag, '/') ? $withTag : "docker.io/library/$withTag";

                echo $full;
                PHP,
            'options' => ['`nginx`', '`nginx:latest`', '`docker.io/library/nginx:latest`', '`docker.io/nginx`'],
            'correct_option' => 2,
            'explanation' => 'No registry means Docker Hub, official images are in `library`, and no tag means `latest`.',
        ],
        [
            'question' => 'Why is relying on the `latest` tag risky?',
            'options' => [
                '`latest` images are slower',
                '`latest` is just a name that can point to a different image tomorrow, so the same command may give a different result',
                '`latest` images are not allowed in production',
                'It isn\'t risky',
            ],
            'correct_option' => 1,
            'explanation' => 'Pin a version like `nginx:1.27`, or a digest for complete certainty.',
        ],
        [
            'question' => 'In `docker run --rm -it php:8.4-cli php -a`, what is `php -a`?',
            'options' => [
                'Options for Docker',
                'The command to run in the container instead of the image\'s default',
                'Part of the image name',
                'An environment variable',
            ],
            'correct_option' => 1,
            'explanation' => 'Everything after the image name is the command. Here it\'s PHP\'s interactive shell.',
        ],
        [
            'question' => 'What does `--rm` do?',
            'options' => [
                'Removes the image after running',
                'Deletes the container when it stops',
                'Removes all other containers',
                'Runs the container read-only',
            ],
            'correct_option' => 1,
            'explanation' => 'Handy for one-off commands, so stopped containers don\'t pile up.',
        ],
        [
            'question' => 'Which command opens a shell inside the running container named `web`?',
            'options' => ['`docker run -it web sh`', '`docker exec -it web sh`', '`docker logs web sh`', '`docker attach web --shell`'],
            'correct_option' => 1,
            'explanation' => '`exec` runs a command in a container that\'s already running; `run` would create a new container.',
        ],
        [
            'question' => 'You save a file inside a container, then remove the container and start a new one from the same image. Is the file there?',
            'options' => [
                'Yes, it\'s saved in the image',
                'No: changes inside a container are lost when it\'s removed, unless they were in a volume',
                'Only if the container was stopped first',
                'Only for files in /tmp',
            ],
            'correct_option' => 1,
            'explanation' => 'Containers are disposable. Data that must survive goes in volumes (lesson 5).',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Run a web server',
            'prompt' => 'Set `$command` to a `docker run` command that starts nginx in the background, named `web`, with the host\'s port `8080` sent to port `80` in the container, using the image `nginx` pinned to version `1.27`.',
            'starter_code' => <<<'PHP'
                <?php

                $command = 'docker run nginx';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $command = 'docker run --detach --publish 8080:80 --name web nginx:1.27';
                PHP,
            'hint' => 'You need `-d`, `-p 8080:80`, `--name web` and the image `nginx:1.27`, with the image last.',
            'checks' => [
                ['label' => 'A docker run command', 'code' => "return learnp_docker_run(\$GLOBALS['command'] ?? '') !== null ?: 'Start with docker run.';"],
                ['label' => 'Runs in the background', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('detach', \$run['flags'] ?? [], true) ?: 'Add -d to run it in the background.';"],
                ['label' => 'Publishes 8080 to 80', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('8080:80', \$run['options']['publish'] ?? [], true) ?: 'Publish the host\\'s port 8080 to the container\\'s port 80 with -p 8080:80.';"],
                ['label' => 'Named web', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn expect_same(\$run['options']['name'] ?? [], ['web']);"],
                ['label' => 'nginx pinned to 1.27', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn expect_same(\$run['image'] ?? null, 'nginx:1.27');"],
            ],
        ],
        [
            'title' => 'A database with settings',
            'prompt' => 'Set `$command` to start a MySQL database in the background, named `db`, from `mysql` pinned to version `8.4`, publishing port `3306` on the host to `3306` in the container. Pass two environment variables: `MYSQL_DATABASE` set to `shop`, and `MYSQL_ROOT_PASSWORD` set to a password of your choice.',
            'starter_code' => <<<'PHP'
                <?php

                $command = 'docker run -d --name db mysql';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $command = 'docker run -d --name db -p 3306:3306 -e MYSQL_DATABASE=shop -e MYSQL_ROOT_PASSWORD=change-me mysql:8.4';
                PHP,
            'hint' => 'Each variable gets its own `-e NAME=value`. Options go before the image name.',
            'checks' => [
                ['label' => 'In the background, named db', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('detach', \$run['flags'] ?? [], true) && (\$run['options']['name'] ?? []) === ['db'] ?: 'Run it detached and named db.';"],
                ['label' => 'mysql:8.4', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn expect_same(\$run['image'] ?? null, 'mysql:8.4');"],
                ['label' => 'Port 3306', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('3306:3306', \$run['options']['publish'] ?? [], true) ?: 'Publish port 3306 with -p 3306:3306.';"],
                ['label' => 'The database name', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('MYSQL_DATABASE=shop', \$run['options']['env'] ?? [], true) ?: 'Set MYSQL_DATABASE=shop with -e.';"],
                ['label' => 'A root password', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nforeach (\$run['options']['env'] ?? [] as \$env) {\n    if (preg_match('/^MYSQL_ROOT_PASSWORD=.+/', \$env) === 1) return true;\n}\nreturn 'Set MYSQL_ROOT_PASSWORD to a password with -e.';"],
            ],
        ],
        [
            'title' => 'A throwaway PHP shell',
            'prompt' => 'Set `$command` to run PHP\'s interactive shell, `php -a`, in a container from `php:8.4-cli`. It must be interactive with a terminal (`-it`), and the container must be deleted when it stops.',
            'starter_code' => <<<'PHP'
                <?php

                $command = 'docker run php:8.4-cli';
                PHP,
            'solution' => <<<'PHP'
                <?php

                $command = 'docker run --rm -it php:8.4-cli php -a';
                PHP,
            'hint' => '`--rm` cleans up, `-it` gives a terminal, and the command goes after the image.',
            'checks' => [
                ['label' => 'Removed when it stops', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn in_array('rm', \$run['flags'] ?? [], true) ?: 'Add --rm.';"],
                ['label' => 'Interactive with a terminal', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn array_intersect(['interactive', 'tty'], \$run['flags'] ?? []) === ['interactive', 'tty'] ?: 'Add -it (or -i -t).';"],
                ['label' => 'php -a in php:8.4-cli', 'code' => "\$run = learnp_docker_run(\$GLOBALS['command'] ?? '');\nreturn expect_same([\$run['image'] ?? null, \$run['args'] ?? []], ['php:8.4-cli', ['php', '-a']]);"],
            ],
        ],
        [
            'title' => 'Read an image name',
            'prompt' => 'Write `parseImage(string $image): array`, returning `registry`, `repository`, `tag` and `digest`. The registry is the part before the first `/` if that part contains a `.` or `:` or is `localhost`; otherwise it is `docker.io`, and a Docker Hub repository without a `/` gets `library/` in front. A digest follows `@` (otherwise `null`). The tag follows the last `:` after the last `/`; without one it is `latest`, unless there is a digest, in which case it is `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function parseImage(string $image): array
                {
                    [$repository, $tag] = explode(':', $image) + [1 => 'latest'];

                    return ['registry' => 'docker.io', 'repository' => $repository, 'tag' => $tag, 'digest' => null];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parseImage(string $image): array
                {
                    [$name, $digest] = array_pad(explode('@', $image, 2), 2, null);

                    $registry = 'docker.io';
                    $first = strstr($name, '/', true);
                    if ($first !== false && (str_contains($first, '.') || str_contains($first, ':') || $first === 'localhost')) {
                        $registry = $first;
                        $name = substr($name, strlen($first) + 1);
                    }

                    $tag = null;
                    $colon = strrpos($name, ':');
                    if ($colon !== false && $colon > (int) strrpos($name, '/')) {
                        $tag = substr($name, $colon + 1);
                        $name = substr($name, 0, $colon);
                    }

                    if ($registry === 'docker.io' && ! str_contains($name, '/')) {
                        $name = "library/$name";
                    }

                    return ['registry' => $registry, 'repository' => $name, 'tag' => $tag ?? ($digest === null ? 'latest' : null), 'digest' => $digest];
                }
                PHP,
            'hint' => 'Split off the digest at `@` first, then the registry, then the tag (a `:` only counts if it comes after the last `/`, so `localhost:5000/shop` isn\'t read as a tag).',
            'checks' => [
                ['label' => 'Official images', 'code' => "return expect_same([parseImage('nginx'), parseImage('php:8.4-cli')], [['registry' => 'docker.io', 'repository' => 'library/nginx', 'tag' => 'latest', 'digest' => null], ['registry' => 'docker.io', 'repository' => 'library/php', 'tag' => '8.4-cli', 'digest' => null]]);"],
                ['label' => 'Other namespaces and registries', 'code' => "return expect_same([parseImage('bitnami/redis:7.4'), parseImage('ghcr.io/acme/shop:2.1')], [['registry' => 'docker.io', 'repository' => 'bitnami/redis', 'tag' => '7.4', 'digest' => null], ['registry' => 'ghcr.io', 'repository' => 'acme/shop', 'tag' => '2.1', 'digest' => null]]);"],
                ['label' => 'Registries with ports', 'code' => "return expect_same([parseImage('localhost:5000/shop'), parseImage('registry.example.com:443/team/api:v3')], [['registry' => 'localhost:5000', 'repository' => 'shop', 'tag' => 'latest', 'digest' => null], ['registry' => 'registry.example.com:443', 'repository' => 'team/api', 'tag' => 'v3', 'digest' => null]]);"],
                ['label' => 'Digests', 'code' => "\$d = 'sha256:' . str_repeat('a', 64);\nreturn expect_same([parseImage(\"nginx@\$d\"), parseImage(\"nginx:1.27@\$d\")], [['registry' => 'docker.io', 'repository' => 'library/nginx', 'tag' => null, 'digest' => \$d], ['registry' => 'docker.io', 'repository' => 'library/nginx', 'tag' => '1.27', 'digest' => \$d]]);"],
            ],
        ],
        [
            'title' => 'Read a port mapping',
            'prompt' => 'Write `parsePort(string $mapping): array` for `-p` values, returning `host_ip`, `host_port`, `container_port` and `protocol`. The forms are `container`, `host:container` and `ip:host:container`, any of them optionally followed by `/tcp` or `/udp`. Missing parts are `null`, except the protocol, which defaults to `tcp`. Ports are integers; an empty host port (as in `127.0.0.1::80`) is `null`.',
            'starter_code' => <<<'PHP'
                <?php

                function parsePort(string $mapping): array
                {
                    [$host, $container] = explode(':', $mapping);

                    return ['host_ip' => null, 'host_port' => $host, 'container_port' => $container, 'protocol' => 'tcp'];
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function parsePort(string $mapping): array
                {
                    [$ports, $protocol] = array_pad(explode('/', $mapping, 2), 2, 'tcp');
                    $parts = explode(':', $ports);

                    [$ip, $host, $container] = match (count($parts)) {
                        1 => [null, null, $parts[0]],
                        2 => [null, $parts[0], $parts[1]],
                        default => [$parts[0], $parts[1], $parts[2]],
                    };

                    return [
                        'host_ip' => $ip,
                        'host_port' => $host === null || $host === '' ? null : (int) $host,
                        'container_port' => (int) $container,
                        'protocol' => $protocol,
                    ];
                }
                PHP,
            'hint' => 'Split off the protocol at `/` first, then count the parts separated by `:` to know which form it is.',
            'checks' => [
                ['label' => 'host:container', 'code' => "return expect_same(parsePort('8080:80'), ['host_ip' => null, 'host_port' => 8080, 'container_port' => 80, 'protocol' => 'tcp']);"],
                ['label' => 'Only the container port', 'code' => "return expect_same(parsePort('80'), ['host_ip' => null, 'host_port' => null, 'container_port' => 80, 'protocol' => 'tcp']);"],
                ['label' => 'An IP and a protocol', 'code' => "return expect_same([parsePort('127.0.0.1:8080:80/tcp'), parsePort('53:53/udp')], [['host_ip' => '127.0.0.1', 'host_port' => 8080, 'container_port' => 80, 'protocol' => 'tcp'], ['host_ip' => null, 'host_port' => 53, 'container_port' => 53, 'protocol' => 'udp']]);"],
                ['label' => 'An empty host port', 'code' => "return expect_same(parsePort('127.0.0.1::80'), ['host_ip' => '127.0.0.1', 'host_port' => null, 'container_port' => 80, 'protocol' => 'tcp']);"],
            ],
        ],
        [
            'title' => 'Look after a container',
            'prompt' => 'Fill `$commands` with five commands, in this order: list the running containers; follow the output of the container `web`; open an interactive shell (`sh` or `bash`) in `web`; stop `web`; delete `web`.',
            'starter_code' => <<<'PHP'
                <?php

                $commands = [
                    'docker ps',
                ];
                PHP,
            'solution' => <<<'PHP'
                <?php

                $commands = [
                    'docker ps',
                    'docker logs --follow web',
                    'docker exec -it web sh',
                    'docker stop web',
                    'docker rm web',
                ];
                PHP,
            'hint' => 'The commands are `ps`, `logs -f`, `exec -it ... sh`, `stop` and `rm`.',
            'checks' => [
                ['label' => 'Five commands', 'code' => "return expect_same(count(\$GLOBALS['commands'] ?? []), 5);"],
                ['label' => 'List running containers', 'code' => "\$w = learnp_shell_words(\$GLOBALS['commands'][0] ?? '');\nreturn in_array(\$w, [['docker', 'ps'], ['docker', 'container', 'ls']], true) ?: 'The first command should be docker ps.';"],
                ['label' => 'Follow the logs', 'code' => "\$w = learnp_shell_words(\$GLOBALS['commands'][1] ?? '');\nreturn (array_slice(\$w, 0, 2) === ['docker', 'logs'] && array_intersect(['-f', '--follow'], \$w) && end(\$w) === 'web') ?: 'The second command should follow the logs of web with docker logs -f web.';"],
                ['label' => 'A shell inside', 'code' => "\$w = learnp_shell_words(\$GLOBALS['commands'][2] ?? '');\nreturn (array_slice(\$w, 0, 2) === ['docker', 'exec'] && (array_intersect(['-it', '-ti'], \$w) !== [] || (in_array('-i', \$w, true) && in_array('-t', \$w, true))) && in_array('web', \$w, true) && in_array(end(\$w), ['sh', 'bash'], true)) ?: 'The third command should be docker exec -it web sh.';"],
                ['label' => 'Stop, then delete', 'code' => "return expect_same([learnp_shell_words(\$GLOBALS['commands'][3] ?? ''), learnp_shell_words(\$GLOBALS['commands'][4] ?? '')], [['docker', 'stop', 'web'], ['docker', 'rm', 'web']]);"],
            ],
        ],
    ],
];
