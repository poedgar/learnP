<?php

// Lesson 6 of the Git course. See 01-first-commits.php for how the checks work.

return [
    'slug' => 'undoing-mistakes',
    'title' => 'Undoing mistakes',
    'summary' => 'Discarding and unstaging changes with git restore, reset in its three modes, revert for shared commits, recovering commits from the reflog, and untracking files.',
    'starter_code' => <<<'GIT'
        # "Lose" a commit with reset --hard, then find it in the reflog and bring it back.
        git init -q shop
        cd shop
        echo "Dune" > books.txt
        git add books.txt
        git commit -q -m "Add the book list"
        echo "Emma" >> books.txt
        git commit -q -am "Add Emma"
        git reset --hard HEAD~1
        git reflog
        git reset --hard HEAD@{1}
        git log --oneline
        GIT,

    'quiz' => [
        [
            'question' => 'You edited `prices.txt` and want it back exactly as it is in the last commit. Which command?',
            'options' => ['`git restore prices.txt`', '`git restore --staged prices.txt`', '`git revert prices.txt`', '`git rm prices.txt`'],
            'correct_option' => 0,
            'explanation' => 'Your edit is thrown away for good, since it was never committed.',
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
                echo "secret" > notes.txt
                git add .
                git restore --staged notes.txt
                git status --short
                GIT,
            'options' => ['`M  prices.txt ?? notes.txt`', '`M  prices.txt`', '`M  prices.txt A  notes.txt`', '`?? notes.txt`'],
            'correct_option' => 0,
            'explanation' => 'notes.txt is no longer staged, so it\'s untracked again, but the file is still there.',
        ],
        [
            'question' => 'Which `git reset` mode moves the branch back but keeps the undone changes staged?',
            'options' => ['`--soft`', '`--mixed`', '`--hard`', '`--keep-all`'],
            'correct_option' => 0,
            'explanation' => '`--mixed` (the default) keeps them unstaged in your files; `--hard` throws them away.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git reset -q --hard HEAD~1
                cat books.txt
                GIT,
            'options' => ['`Dune`', '`Dune Emma`', '`Emma`', 'Nothing'],
            'correct_option' => 0,
            'explanation' => '`--hard` makes your files match the commit you reset to, so Emma is gone (until you use the reflog).',
        ],
        [
            'question' => 'A bad commit is already on the server and your teammates have pulled it. How do you undo it?',
            'options' => [
                '`git reset --hard HEAD~1`, then `git push --force`',
                '`git revert` it, which adds a new commit undoing the change, then push',
                '`git commit --amend`',
                'Delete the server repository',
            ],
            'correct_option' => 1,
            'explanation' => 'Revert doesn\'t rewrite history, so nobody else\'s copy breaks.',
        ],
        [
            'question' => 'Which commits does the last command list, newest first?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 0.99" > prices.txt
                git commit -q -am "Change the price of Dune"
                git revert HEAD --no-edit
                git log --format=%s
                GIT,
            'options' => [
                'Add prices',
                'Revert "Change the price of Dune", Change the price of Dune, Add prices',
                'Change the price of Dune, Add prices',
                'Revert "Change the price of Dune", Add prices',
            ],
            'correct_option' => 1,
            'explanation' => 'Revert adds a commit; the original stays in the history.',
        ],
        [
            'question' => 'What does the reflog record?',
            'options' => [
                'Every command you typed',
                'Every place HEAD has pointed, so you can find commits no branch points at any more',
                'The server\'s history',
                'Deleted files',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s local to your repository, and the safety net after a bad reset or rebase.',
        ],
        [
            'question' => 'What does `git rm --cached app.log` do?',
            'options' => [
                'Deletes app.log from your disk and from Git',
                'Stops Git tracking app.log from the next commit on, but leaves the file on disk',
                'Removes app.log from every old commit',
                'Adds app.log to .gitignore',
            ],
            'correct_option' => 1,
            'explanation' => 'Old commits still contain the file. Add a .gitignore rule so it isn\'t added again.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Not that one',
            'prompt' => 'You added Emma to `books.txt` (good) and accidentally changed `prices.txt` (bad). Throw away the change to `prices.txt`, then commit the book list change as `Add Emma`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                git add .
                git commit -q -m "Start the shop"
                echo "Emma" >> books.txt
                echo "Dune 0.99" > prices.txt
                git commit -am "Add Emma"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                git add .
                git commit -q -m "Start the shop"
                echo "Emma" >> books.txt
                echo "Dune 0.99" > prices.txt
                git restore prices.txt
                git commit -am "Add Emma"
                GIT,
            'hint' => '`git restore prices.txt` before committing.',
            'checks' => [
                ['label' => 'Add Emma changes only books.txt', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (commit.message !== 'Add Emma') return `The latest commit is "${commit.message}".`;
                    return commit.changed.join(', ') === 'books.txt' || `It changes: ${commit.changed.join(', ')}.`;
                    JS],
                ['label' => 'prices.txt is back to Dune 9.99', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.files['prices.txt'].trim() !== 'Dune 9.99') return 'prices.txt still has the accidental change.';
                    return r.clean || 'There are leftover changes.';
                    JS],
            ],
        ],
        [
            'title' => 'Unstage it',
            'prompt' => '`git add .` staged your private `notes.txt` along with the price change. Unstage `notes.txt` (keep the file), then commit only the price change as `Raise the price of Dune`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 12.99" > prices.txt
                echo "Call the printer" > notes.txt
                git add .
                git commit -m "Raise the price of Dune"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 12.99" > prices.txt
                echo "Call the printer" > notes.txt
                git add .
                git restore --staged notes.txt
                git commit -m "Raise the price of Dune"
                GIT,
            'hint' => '`git restore --staged notes.txt` unstages without touching the file.',
            'checks' => [
                ['label' => 'The commit has only the price change', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (commit.message !== 'Raise the price of Dune') return `The latest commit is "${commit.message}".`;
                    return !('notes.txt' in commit.files) || 'notes.txt was committed.';
                    JS],
                ['label' => 'notes.txt is still there, untracked', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.file('notes.txt') === null) return 'notes.txt is gone. Keep the file; just unstage it.';
                    return r.status.untracked.includes('notes.txt') || 'notes.txt should be untracked.';
                    JS],
            ],
        ],
        [
            'title' => 'Squash them',
            'prompt' => 'The last three commits are one small change in three pieces. Combine them into a single commit with the message `Add Emma and Ulysses`, on top of `Add the book list`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Ulyses" >> books.txt
                git commit -q -am "Add Ulysses"
                echo "Dune" > books.txt
                echo "Emma" >> books.txt
                echo "Ulysses" >> books.txt
                git commit -q -am "Fix the spelling of Ulysses"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Ulyses" >> books.txt
                git commit -q -am "Add Ulysses"
                echo "Dune" > books.txt
                echo "Emma" >> books.txt
                echo "Ulysses" >> books.txt
                git commit -q -am "Fix the spelling of Ulysses"
                git reset --soft HEAD~3
                git commit -m "Add Emma and Ulysses"
                GIT,
            'hint' => '`git reset --soft HEAD~3` keeps all three changes staged; then commit once.',
            'checks' => [
                ['label' => 'Two commits', 'code' => <<<'JS'
                    const log = repo('/work/shop').commits.map((c) => c.message);
                    return log.join(' | ') === 'Add Emma and Ulysses | Add the book list' || `The history is: ${log.join(' | ')}.`;
                    JS],
                ['label' => 'Nothing was lost', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.commit().files['books.txt'].trim().split('\n').join(',') !== 'Dune,Emma,Ulysses') return 'books.txt in the new commit should list Dune, Emma and Ulysses.';
                    return r.clean || 'There are leftover changes.';
                    JS],
            ],
        ],
        [
            'title' => 'Undo it in public',
            'prompt' => 'You pushed `Change the price of Dune`, and it was a mistake. Teammates may have pulled it, so don\'t rewrite history: undo it with a new commit and push that to the server.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 0.99" > prices.txt
                git commit -q -am "Change the price of Dune"
                git remote add origin ../server.git
                git push -q -u origin main
                git reset --hard HEAD~1
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 0.99" > prices.txt
                git commit -q -am "Change the price of Dune"
                git remote add origin ../server.git
                git push -q -u origin main
                git revert HEAD --no-edit
                git push
                GIT,
            'hint' => '`git revert HEAD --no-edit`, then `git push`.',
            'checks' => [
                ['label' => 'The server has a revert on top', 'code' => <<<'JS'
                    const log = repo('/work/server.git').log('main').map((c) => c.message);
                    return log.join(' | ') === 'Revert "Change the price of Dune" | Change the price of Dune | Add prices' || `The server's history is: ${log.join(' | ')}.`;
                    JS],
                ['label' => 'The price is back to Dune 9.99', 'code' => <<<'JS'
                    const content = repo('/work/server.git').commit('main').files['prices.txt'];
                    return content.trim() === 'Dune 9.99' || `prices.txt on the server says ${content.trim()}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Get it back',
            'prompt' => 'Someone ran `git reset --hard HEAD~2` by mistake and two commits vanished from `main`. Bring `main` back to where it was, with all three commits.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Ulysses" >> books.txt
                git commit -q -am "Add Ulysses"
                git reset --hard HEAD~2
                git log --oneline
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Ulysses" >> books.txt
                git commit -q -am "Add Ulysses"
                git reset --hard HEAD~2
                git reflog
                git reset --hard HEAD@{1}
                GIT,
            'hint' => '`git reflog` shows where HEAD was before the reset: `HEAD@{1}`. Reset back to it.',
            'checks' => [
                ['label' => 'main has all three commits', 'code' => <<<'JS'
                    const log = repo('/work/shop').log('main').map((c) => c.message);
                    return log.join(' | ') === 'Add Ulysses | Add Emma | Add the book list' || `main's history is: ${log.join(' | ')}.`;
                    JS],
                ['label' => 'The files are back too', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.branch !== 'main') return `You are on ${r.branch ?? 'no branch'}.`;
                    return (r.clean && r.files['books.txt'].includes('Ulysses')) || 'books.txt should list Ulysses again, with nothing uncommitted.';
                    JS],
            ],
        ],
        [
            'title' => 'Stop tracking it',
            'prompt' => '`app.log` was committed by mistake. Stop tracking it without deleting it from your disk, make Git ignore every `.log` file from now on, and commit both changes as `Stop tracking log files`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "<?php" > index.php
                echo "debug output" > app.log
                git add .
                git commit -q -m "Start the shop"
                git rm app.log
                git commit -m "Stop tracking log files"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "<?php" > index.php
                echo "debug output" > app.log
                git add .
                git commit -q -m "Start the shop"
                git rm --cached app.log
                echo "*.log" > .gitignore
                git add .gitignore
                git commit -m "Stop tracking log files"
                GIT,
            'hint' => '`git rm --cached app.log` keeps the file on disk. Then add a `.gitignore` with `*.log` and commit.',
            'checks' => [
                ['label' => 'The latest commit no longer has app.log', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (commit.message !== 'Stop tracking log files') return `The latest commit is "${commit.message}".`;
                    if ('app.log' in commit.files) return 'app.log is still tracked.';
                    return '.gitignore' in commit.files || 'Commit the .gitignore too.';
                    JS],
                ['label' => 'Log files are ignored', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    return (r.ignored('app.log') && r.ignored('error.log')) || 'Every .log file should be ignored.';
                    JS],
                ['label' => 'app.log is still on disk', 'code' => <<<'JS'
                    return repo('/work/shop').file('app.log') !== null || 'app.log was deleted. Use --cached to keep it.';
                    JS],
            ],
        ],
    ],
];
