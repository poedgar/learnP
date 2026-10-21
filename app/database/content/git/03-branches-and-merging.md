## Why branches

You're halfway through a new search page when a customer reports a wrong price. You want to fix the
price *now*, without shipping half a search page. **Branches** make that easy: each line of work gets
its own branch, and you move between them freely. When a piece of work is done, you **merge** it back.

## What a branch is

A branch is just a name that points at a commit. `main` is the branch `git init` makes. When you
commit, the branch you're on moves forward to the new commit; the others stay where they were.
`HEAD` tells Git which branch you're on.

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git branch search
git branch
git switch search
git branch --show-current
```

- `git branch name` creates a branch at the current commit (but doesn't move you to it).
- `git branch` lists branches; `*` marks the one you're on.
- `git switch name` moves you to a branch, changing your files to match it.
- `git switch -c name` creates a branch and switches to it in one go. (Older tutorials use
  `git checkout -b name`; it does the same.)

## Working on two branches

Commits on one branch don't appear on the other until you merge:

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git switch -c search
echo "<?php // search" > search.php
git add search.php
git commit -q -m "Add a search page"
ls
git switch main
ls
git log --oneline --all --graph
```

`--all` shows every branch, and `--graph` draws how they relate. `search.php` disappears from your
folder when you switch to `main`, and comes back when you switch to `search`: Git swaps the files
to match the branch.

## Merging

To bring a branch's work into the one you're on, `git merge` it. If `main` hasn't moved since the
branch started, Git simply moves `main` forward to the branch's commit. That's a **fast-forward**:

```git
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
git log --oneline
```

If both branches have new commits, Git combines them in a new **merge commit** with two parents:

```git
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
echo "Emma" >> books.txt
git commit -q -am "Add Emma"
git merge search -m "Merge the search page"
git log --oneline --graph
```

Once a branch is merged you can delete its name with `git branch -d search`. The commits stay; only
the label goes. Git refuses `-d` for a branch that isn't merged yet, so you can't lose work by
accident (`-D` forces it).

## Conflicts

When both branches changed the *same lines* differently, Git can't know which version is right. The
merge stops with a **conflict**, and Git writes both versions into the file between markers:

```git
# Error: the merge stops with a conflict.
git init -q shop
cd shop
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q -m "Add prices"
git switch -c sale
echo "Dune 7.99" > prices.txt
git commit -q -am "Put Dune on sale"
git switch main
echo "Dune 12.99" > prices.txt
git commit -q -am "Raise the price of Dune"
git merge sale
cat prices.txt
git status
```

Between `<<<<<<< HEAD` and `=======` is your branch's version, and between `=======` and
`>>>>>>> sale` is theirs. To resolve it:

1. Edit the file to what it should be, removing the markers.
2. `git add` it to mark it resolved.
3. `git commit` to finish the merge (Git has the message ready).

```git
# Error: the merge stops with a conflict, which we then resolve.
git init -q shop
cd shop
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q -m "Add prices"
git switch -c sale
echo "Dune 7.99" > prices.txt
git commit -q -am "Put Dune on sale"
git switch main
echo "Dune 12.99" > prices.txt
git commit -q -am "Raise the price of Dune"
git merge sale
echo "Dune 10.99" > prices.txt
git add prices.txt
git commit
git log --oneline --graph
```

Changed your mind? `git merge --abort` puts everything back as it was before the merge. Conflicts
are normal, not a sign that something went wrong. Small, frequent merges keep them small.
