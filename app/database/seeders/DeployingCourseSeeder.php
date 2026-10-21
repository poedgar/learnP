<?php

namespace Database\Seeders;

class DeployingCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'deploying',
            'title' => 'Deploying and running Laravel',
            'language' => 'php',
            'description' => 'Ship and run Laravel apps with confidence: environments and secrets, zero-downtime releases, servers, workers and the scheduler, CI/CD and gradual rollouts, monitoring, backups and incidents, each practised in plain PHP.',
            'position' => 25,
        ];
    }

    protected function directory(): string
    {
        return 'deploying';
    }

    protected function lessons(): array
    {
        return [
            '01-environments-and-configuration',
            '02-build-and-release',
            '03-servers-and-processes',
            '04-ci-cd',
            '05-monitoring-and-incidents',
        ];
    }
}
