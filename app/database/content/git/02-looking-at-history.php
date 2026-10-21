<?php

// Lesson 2 of the Git course. See 01-first-commits.php for how the checks work. The challenge
// setups are quiet (-q), so `output` is just what the learner's own commands print.

return [
    'slug' => 'looking-at-history',
    'title' => 'Looking at history',
    'summary' => 'Reading history with git log and its options, your own log format, naming commits with HEAD~n, git show, and git diff before and after staging.',
    'starter_code' => <<<'GIT'
        # Try other options: -p, --stat, --reverse, --format="%h %s".
        git init -q shop
        cd shop
        echo "Dune" > books.txt
        git add books.txt
        git commit -q -m "Add the book list"
        echo "Emma" >> books.txt
        git commit -q -am "Add Emma"
        git log --oneline
        git diff HEAD~1 HEAD
        GIT,

    'quiz' => [
        [
            'question' => 'What does `HEAD~1` mean?',
            'options' => [
                'The first commit in the repository',
                'The commit before the one you are on',
                'The next commit',
                'The staged changes',
            ],
            'correct_option' => 1,
            'explanation' => '`HEAD` is the commit you\'re on; each `~` steps back one commit. `HEAD~2` is two back.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "one" > notes.txt
                git add notes.txt
                git commit -q -m "First"
                echo "two" >> notes.txt
                git commit -q -am "Second"
                echo "three" >> notes.txt
                git commit -q -am "Third"
                git log --format=%s -n 1 HEAD~1
                GIT,
            'options' => ['`Third`', '`Second`', '`First`', '`Third Second`'],
            'correct_option' => 1,
            'explanation' => 'Starting from `HEAD~1` (Second), `-n 1` keeps only that one commit.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "one" > notes.txt
                git add notes.txt
                git commit -q -m "First"
                echo "two" >> notes.txt
                git commit -q -am "Second"
                echo "three" >> notes.txt
                git commit -q -am "Third"
                git log --reverse --format=%s
                GIT,
            'options' => ['`Third Second First`', '`First Second Third`', '`First`', '`Third`'],
            'correct_option' => 1,
            'explanation' => '`--reverse` lists the oldest commit first.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                git config user.name Ada
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git config user.name Grace
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git log --format=%an
                GIT,
            'options' => ['`Ada Grace`', '`Grace Ada`', '`Grace`', '`You You`'],
            'correct_option' => 1,
            'explanation' => 'Each commit keeps the name configured when it was made, and the log is newest first.',
        ],
        [
            'question' => 'You changed `books.txt` and ran `git add books.txt`. What does plain `git diff` show now?',
            'options' => [
                'The change to books.txt',
                'Nothing: it compares your files with the staging area, and they match',
                'The whole file',
                'An error',
            ],
            'correct_option' => 1,
            'explanation' => 'Once a change is staged, use `git diff --staged` to see it.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git show HEAD~1:prices.txt
                GIT,
            'options' => ['`Dune 12.99`', '`Dune 9.99`', '`Dune 9.99 Dune 12.99`', 'An error'],
            'correct_option' => 1,
            'explanation' => '`commit:path` prints the file as it was in that commit, without touching your files.',
        ],
        [
            'question' => 'Which command lists the files each commit changed, with a count of changed lines?',
            'options' => ['`git log --stat`', '`git log --oneline`', '`git status`', '`git log --reverse`'],
            'correct_option' => 0,
            'explanation' => '`--stat` adds a summary per file; `-p` shows every changed line.',
        ],
        [
            'question' => 'Which command shows only commits whose message mentions "price"?',
            'options' => ['`git log --author=price`', '`git log --grep=price`', '`git log price`', '`git show price`'],
            'correct_option' => 1,
            'explanation' => '`--grep` searches messages, `--author` searches authors. `git log price` would look for a branch or file called price.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Oldest first',
            'prompt' => 'Print the history of the repository one line per commit (short hash and subject), with the oldest commit first.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git log
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git log --oneline --reverse
                GIT,
            'hint' => 'Combine two options: one for one line per commit, one for the order.',
            'checks' => [
                ['label' => 'Prints the history, oldest first, one line each', 'code' => <<<'JS'
                    const expected = git('git log --oneline --reverse').trim();
                    return output.trim() === expected || `Expected:\n${expected}`;
                    JS],
            ],
        ],
        [
            'title' => 'Who wrote what',
            'prompt' => 'Ada and Grace both worked on this repository. Print just the subjects of Grace\'s commits, one per line, newest first, and nothing else.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                git config user.name "Ada Lovelace"
                echo "Dnue" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git config user.name "Grace Hopper"
                echo "Dune" > books.txt
                git commit -q -am "Fix a typo in Dune"
                git config user.name "Ada Lovelace"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git config user.name "Grace Hopper"
                echo "Open 9 to 5" > hours.txt
                git add hours.txt
                git commit -q -m "Add opening hours"
                git config user.name "Ada Lovelace"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git log --oneline
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                git config user.name "Ada Lovelace"
                echo "Dnue" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git config user.name "Grace Hopper"
                echo "Dune" > books.txt
                git commit -q -am "Fix a typo in Dune"
                git config user.name "Ada Lovelace"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git config user.name "Grace Hopper"
                echo "Open 9 to 5" > hours.txt
                git add hours.txt
                git commit -q -m "Add opening hours"
                git config user.name "Ada Lovelace"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git log --author=Grace --format=%s
                GIT,
            'hint' => 'Filter with `--author=Grace` and print only the subject with `--format=%s`.',
            'checks' => [
                ['label' => "Prints the subjects of Grace's commits", 'code' => <<<'JS'
                    const lines = output.trim().split('\n');
                    const expected = ['Add opening hours', 'Fix a typo in Dune'];
                    return JSON.stringify(lines) === JSON.stringify(expected) || `Expected exactly:\n${expected.join('\n')}`;
                    JS],
            ],
        ],
        [
            'title' => 'The old price list',
            'prompt' => 'The price list has changed twice. Without changing any files, print `prices.txt` as it was two commits before the latest one.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Add the price of Emma"
                echo "Dune 12.99" > prices.txt
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Raise the price of Dune"
                cat prices.txt
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Add the price of Emma"
                echo "Dune 12.99" > prices.txt
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Raise the price of Dune"
                git show HEAD~2:prices.txt
                GIT,
            'hint' => '`git show <commit>:<file>` prints a file from a commit. Two before the latest is `HEAD~2`.',
            'checks' => [
                ['label' => 'Prints the first version', 'code' => <<<'JS'
                    return output.trim() === 'Dune 9.99' || 'Expected the first version of prices.txt, a single line: Dune 9.99.';
                    JS],
                ['label' => 'Your files are unchanged', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    return (r.clean && r.commits.length === 3) || 'Only look: the files and history should stay as they were.';
                    JS],
            ],
        ],
        [
            'title' => 'Check before you commit',
            'prompt' => 'A price change is already staged. First show exactly what the next commit will contain, then commit it with the message `Raise the price of Dune`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 12.99" > prices.txt
                git add prices.txt
                git diff
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 12.99" > prices.txt
                git add prices.txt
                git diff --staged
                git commit -m "Raise the price of Dune"
                GIT,
            'hint' => 'Plain `git diff` shows unstaged changes only. Add `--staged`.',
            'checks' => [
                ['label' => 'Shows the staged change', 'code' => <<<'JS'
                    return (output.includes('-Dune 9.99') && output.includes('+Dune 12.99')) || 'Show the staged diff: it should include -Dune 9.99 and +Dune 12.99.';
                    JS],
                ['label' => 'Commits it', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.commit().message !== 'Raise the price of Dune') return `The latest commit is "${r.commit().message}".`;
                    return r.clean || 'Something is left uncommitted.';
                    JS],
            ],
        ],
        [
            'title' => 'Which files?',
            'prompt' => 'Print just the names of the files the latest commit changed, one per line.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                echo "<?php" > index.php
                git add .
                git commit -q -m "Start the shop"
                echo "Emma" >> books.txt
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Add Emma"
                git show
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                echo "<?php" > index.php
                git add .
                git commit -q -m "Start the shop"
                echo "Emma" >> books.txt
                echo "Emma 7.50" >> prices.txt
                git commit -q -am "Add Emma"
                git diff --name-only HEAD~1 HEAD
                GIT,
            'hint' => 'Compare the latest commit with the one before it, `HEAD~1 HEAD`, and ask `git diff` for names only.',
            'checks' => [
                ['label' => 'Prints books.txt and prices.txt only', 'code' => <<<'JS'
                    const names = output.split('\n').map((line) => line.trim()).filter(Boolean).sort();
                    return names.join(',') === 'books.txt,prices.txt' || `Expected two lines, books.txt and prices.txt, but got:\n${output.trim()}`;
                    JS],
            ],
        ],
        [
            'title' => 'Everything about prices',
            'prompt' => 'Print, one line per commit (short hash and subject), only the commits whose message mentions `price`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git log --oneline
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git log --oneline --grep=price
                GIT,
            'hint' => '`--grep` keeps commits whose message matches.',
            'checks' => [
                ['label' => 'Prints the two price commits', 'code' => <<<'JS'
                    const expected = git('git log --oneline --grep=price').trim();
                    if (expected.split('\n').length !== 2) return 'The setup has changed: there should be two commits about prices.';
                    return output.trim() === expected || `Expected:\n${expected}`;
                    JS],
            ],
        ],
    ],
];
