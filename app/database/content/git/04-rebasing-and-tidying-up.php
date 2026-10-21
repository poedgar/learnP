<?php

// Lesson 4 of the Git course. See 01-first-commits.php for how the checks work.

return [
    'slug' => 'rebasing-and-tidying-up',
    'title' => 'Rebasing and tidying up',
    'summary' => 'Amending the last commit, rebasing a branch onto main and resolving conflicts along the way, copying a commit with cherry-pick, and stashing unfinished work.',
    'starter_code' => <<<'GIT'
        # Rebase search onto the latest main, then look at the graph.
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
        echo "Emma" >> books.txt
        git commit -q -am "Add Emma"
        git switch -q search
        git rebase main
        git log --oneline --graph --all
        GIT,

    'quiz' => [
        [
            'question' => 'What does `git commit --amend` do?',
            'options' => [
                'Adds a new commit after the last one',
                'Replaces the last commit with a new one, including whatever is staged now',
                'Undoes the last commit',
                'Changes an old commit in the middle of the history',
            ],
            'correct_option' => 1,
            'explanation' => 'The replacement gets a new hash, so amend only commits you haven\'t shared.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the bok list"
                git commit -q --amend -m "Add the book list"
                git log --format=%s
                GIT,
            'options' => [
                '`Add the book list`',
                '`Add the book list Add the bok list`',
                '`Add the bok list`',
                '`Add the bok list Add the book list`',
            ],
            'correct_option' => 0,
            'explanation' => 'The amended commit replaced the old one: there\'s still only one commit.',
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
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git switch -q search
                git rebase -q main
                git log --format=%s
                GIT,
            'options' => [
                '`Add a search page Add the book list`',
                '`Add a search page Add Emma Add the book list`',
                '`Add Emma Add a search page Add the book list`',
                '`Merge branch \'main\' into search Add Emma Add a search page Add the book list`',
            ],
            'correct_option' => 1,
            'explanation' => 'The search commit was replayed on top of the latest main, so the history is one straight line.',
        ],
        [
            'question' => 'Which commits should you not rebase?',
            'options' => [
                'Commits with more than one file',
                'Commits other people already have, for example ones you have pushed to a shared branch',
                'Commits on main',
                'Commits older than a week',
            ],
            'correct_option' => 1,
            'explanation' => 'Rebasing makes new commits. Anyone who built on the old ones ends up with a tangled history.',
        ],
        [
            'question' => 'A rebase stopped with a conflict. You fixed the file and ran `git add`. What next?',
            'options' => ['`git commit`', '`git rebase --continue`', '`git merge --continue`', '`git rebase main` again'],
            'correct_option' => 1,
            'explanation' => '`--continue` commits the fixed step and carries on replaying the rest. `--abort` would cancel the whole rebase.',
        ],
        [
            'question' => 'What does this print, after the cherry-pick\'s own report?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 1299" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Fix the price of Dune"
                git switch -q main
                git cherry-pick search
                ls
                GIT,
            'options' => [
                'prices.txt and search.php',
                'Only prices.txt',
                'Only search.php',
                'An error',
            ],
            'correct_option' => 1,
            'explanation' => 'Cherry-pick copies only the one commit `search` points at, the price fix, not the search page before it.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                echo "Dune 8.99" > prices.txt
                git stash -q -m "price ideas"
                git stash list
                GIT,
            'options' => [
                '`stash@{0}: On main: price ideas`',
                '`price ideas`',
                '`stash@{1}: On main: price ideas`',
                'Nothing',
            ],
            'correct_option' => 0,
            'explanation' => 'The newest stash is always `stash@{0}`, labelled with the branch and your message.',
        ],
        [
            'question' => 'How do you include new, untracked files in a stash?',
            'options' => ['`git stash -u`', '`git stash --all-files`', '`git stash pop`', 'Stash always includes them'],
            'correct_option' => 0,
            'explanation' => 'By default stash takes only changes to files Git already tracks.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Fix the typo',
            'prompt' => 'The last commit\'s message has a typo. Change it to `Add the book list` without adding a second commit.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add teh book list"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add teh book list"
                git commit --amend -m "Add the book list"
                GIT,
            'hint' => '`git commit --amend -m "..."` replaces the last commit\'s message.',
            'checks' => [
                ['label' => 'One commit, Add the book list', 'code' => <<<'JS'
                    const commits = repo('/work/shop').commits;
                    if (commits.length !== 1) return `Expected one commit, found ${commits.length}.`;
                    return commits[0].message === 'Add the book list' || `The message is "${commits[0].message}".`;
                    JS],
            ],
        ],
        [
            'title' => 'The forgotten file',
            'prompt' => 'You committed `Start the shop` but forgot `prices.txt`. Put it into that same commit, keeping the message, so the history still has one commit.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                git add books.txt
                git commit -q -m "Start the shop"
                git add prices.txt
                git commit -m "Add the prices I forgot"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 9.99" > prices.txt
                git add books.txt
                git commit -q -m "Start the shop"
                git add prices.txt
                git commit --amend -m "Start the shop"
                GIT,
            'hint' => 'Stage `prices.txt`, then amend instead of making a new commit.',
            'checks' => [
                ['label' => 'Still one commit, Start the shop', 'code' => <<<'JS'
                    const commits = repo('/work/shop').commits;
                    if (commits.length !== 1) return `Expected one commit, found ${commits.length}.`;
                    return commits[0].message === 'Start the shop' || `The message is "${commits[0].message}".`;
                    JS],
                ['label' => 'It holds both files', 'code' => <<<'JS'
                    const files = Object.keys(repo('/work/shop').commit().files).sort().join(', ');
                    return files === 'books.txt, prices.txt' || `The commit holds: ${files}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Catch up with main',
            'prompt' => '`main` moved on after you started `search`. Rebase `search` onto the latest `main`, so its history is a straight line with no merge commit.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                echo "<form></form>" > form.html
                git add form.html
                git commit -q -m "Add a search form"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git switch -q search
                git merge main
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                echo "<form></form>" > form.html
                git add form.html
                git commit -q -m "Add a search form"
                git switch -q main
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git switch -q search
                git rebase main
                GIT,
            'hint' => 'On `search`, run `git rebase main`.',
            'checks' => [
                ['label' => 'search sits on top of main', 'code' => <<<'JS'
                    return repo('/work/shop').isAncestor('main', 'search') || "main's latest commit isn't in search's history yet.";
                    JS],
                ['label' => 'A straight line, no merge commit', 'code' => <<<'JS'
                    const log = repo('/work/shop').log('search');
                    if (log.some((c) => c.parents.length > 1)) return 'There is a merge commit. Rebase instead of merging.';
                    return log.map((c) => c.message).join(' | ') === 'Add a search form | Add a search page | Add Emma | Add the book list' || `search's history is: ${log.map((c) => c.message).join(' | ')}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Conflict on the way',
            'prompt' => 'Rebase `sale` onto `main`. It will stop with a conflict in `prices.txt`: resolve it to `Dune 10.99` and finish the rebase.',
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
                git switch -q sale
                git rebase main
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
                git switch -q sale
                git rebase main
                echo "Dune 10.99" > prices.txt
                git add prices.txt
                git rebase --continue
                GIT,
            'hint' => 'Fix the file, `git add` it, then `git rebase --continue`.',
            'checks' => [
                ['label' => 'The rebase is finished', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (r.rebasing) return 'The rebase is still in progress.';
                    return r.branch === 'sale' || `You are on ${r.branch ?? 'no branch'}; the rebase should end on sale.`;
                    JS],
                ['label' => 'sale sits on top of main', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (!r.isAncestor('main', 'sale')) return "main's latest commit isn't in sale's history.";
                    return r.log('sale').every((c) => c.parents.length < 2) || 'There is a merge commit.';
                    JS],
                ['label' => 'prices.txt says Dune 10.99', 'code' => <<<'JS'
                    const content = repo('/work/shop').commit('sale').files['prices.txt'];
                    return content.trim() === 'Dune 10.99' || `prices.txt contains:\n${content.trim()}`;
                    JS],
            ],
        ],
        [
            'title' => 'Just the fix',
            'prompt' => 'While working on `search`, you fixed the price of Dune. `main` needs that fix today, but not the unfinished search page. Copy only the fix onto `main`.',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 1299" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Fix the price of Dune"
                echo "<form></form>" > form.html
                git add form.html
                git commit -q -m "Add a search form"
                git switch -q main
                git merge search
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune 1299" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git switch -q -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                echo "Dune 12.99" > prices.txt
                git commit -q -am "Fix the price of Dune"
                echo "<form></form>" > form.html
                git add form.html
                git commit -q -m "Add a search form"
                git switch -q main
                git cherry-pick search~1
                GIT,
            'hint' => 'The fix is the commit before the newest on `search`: `search~1`. Cherry-pick it while on `main`.',
            'checks' => [
                ['label' => 'main has the fix', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit('main');
                    if (commit.message !== 'Fix the price of Dune') return `The latest commit on main is "${commit.message}".`;
                    return commit.files['prices.txt'].trim() === 'Dune 12.99' || 'prices.txt on main should say Dune 12.99.';
                    JS],
                ['label' => 'and nothing else from search', 'code' => <<<'JS'
                    const files = Object.keys(repo('/work/shop').commit('main').files).sort().join(', ');
                    return files === 'prices.txt' || `main has: ${files}. Copy only the fix.`;
                    JS],
            ],
        ],
        [
            'title' => 'Hold that thought',
            'prompt' => 'You\'re halfway through adding a book to `books.txt` when the price of Dune needs fixing. Put your unfinished change aside, change `prices.txt` to `Dune 12.99` and commit only that as `Fix the price of Dune`, then bring your unfinished change back (uncommitted).',
            'starter_code' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 1299" > prices.txt
                git add .
                git commit -q -m "Start the shop"
                echo "Emm" >> books.txt
                echo "Dune 12.99" > prices.txt
                git commit -am "Fix the price of Dune"
                GIT,
            'solution' => <<<'GIT'
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                echo "Dune 1299" > prices.txt
                git add .
                git commit -q -m "Start the shop"
                echo "Emm" >> books.txt
                git stash
                echo "Dune 12.99" > prices.txt
                git commit -am "Fix the price of Dune"
                git stash pop
                GIT,
            'hint' => '`git stash` before the fix, and `git stash pop` after committing it.',
            'checks' => [
                ['label' => 'The fix is committed on its own', 'code' => <<<'JS'
                    const commit = repo('/work/shop').commit();
                    if (commit.message !== 'Fix the price of Dune') return `The latest commit is "${commit.message}".`;
                    return commit.changed.join(', ') === 'prices.txt' || `The fix changes: ${commit.changed.join(', ')}. It should change only prices.txt.`;
                    JS],
                ['label' => 'Your unfinished change is back', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (!r.files['books.txt'].includes('Emm')) return "books.txt doesn't have your unfinished line.";
                    return r.status.unstaged.includes('books.txt') || 'books.txt should have uncommitted changes.';
                    JS],
                ['label' => 'Nothing left in the stash', 'code' => <<<'JS'
                    return repo('/work/shop').stash.length === 0 || 'Use git stash pop, which also removes it from the stash.';
                    JS],
            ],
        ],
    ],
];
