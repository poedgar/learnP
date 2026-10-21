<?php

namespace Database\Seeders;

class DockerCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'docker',
            'title' => 'Docker',
            'language' => 'php',
            'label' => 'Docker',
            'description' => 'Package and run apps in containers: images and containers, Dockerfiles, layer caching, small and safe images, volumes, networks and configuration, and Docker Compose. You write the files; PHP checks them.',
            'position' => 14,
        ];
    }

    protected function directory(): string
    {
        return 'docker';
    }

    protected function lessons(): array
    {
        return [
            '01-images-and-containers',
            '02-writing-a-dockerfile',
            '03-layers-and-caching',
            '04-small-and-safe-images',
            '05-volumes-networks-and-configuration',
            '06-docker-compose',
        ];
    }
}
