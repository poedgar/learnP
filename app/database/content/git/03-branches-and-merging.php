<?php

// Lesson 3 of the Git course. See 01-first-commits.php for how the checks work.

return [
    'slug' => 'branches-and-merging',
    'title' => 'Branches and merging',
    'summary' => 'Branches as movable names, switching between them, fast-forward and merge commits, deleting merged branches, and resolving merge conflicts.',
    'starter_code' => <<<'GIT'
        # Make a branch, commit on it, then merge it back. Watch the graph.
        git init -q shop
        cd shop
        echo "Dune" > books.txt
        git add books.txt
        git commit -q -m "Add the book list"
        git switch -c search
        echo "<?php // search" > search.php
        git add search.php
        git commit -q -m "Add a search page"
        git switch main
        git merge search
        git log --oneline --graph --all
        GIT,

    'quiz' => [
        [
            'question' => 'What is a branch in Git?',
            'options' => [
                'A full copy of the project in another folder',
                'A name that points at a commit and moves forward when you commit on it',
                'A list of staged changes',
                'A backup on a server',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s why branches are cheap: making one just writes a name.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git branch search
                git branch --show-current
                GIT,
            'options' => ['`search`', '`main`', '`HEAD`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => '`git branch search` creates the branch but leaves you where you were. `git switch -c` creates and switches.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git switch -q main
                ls
                GIT,
            'options' => ['`books.txt search.php`', '`books.txt`', '`search.php`', 'Nothing'],
            'correct_option' => 1,
            'explanation' => 'Switching to `main` makes your files match `main`, which has never seen search.php.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git switch -q main
                git merge -q search
                git log --format=%s
                GIT,
            'options' => [
                '`Add a search page Add the book list`',
                '`Merge branch \'search\' Add a search page Add the book list`',
                '`Add the book list`',
                '`Add the book list Add a search page`',
            ],
            'correct_option' => 0,
            'explanation' => '`main` hadn\'t moved, so the merge was a fast-forward: `main` just moved up to the search commit. No merge commit.',
        ],
        [
            'question' => 'When does `git merge` make a merge commit?',
            'options' => [
                'Always',
                'When both branches have commits the other doesn\'t',
                'Only when there is a conflict',
                'Never; merges only move branch names',
            ],
            'correct_option' => 1,
            'explanation' => 'If only the other branch moved on, Git fast-forwards. A merge commit (with two parents) joins two histories that both moved.',
        ],
        [
            'question' => 'In a conflicted file, which part is the version from the branch you are on?',
            'options' => [
                'Between `<<<<<<< HEAD` and `=======`',
                'Between `=======` and `>>>>>>> sale`',
                'Everything after `>>>>>>>`',
                'Git only keeps the other branch\'s version',
            ],
            'correct_option' => 0,
            'explanation' => '`HEAD` is where you are; the name after `>>>>>>>` is the branch you\'re merging in.',
        ],
        [
            'question' => 'You have edited a conflicted file to what it should be. What next?',
            'options' => [
                '`git merge` again',
                '`git add` the file, then `git commit`',
                '`git switch main`',
                'Nothing: Git notices automatically',
            ],
            'correct_option' => 1,
            'explanation' => '`git add` marks the conflict resolved, and `git commit` finishes the merge.',
        ],
        [
            'question' => 'You run `git branch -d sale`, but `sale` has commits that aren\'t merged anywhere. What happens?',
            'options' => [
                'The branch and its commits are deleted',
                'Git refuses and suggests `-D` if you really mean it',
                'Git merges it first',
                'Git switches to it',
            ],
            'correct_option' => 1,
            'explanation' => '`-d` is the safe delete. `-D` forces it, and the commits are then only reachable through the reflog.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Work on the side',
            'prompt' => 'Make a branch called `search` and commit a file `search.php` (any content) on it with the message `Add a search page`. Finish back on `main`, which must not have the search page yet.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "<?php // search" > search.php
                git add search.php
                git commit -m "Add a search page"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -c search
                echo "<?php // search" > search.php
                git add search.php
                git commit -m "Add a search page"
                git switch main
                GIT,
            'hint' => 'Run `git switch -c search` before creating the file, and `git switch main` at the end.',
            'checks' => [
                ['label' => 'search has the search page', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit('search');
                    if (!commit) return 'There is no branch called search.';
                    return (commit.message === 'Add a search page' && 'search.php' in commit.files) || 'The latest commit on search should add search.php, with the message Add a search page.';
                    JS],
                ['label' => 'main does not', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    return !('search.php' in r.commit('main').files) || 'main already has search.php. Commit it on the search branch instead.';
                    JS],
                ['label' => 'You are back on main', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    return r.branch === 'main' || `You are on ${r.branch ?? 'no branch'}.`;
                    JS],
            ],
        ],
        [
            'title' => 'A quick fix',
            'prompt' => 'Make a branch `fix-price`, change `prices.txt` to `Dune 12.99` and commit it there as `Fix the price of Dune`. Then merge it into `main` (it will fast-forward) and delete the `fix-price` branch.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 1299" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 1299" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -c fix-price
                echo "Dune 12.99" > prices.txt
                git commit -am "Fix the price of Dune"
                git switch main
                git merge fix-price
                git branch -d fix-price
                GIT,
            'hint' => 'Switch back to `main` before `git merge fix-price`; then `git branch -d fix-price`.',
            'checks' => [
                ['label' => 'main has the fix', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit('main');
                    if (commit.message !== 'Fix the price of Dune') return `The latest commit on main is "${commit.message}".`;
                    return commit.files['prices.txt'].trim() === 'Dune 12.99' || 'prices.txt should say Dune 12.99.';
                    JS],
                ['label' => 'It was a fast-forward', 'code' => <<<'JS'
                    return repo('/work/shop').commits.every((c) => c.parents.length < 2) || 'There is a merge commit. Commit nothing on main before merging, so Git can fast-forward.';
                    JS],
                ['label' => 'fix-price is deleted', 'code' => <<<'JS'
                    return !('fix-price' in repo('/work/shop').branches) || 'Delete the branch after merging.';
                    JS],
            ],
        ],
        [
            'title' => 'Bring it together',
            'prompt' => 'Both `main` and `search` have new commits. Merge `search` into `main` with a merge commit whose message is `Merge the search page`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php // search" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php // search" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git merge search -m "Merge the search page"
                GIT,
            'hint' => 'On `main`, run `git merge search -m "..."`.',
            'checks' => [
                ['label' => 'main ends in a merge commit', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    const commit = r.commit('main');
                    if (commit.parents.length !== 2) return 'The latest commit on main should be a merge commit, with two parents.';
                    return commit.message === 'Merge the search page' || `The merge commit's message is "${commit.message}".`;
                    JS],
                ['label' => 'Both lines of work are in main', 'code' => <<<'JS'
                    const files = repo('/work/shop').commit('main').files;
                    if (!('search.php' in files)) return 'search.php is missing from main.';
                    return files['books.txt'].includes('Emma') || 'Emma is missing from books.txt.';
                    JS],
            ],
        ],
        [
            'title' => 'Settle the price',
            'prompt' => 'Merging `sale` into `main` conflicts over the price of Dune. Resolve it so `prices.txt` contains just `Dune 10.99`, and finish the merge.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c sale
                echo "Dune 7.99" > prices.txt
                git commit -q -am "Put Dune on sale"
                git switch -q main
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git merge sale
                cat prices.txt
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c sale
                echo "Dune 7.99" > prices.txt
                git commit -q -am "Put Dune on sale"
                git switch -q main
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git merge sale
                echo "Dune 10.99" > prices.txt
                git add prices.txt
                git commit
                GIT,
            'hint' => 'Overwrite the file with `echo "Dune 10.99" > prices.txt`, then `git add` and `git commit`.',
            'checks' => [
                ['label' => 'The merge is finished', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.merging) return 'The merge is still in progress: add the resolved file and commit.';
                    return r.commit().parents.length === 2 || 'The latest commit should be the merge commit.';
                    JS],
                ['label' => 'prices.txt says Dune 10.99', 'code' => <<<'JS'
                    const content = repo('/work/shop').commit().files['prices.txt'];
                    if (/^(<<<<<<<|=======|>>>>>>>)/m.test(content)) return 'The conflict markers were committed.';
                    return content.trim() === 'Dune 10.99' || `prices.txt contains:\n${content.trim()}`;
                    JS],
            ],
        ],
        [
            'title' => 'Not now',
            'prompt' => 'The merge of `sale` conflicts, and you decide to deal with it tomorrow. Back out of the merge so `main` is exactly as it was before.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c sale
                echo "Dune 7.99" > prices.txt
                git commit -q -am "Put Dune on sale"
                git switch -q main
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git merge sale
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c sale
                echo "Dune 7.99" > prices.txt
                git commit -q -am "Put Dune on sale"
                git switch -q main
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Raise the price of Dune"
                git merge sale
                git merge --abort
                GIT,
            'hint' => '`git merge --abort` undoes a merge that stopped with conflicts.',
            'checks' => [
                ['label' => 'No merge in progress', 'code' => <<<'JS'
                    return !repo('/work/shop').merging || 'The merge is still in progress.';
                    JS],
                ['label' => 'main is as it was', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.commit().message !== 'Raise the price of Dune') return `The latest commit is "${r.commit().message}".`;
                    if (!r.clean) return 'There are leftover changes.';
                    return r.files['prices.txt'].trim() === 'Dune 12.99' || 'prices.txt should be back to Dune 12.99.';
                    JS],
            ],
        ],
        [
            'title' => 'Keep both',
            'prompt' => 'Grace added `Emma` to the book list on `main` while you added `Ulysses` on `new-books`. Merge `new-books` into `main` and resolve the conflict by keeping all three books: `Dune`, then `Emma`, then `Ulysses`, one per line.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c new-books
                echo "Ulysses" >> books.txt
                git commit -q -am "Add Ulysses"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git merge new-books
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c new-books
                echo "Ulysses" >> books.txt
                git commit -q -am "Add Ulysses"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git merge new-books
                echo "Dune" > books.txt
                echo "Emma" >> books.txt
                echo "Ulysses" >> books.txt
                git add books.txt
                git commit
                GIT,
            'hint' => 'Write the file again with all three lines (`>` for the first, `>>` for the others), then `git add` and `git commit`.',
            'checks' => [
                ['label' => 'The merge is finished', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.merging) return 'The merge is still in progress: add the resolved file and commit.';
                    return r.commit().parents.length === 2 || 'The latest commit should be the merge commit.';
                    JS],
                ['label' => 'All three books, in order', 'code' => <<<'JS'
                    const lines = repo('/work/shop').commit().files['books.txt'].trim().split('\n');
                    return lines.join(',') === 'Dune,Emma,Ulysses' || `books.txt contains:\n${lines.join('\n')}`;
                    JS],
            ],
        ],
    ],
];
