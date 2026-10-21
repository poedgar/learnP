<?php

// Lesson 5 of the Git course. See 01-first-commits.php for how the checks work. A bare
// repository at /work/server.git plays the server (GitHub or similar).

return [
    'slug' => 'remotes-and-teamwork',
    'title' => 'Remotes and teamwork',
    'summary' => 'Sharing work through a remote: cloning, pushing, fetching and pulling, rejected pushes, upstream branches, pushing branches for review, and tags for releases.',
    'starter_code' => <<<'GIT'
        # Alice and Bob share a server. Try making Bob commit and push, then Alice pull.
        git init -q --bare server.git
        git clone -q server.git alice
        cd alice
        echo "Dune" > books.txt
        git add books.txt
        git commit -q -m "Add the book list"
        git push
        cd ..
        git clone server.git bob
        cd bob
        git log --oneline
        GIT,

    'quiz' => [
        [
            'question' => 'What is a bare repository?',
            'options' => [
                'A repository with no commits yet',
                'A repository with history but no working files, the kind a server holds',
                'A repository without a .gitignore',
                'A repository that has never been pushed',
            ],
            'correct_option' => 1,
            'explanation' => 'Nobody edits files on the server, so it only needs the history.',
        ],
        [
            'question' => 'After `git clone`, what is `origin`?',
            'options' => [
                'The first commit',
                'The name Git gives the repository you cloned from',
                'The main branch',
                'A backup of your files',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s just the default remote name; `git remote -v` shows its URL.',
        ],
        [
            'question' => 'What is the difference between `git fetch` and `git pull`?',
            'options' => [
                'They are the same',
                '`fetch` downloads new commits and updates `origin/...` only; `pull` fetches and then merges (or rebases) into your branch',
                '`fetch` uploads, `pull` downloads',
                '`pull` only works on main',
            ],
            'correct_option' => 1,
            'explanation' => '`fetch` is always safe: it never changes your own branches or files.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q --bare server.git
                git init -q alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git remote add origin ../server.git
                git push -q -u origin main
                cd ..
                git clone -q server.git bob
                cd alice
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git push -q
                cd ../bob
                git log --format=%s
                GIT,
            'options' => [
                '`Add Emma Add the book list`',
                '`Add the book list`',
                '`Add Emma`',
                'Nothing',
            ],
            'correct_option' => 1,
            'explanation' => 'Bob cloned before Alice\'s second push and hasn\'t fetched or pulled since.',
        ],
        [
            'question' => 'Your push is rejected because the server has commits you don\'t have. What should you do?',
            'options' => [
                '`git push --force`',
                'Pull (for example `git pull --rebase`), fix any conflicts, then push again',
                'Delete your repository and clone again',
                'Make another commit and push again',
            ],
            'correct_option' => 1,
            'explanation' => 'Forcing would throw away the other person\'s commits on the server.',
        ],
        [
            'question' => 'What does `-u` do in `git push -u origin search`?',
            'options' => [
                'Pushes urgently',
                'Makes `origin/search` the upstream of your `search` branch, so later `git push` and `git pull` need no arguments',
                'Updates every branch',
                'Undoes the last push',
            ],
            'correct_option' => 1,
            'explanation' => 'It\'s short for `--set-upstream`. `git branch -vv` shows each branch\'s upstream.',
        ],
        [
            'question' => 'What does this print?',
            'code' => <<<'GIT'
                git init -q --bare server.git
                git init -q alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git remote add origin ../server.git
                git push -q -u origin main
                git tag -a v1.0 -m "First release"
                git push -q origin v1.0
                cd ..
                git clone -q server.git bob
                cd bob
                git tag
                GIT,
            'options' => ['`v1.0`', 'Nothing', '`First release`', '`origin/v1.0`'],
            'correct_option' => 0,
            'explanation' => 'Alice pushed the tag, so the server has it, and a clone gets every tag.',
        ],
        [
            'question' => 'How does work usually get onto `main` in a team?',
            'options' => [
                'Everyone pushes to main whenever they like',
                'You push a branch, open a pull request for review, and it is merged when approved',
                'Only the server can commit',
                'By emailing files',
            ],
            'correct_option' => 1,
            'explanation' => 'Pull requests (merge requests on GitLab) let teammates review and discuss changes first.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'Share your work',
            'prompt' => 'The server already has the book list. Clone it into a folder called `alice`, commit a new file `prices.txt` (any content) as `Add prices`, and push it to the server.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git setup
                cd setup
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                # Your commands here:
                git init alice
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git setup
                cd setup
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                git clone server.git alice
                cd alice
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -m "Add prices"
                git push
                GIT,
            'hint' => '`git clone server.git alice`, `cd alice`, commit as usual, then `git push`.',
            'checks' => [
                ['label' => 'alice is a clone of the server', 'code' => <<<'JS'
                    const r = repo('/work/alice');
                    return /server\.git$/.test(r.remotes.origin ?? '') || 'alice should have the server as its origin. Use git clone.';
                    JS],
                ['label' => 'The server has your commit', 'code' => <<<'JS'
                    const log = repo('/work/server.git').log('main').map((c) => c.message);
                    return log.join(' | ') === 'Add prices | Add the book list' || `The server's history is: ${log.join(' | ')}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Connect it up',
            'prompt' => 'You started `shop` with `git init`, and now there\'s an empty server at `server.git`. Add it as the remote `origin`, and push `main` to it so that `main` tracks `origin/main`.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git init -q shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git remote add origin ../server.git
                git push -u origin main
                GIT,
            'hint' => '`git remote add origin ../server.git`, then `git push -u origin main`.',
            'checks' => [
                ['label' => 'origin is the server', 'code' => <<<'JS'
                    const url = repo('/work/shop').remotes.origin;
                    if (!url) return 'There is no remote called origin.';
                    return /server\.git$/.test(url) || `origin points at ${url}.`;
                    JS],
                ['label' => 'The server has main', 'code' => <<<'JS'
                    const commit = repo('/work/server.git').commit('main');
                    return commit?.message === 'Add the book list' || "The server doesn't have the main branch yet.";
                    JS],
                ['label' => 'main tracks origin/main', 'code' => <<<'JS'
                    const upstream = repo('/work/shop').upstream('main');
                    return upstream === 'origin/main' || `main's upstream is ${upstream ?? 'not set'}. Push with -u.`;
                    JS],
            ],
        ],
        [
            'title' => 'Catch up',
            'prompt' => 'Alice pushed two commits after Bob cloned. Bring Bob\'s `main` up to date with the server.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                git clone -q server.git bob
                cd alice
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git push -q
                cd ../bob
                git fetch
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                git clone -q server.git bob
                cd alice
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git push -q
                cd ../bob
                git pull
                GIT,
            'hint' => '`git fetch` only updates `origin/main`. `git pull` also brings your `main` up to date.',
            'checks' => [
                ['label' => "Bob's main matches the server", 'code' => <<<'JS'
                    const bob = repo('/work/bob');
                    return bob.resolve('main') === repo('/work/server.git').resolve('main') || "Bob's main is behind the server.";
                    JS],
                ['label' => "Bob's files are up to date", 'code' => <<<'JS'
                    const files = repo('/work/bob').files;
                    return ('prices.txt' in files && files['books.txt'].includes('Emma')) || "Bob's working files don't have Alice's changes.";
                    JS],
            ],
        ],
        [
            'title' => 'Rejected',
            'prompt' => 'Bob committed `Add prices`, but Alice pushed first, so Bob\'s push is rejected. Get Bob\'s commit onto the server, keeping the history a straight line (no merge commit).',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                git clone -q server.git bob
                cd alice
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git push -q
                cd ../bob
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git push
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git alice
                cd alice
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                cd ..
                git clone -q server.git bob
                cd alice
                echo "Emma" >> books.txt
                git commit -q -am "Add Emma"
                git push -q
                cd ../bob
                echo "Dune 9.99" > prices.txt
                git add prices.txt
                git commit -q -m "Add prices"
                git push
                git pull --rebase
                git push
                GIT,
            'hint' => '`git pull --rebase` puts Bob\'s commit on top of Alice\'s; then push again.',
            'checks' => [
                ['label' => 'The server has both commits', 'code' => <<<'JS'
                    const log = repo('/work/server.git').log('main').map((c) => c.message);
                    if (!log.includes('Add Emma')) return "Alice's commit is missing from the server. Don't force-push.";
                    return log.includes('Add prices') || "Bob's commit isn't on the server yet.";
                    JS],
                ['label' => 'A straight line', 'code' => <<<'JS'
                    const log = repo('/work/server.git').log('main');
                    if (log.some((c) => c.parents.length > 1)) return 'There is a merge commit. Use git pull --rebase.';
                    return log.map((c) => c.message).join(' | ') === 'Add prices | Add Emma | Add the book list' || `The server's history is: ${log.map((c) => c.message).join(' | ')}.`;
                    JS],
            ],
        ],
        [
            'title' => 'Ready for review',
            'prompt' => 'Make a branch `search`, commit `search.php` (any content) on it as `Add a search page`, and push the branch to the server with `origin/search` as its upstream. `main` on the server must stay as it is.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git push
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                git push -q
                git switch -c search
                echo "<?php" > search.php
                git add search.php
                git commit -q -m "Add a search page"
                git push -u origin search
                GIT,
            'hint' => 'Switch to a new branch before committing, then `git push -u origin search`.',
            'checks' => [
                ['label' => 'The server has the search branch', 'code' => <<<'JS'
                    const commit = repo('/work/server.git').commit('search');
                    if (!commit) return 'The server has no search branch.';
                    return commit.message === 'Add a search page' || `search on the server ends with "${commit.message}".`;
                    JS],
                ['label' => "The server's main is unchanged", 'code' => <<<'JS'
                    const commit = repo('/work/server.git').commit('main');
                    return commit.message === 'Add the book list' || 'The search page reached main on the server. It should only be on the search branch.';
                    JS],
                ['label' => 'search tracks origin/search', 'code' => <<<'JS'
                    const upstream = repo('/work/shop').upstream('search');
                    return upstream === 'origin/search' || `search's upstream is ${upstream ?? 'not set'}. Push with -u.`;
                    JS],
            ],
        ],
        [
            'title' => 'Release it',
            'prompt' => 'The shop is ready for its first release. Make an annotated tag `v1.0` on the latest commit with the message `First release`, and push the tag to the server.',
            'starter_code' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git push -q
                git tag v1.0
                git push
                GIT,
            'solution' => <<<'GIT'
                git init -q --bare server.git
                git clone -q server.git shop
                cd shop
                echo "Dune" > books.txt
                git add books.txt
                git commit -q -m "Add the book list"
                echo "<?php" > index.php
                git add index.php
                git commit -q -m "Add the home page"
                git push -q
                git tag -a v1.0 -m "First release"
                git push origin v1.0
                GIT,
            'hint' => '`git tag -a v1.0 -m "..."` makes an annotated tag; tags are pushed by name: `git push origin v1.0`.',
            'checks' => [
                ['label' => 'v1.0 is an annotated tag on the latest commit', 'code' => <<<'JS'
                    const r = repo('/work/shop');
                    if (!('v1.0' in r.tags)) return 'There is no tag v1.0.';
                    if (!r.annotatedTags.includes('v1.0')) return 'v1.0 is a lightweight tag. Use -a and -m.';
                    return r.tags['v1.0'] === r.head || 'v1.0 should be on the latest commit.';
                    JS],
                ['label' => 'The server has the tag', 'code' => <<<'JS'
                    const server = repo('/work/server.git');
                    if (!('v1.0' in server.tags)) return "The server doesn't have v1.0. Tags aren't pushed with branches.";
                    return !('v1.0' in server.branches) || 'The server has a branch called v1.0 instead of a tag.';
                    JS],
            ],
        ],
    ],
];
