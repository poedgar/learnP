<?php

namespace Database\Seeders;

class GitCourseSeeder extends ContentCourseSeeder
{
    protected function course(): array
    {
        return [
            'slug' => 'git',
            'title' => 'Git',
            'language' => 'git',
            'description' => 'Keep the history of your code and work with others: commits, history, branches and merges, rebasing, remotes, and undoing mistakes. You type real Git commands into a Git that runs in your browser.',
            'position' => 21,
        ];
    }

    protected function directory(): string
    {
        return 'git';
    }

    protected function lessons(): array
    {
        return [
            '01-first-commits',
            '02-looking-at-history',
            '03-branches-and-merging',
            '04-rebasing-and-tidying-up',
            '05-remotes-and-teamwork',
            '06-undoing-mistakes',
        ];
    }
}
