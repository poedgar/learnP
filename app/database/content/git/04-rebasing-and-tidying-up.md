## A tidy history

Merging is safe and records exactly what happened, but a history full of merge commits for tiny
branches can be hard to read. This lesson covers the tools for keeping history tidy: fixing the last
commit, **rebasing** a branch so it sits on top of the latest `main`, copying single commits with
**cherry-pick**, and putting unfinished work aside with **stash**.

## Fixing the last commit

Forgot a file, or made a typo in the message? `git commit --amend` replaces the last commit with a
new one that includes whatever is staged now, and the new message if you give one:

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the bok list"
git commit -q --amend -m "Add the book list"
echo "Dune 9.99" > prices.txt
git add prices.txt
git commit -q --amend -m "Add the book list and prices"
git log --stat
```

There is still only one commit. "Replaces" is the important word: the amended commit gets a new hash,
so only amend commits you haven't shared with anyone yet.

## Rebasing

Say you started `search` from `main`, and `main` has moved on since. Instead of merging `main` in,
you can **rebase** your branch: Git takes your branch's commits and replays them, one by one, on top
of the latest `main`, as if you'd started from there:

```git
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
git log --oneline --graph --all
git rebase main
git log --oneline --graph --all
```

The history is now a straight line, and merging `search` into `main` will be a fast-forward. Compare
the hashes: the search commit is a *new* commit with the same change. That's why the golden rule of
rebasing is: **don't rebase commits other people already have.** Rebase your own local work; merge
shared branches.

## Conflicts while rebasing

Rebasing can conflict just like merging, one commit at a time. Git stops, you fix the file and
`git add` it, then run `git rebase --continue` (not `git commit`). `git rebase --abort` puts
everything back as it was.

```git
# Error: the rebase stops with a conflict, which we then resolve.
git init -q shop
cd shop
echo "Open 9 to 5" > hours.txt
git add hours.txt
git commit -q -m "Add opening hours"
git switch -q -c summer
echo "Open 8 to 8" > hours.txt
git commit -q -am "Open longer in summer"
git switch -q main
echo "Open 9 to 6" > hours.txt
git commit -q -am "Close an hour later"
git switch -q summer
git rebase main
cat hours.txt
echo "Open 8 to 8" > hours.txt
git add hours.txt
git rebase --continue
git log --oneline
```

Real Git also has `git rebase -i` (interactive), which opens an editor where you can reorder, squash
and reword commits. This sandbox has no editor, so we won't use it here.

## Copying one commit

Sometimes you want just one commit from another branch: a bug fix made on a feature branch that
`main` needs today. `git cherry-pick` copies a commit onto the branch you're on:

```git
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
git log --oneline
ls
```

`main` got the fix, but not the search page. You can name the commit by branch (its newest commit),
by hash, or as `search~1` and so on.

## Putting work aside

You're in the middle of something and need to switch branches, but your changes aren't ready to
commit. `git stash` saves your uncommitted changes and gives you a clean working folder;
`git stash pop` brings them back:

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
echo "Half a new feature" >> books.txt
git stash
git status --short
git stash list
git stash pop
git status --short
```

Stash only takes files Git tracks, unless you add `-u` to include untracked files too. Give a stash a
name with `git stash -m "price ideas"`, see them all with `git stash list`, and use `git stash apply`
to bring one back while keeping it in the list.
