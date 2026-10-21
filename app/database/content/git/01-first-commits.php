<?php

// Lesson 1 of the Git course. The theory is in the .md file with the same name.
// Learners write shell scripts that run in the simulated Git (resources/js/lib/git). Each check
// is the body of a JavaScript function given repo(dir?), git(command) and output (see engine.js);
// it returns true or a message. Every script starts in /work on an empty machine.

return [
    'slug' => 'first-commits',
    'title' => 'First commits',
    'summary' => 'What Git is for, repositories, the staging area and commits, git status and git log, setting your name, .gitignore, and writing good commit messages.',
    'starter_code' => <<<'GIT'
        # Change these commands, then press Run (Ctrl+Enter). Try adding a second file and commit.
        git init bookshop
        cd bookshop
        echo "# Corner Books" > README.md
        git add README.md
        git commit -m "Add a README"
        git log --oneline
        GIT,

    'quiz' => [
        [
            'question' => 'What does a commit hold?',
            'options' => [
                'Only the files that changed, as a list of edits',
                'A snapshot of the project, with an author, a date, a message and a link to the commit before it',
                'A copy of the .git folder',
                'The output of git status',
            ],
            'correct_option' => 1,
            'explanation' => 'Each commit is a full snapshot (Git stores unchanged files efficiently) plus who, when, why and its parent.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git status --short
                GIT,
            'options' => ['`?? books.txt`', '`A  books.txt`', '`M  books.txt`', 'Nothing'],
            'correct_option' => 0,
            'explanation' => '`??` means untracked: Git sees the file but isn\'t keeping its history until you `git add` it.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git status --short
                GIT,
            'options' => ['`?? books.txt`', '`A  books.txt`', '`M  books.txt`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => '`A` in the first column means the new file is staged: it will be in the next commit.',
        ],
        [
            'question' => 'Why does Git have a staging area between your files and a commit?',
            'options' => [
                'It makes commits faster',
                'So you can choose exactly which changes go into each commit',
                'It is where Git keeps backups',
                'It is only needed for the first commit',
            ],
            'correct_option' => 1,
            'explanation' => 'You can stage the bug fix, commit it, then stage and commit the unrelated typo separately.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "one" > a.txt
                git add a.txt
                git commit -q -m "First"
                echo "two" > b.txt
                git add b.txt
                git commit -q -m "Second"
                git log --format=%s
                GIT,
            'options' => ['`First Second`', '`Second First`', '`Second`', '`First`'],
            'correct_option' => 1,
            'explanation' => '`git log` lists commits newest first, and `%s` is just the subject line.',
        ],
        [
            'question' => 'Which of these belongs in `.gitignore`?',
            'options' => ['`README.md`', '`composer.json`', '`.env`, which holds passwords', '`.gitignore` itself'],
            'correct_option' => 2,
            'explanation' => 'Secrets, logs and downloadable dependencies stay out of the repository. `.gitignore` itself is committed so everyone shares it.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "*.log" > .gitignore
                echo "oops" > error.log
                echo "<?php" > index.php
                git add .
                git status --short
                GIT,
            'options' => [
                'Three `A` lines: `.gitignore`, `error.log` and `index.php`',
                'Two `A` lines: `.gitignore` and `index.php`',
                'One `A` line: `index.php`',
                '`?? error.log` and two `A` lines',
            ],
            'correct_option' => 1,
            'explanation' => '`error.log` matches `*.log`, so `git add .` skips it and status doesn\'t mention it.',
        ],
        [
            'question' => 'Which commit message follows the usual conventions best?',
            'options' => [
                '`fixed stuff`',
                '`Fix the price rounding on the basket page`',
                '`I have fixed the bug where prices were rounded wrongly on the basket page and also tidied the CSS.`',
                '`WIP`',
            ],
            'correct_option' => 1,
            'explanation' => 'A short, specific subject in the imperative. Unrelated changes (the CSS) belong in their own commit.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Start a repository',
            'prompt' => 'Create a repository in a new folder called `cafe`, add a file `menu.txt` containing `Coffee 2.50`, and commit it with the message `Add the menu`.',
            'starter_code' => <<<'GIT'
                git init cafe
                cd cafe
                GIT,
            'solution' => <<<'GIT'
                git init cafe
                cd cafe
                echo "Coffee 2.50" > menu.txt
                git add menu.txt
                git commit -m "Add the menu"
                GIT,
            'hint' => 'Write the file with `echo "..." > menu.txt`, then `git add` it and `git commit -m "..."`.',
            'checks' => [
                ['label' => 'There is a repository in cafe', 'code' => <<<'JS'
                    repo('/work/cafe');
                    return true;
                    JS],
                ['label' => 'One commit: Add the menu', 'code' => <<<'JS'
                    const commits = repo('/work/cafe').commits;
                    if (commits.length !== 1) return `Expected one commit, found ${commits.length}.`;
                    return commits[0].message === 'Add the menu' || `The message is "${commits[0].message}".`;
                    JS],
                ['label' => 'menu.txt says Coffee 2.50', 'code' => <<<'JS'
                    const files = repo('/work/cafe').commit()?.files ?? {};
                    if (!('menu.txt' in files)) return 'menu.txt is not in the commit.';
                    return files['menu.txt'].trim() === 'Coffee 2.50' || `menu.txt contains "${files['menu.txt'].trim()}".`;
                    JS],
            ],
        ],
        [
            'title' => 'Only what is ready',
            'prompt' => 'The script sets up a repository with two new pages. `about.html` is finished, but `draft.html` isn\'t. Commit only `about.html`, with the message `Add the about page`, and leave `draft.html` untracked.',
            'starter_code' => <<<'GIT'
                git init site
                cd site
                echo "<h1>About us</h1>" > about.html
                echo "<h1>TODO</h1>" > draft.html
                GIT,
            'solution' => <<<'GIT'
                git init site
                cd site
                echo "<h1>About us</h1>" > about.html
                echo "<h1>TODO</h1>" > draft.html
                git add about.html
                git commit -m "Add the about page"
                GIT,
            'hint' => 'Name the file in `git add about.html` instead of using `git add .`.',
            'checks' => [
                ['label' => 'about.html is committed', 'code' => <<<'JS'
                    const commit = repo('/work/site').commit();
                    if (!commit) return 'There are no commits yet.';
                    if (commit.message !== 'Add the about page') return `The message is "${commit.message}".`;
                    return Object.keys(commit.files).join(',') === 'about.html' || `The commit holds: ${Object.keys(commit.files).join(', ')}.`;
                    JS],
                ['label' => 'draft.html is still untracked', 'code' => <<<'JS'
                    return repo('/work/site').status.untracked.includes('draft.html') || 'draft.html should still be untracked.';
                    JS],
            ],
        ],
        [
            'title' => 'Two commits',
            'prompt' => 'In a new repository `shop`, commit `index.php` (any content) with the message `Add the home page`. Then add `books.php` and commit it separately with the message `Add the books page`.',
            'starter_code' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > index.php
                echo "<?php" > books.php
                git add .
                git commit -m "Add pages"
                GIT,
            'solution' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > index.php
                git add index.php
                git commit -m "Add the home page"
                echo "<?php" > books.php
                git add books.php
                git commit -m "Add the books page"
                GIT,
            'hint' => 'Commit after creating each file: `git add` the one file, then `git commit`.',
            'checks' => [
                ['label' => 'Two commits, in order', 'code' => <<<'JS'
                    const messages = repo('/work/shop').commits.map((c) => c.message);
                    return JSON.stringify(messages) === JSON.stringify(['Add the books page', 'Add the home page']) || `The history is: ${messages.join(' ← ') || 'empty'}.`;
                    JS],
                ['label' => 'Each commit holds its own page', 'code' => <<<'JS'
                    const [second, first] = repo('/work/shop').commits;
                    if (!first || !second) return 'Make two commits.';
                    if (first.changed.join() !== 'index.php') return `The first commit changes ${first.changed.join(', ')}.`;
                    return second.changed.join() === 'books.php' || `The second commit changes ${second.changed.join(', ')}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Keep secrets out',
            'prompt' => 'The script creates `index.php`, a log file `app.log` and a secrets file `.env`. Before committing, write a `.gitignore` that ignores every `.log` file and `.env`. Then commit everything else (including `.gitignore`) with `git add .` and the message `Start the shop`.',
            'starter_code' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > index.php
                echo "debug output" > app.log
                echo "DB_PASSWORD=hunter2" > .env
                git add .
                git commit -m "Start the shop"
                GIT,
            'solution' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > index.php
                echo "debug output" > app.log
                echo "DB_PASSWORD=hunter2" > .env
                echo "*.log" > .gitignore
                echo ".env" >> .gitignore
                git add .
                git commit -m "Start the shop"
                GIT,
            'hint' => 'Write the first pattern with `>` and the second with `>>`, which adds a line instead of replacing the file. Do it before `git add .`.',
            'checks' => [
                ['label' => 'Logs and .env are ignored', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (!r.ignored('app.log') || !r.ignored('other.log')) return 'Every .log file should be ignored.';
                    return r.ignored('.env') || '.env should be ignored.';
                    JS],
                ['label' => 'The commit has the right files', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (!commit) return 'There are no commits yet.';
                    const files = Object.keys(commit.files).sort().join(', ');
                    return files === '.gitignore, index.php' || `The commit holds: ${files}.`;
                    JS],
                ['label' => 'The secret never reached the history', 'code' => <<<'JS'
                    return repo('/work/shop').commits.every((c) => !Object.values(c.files).some((content) => content.includes('hunter2'))) || 'The password is in a commit.';
                    JS],
            ],
        ],
        [
            'title' => 'Sign your work',
            'prompt' => 'In a new repository `notes`, set your name to `Grace Hopper` and your email to `grace@example.com` for this repository only (no `--global`), then commit a file `todo.txt` with the message `Add a to-do list`.',
            'starter_code' => <<<'GIT'
                git init notes
                cd notes
                echo "Buy more books" > todo.txt
                git add todo.txt
                git commit -m "Add a to-do list"
                GIT,
            'solution' => <<<'GIT'
                git init notes
                cd notes
                git config user.name "Grace Hopper"
                git config user.email grace@example.com
                echo "Buy more books" > todo.txt
                git add todo.txt
                git commit -m "Add a to-do list"
                GIT,
            'hint' => 'Use `git config user.name "..."` and `git config user.email ...` before committing.',
            'checks' => [
                ['label' => 'The repository knows who you are', 'code' => <<<'JS'
                    const r = repo('/work/notes');
                    if (r.config('user.name') !== 'Grace Hopper') return `user.name is ${r.config('user.name')}.`;
                    return r.config('user.email') === 'grace@example.com' || `user.email is ${r.config('user.email')}.`;
                    JS],
                ['label' => 'Grace wrote the commit', 'code' => <<<'JS'
                    const commit = repo('/work/notes').commit();
                    if (!commit) return 'There are no commits yet.';
                    return commit.author === 'Grace Hopper' || `The author is ${commit.author}. Set the name before committing.`;
                    JS],
                ['label' => 'Only for this repository', 'code' => <<<'JS'
                    return !/--global/.test(output + git('git config --global user.name')) || 'Leave out --global.';
                    JS],
            ],
        ],
        [
            'title' => 'Explain why',
            'prompt' => 'In a new repository `shop`, commit a file `search.php` with a two-part message: the subject `Add book search`, and a body explaining why, which must mention `customers`. Keep the subject under 50 characters with no full stop at the end.',
            'starter_code' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > search.php
                git add search.php
                git commit -m "added search."
                GIT,
            'solution' => <<<'GIT'
                git init shop
                cd shop
                echo "<?php" > search.php
                git add search.php
                git commit -m "Add book search" -m "Customers kept asking for it, and browsing 400 titles by hand was slow."
                GIT,
            'hint' => 'Each `-m` adds a paragraph: `git commit -m "subject" -m "body"`.',
            'checks' => [
                ['label' => 'The subject is Add book search', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (!commit) return 'There are no commits yet.';
                    return commit.message === 'Add book search' || `The subject is "${commit.message}".`;
                    JS],
                ['label' => 'A body explains why', 'code' => <<<'JS'
                    const message = repo('/work/shop').commit()?.fullMessage ?? '';
                    const [, body = ''] = message.split(/\n\n/);
                    return /customers/i.test(body) || 'Add a body paragraph (a second -m) that mentions customers.';
                    JS],
            ],
        ],
    ],
];
