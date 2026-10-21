## Almost nothing is lost

One of the best things about Git is that mistakes are rarely final. This lesson goes from the
smallest undo to the biggest: throwing away an edit, unstaging a file, undoing commits with `reset`
and `revert`, and getting "lost" commits back with the reflog. The one thing Git can't bring back is
work you never committed or stashed, so the commands that throw away uncommitted changes are the ones
to type carefully.

## Throwing away changes to a file

You edited a file and want it back as it was in the last commit. `git restore` does that. The edit is
gone for good, so be sure:

```git
git init -q shop
cd shop
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q -m "Add prices"
echo "Dune 0.99" > prices.txt
git status --short
git restore prices.txt
cat prices.txt
git status --short
```

## Unstaging

Staged something by mistake? `git restore --staged file` takes it out of the staging area but keeps
your edit in the file:

```git
git init -q shop
cd shop
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q -m "Add prices"
echo "Dune 12.99" > prices.txt
echo "secret" > notes.txt
git add .
git status --short
git restore --staged notes.txt
git status --short
```

(Older tutorials use `git checkout -- file` and `git reset file` for these two. They still work.)

## Undoing commits you haven't shared: reset

`git reset` moves your branch back to an earlier commit. What happens to the changes from the commits
you "removed" depends on the mode:

- `--soft` keeps them **staged**: handy for redoing the last commit, or squashing several into one.
- `--mixed` (the default) keeps them in your files, **unstaged**.
- `--hard` **throws them away**, files and all.

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
echo "Emma" >> books.txt
git commit -q -am "Add Emma"
echo "Ulysses" >> books.txt
git commit -q -am "Add Ulysses"
git reset --soft HEAD~2
git status --short
git commit -q -m "Add Emma and Ulysses"
git log --oneline
```

Two commits became one. Like rebasing and amending, reset rewrites history: only use it on commits you
haven't pushed.

## Undoing commits you have shared: revert

Once a commit is on the server, don't remove it; add a new commit that does the opposite.
`git revert` makes that commit for you:

```git
git init -q shop
cd shop
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q -m "Add prices"
echo "Dune 0.99" > prices.txt
git commit -q -am "Change the price of Dune"
git revert HEAD --no-edit
git log --oneline
cat prices.txt
```

The history now tells the truth: the change happened, and then it was undone. Everyone who pulls gets
the fix, and nobody's history is rewritten. You can revert any commit, not just the last:
`git revert a1b2c3d`.

## The reflog: Git's safety net

Every time `HEAD` moves (commits, resets, switches, rebases), Git writes it down in the **reflog**.
So even after `git reset --hard`, the "lost" commits are still there, just with no branch pointing
at them, and you can go back:

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
echo "Emma" >> books.txt
git commit -q -am "Add Emma"
git reset --hard HEAD~1
git log --oneline
git reflog
git reset --hard HEAD@{1}
git log --oneline
```

`HEAD@{1}` means "where HEAD was one move ago". You could also use the hash from the reflog, or
`git branch rescue <hash>` to put a branch on a lost commit.

## Files you shouldn't have committed

Committed a log file before adding it to `.gitignore`? `git rm --cached` stops Git tracking the file
but leaves it on disk, and the ignore rule keeps it out from now on:

```git
git init -q shop
cd shop
echo "<?php" > index.php
echo "debug output" > app.log
git add .
git commit -q -m "Start the shop"
git rm --cached app.log
echo "*.log" > .gitignore
git add .gitignore
git commit -q -m "Stop tracking log files"
git ls-files
ls
```

Remember that the file is still in the *old* commits. If it held a password, change the password:
anyone with the repository can read the history.

Finally, `git clean` deletes untracked files: `git clean -n` lists what it would delete, and
`git clean -f` deletes them.
