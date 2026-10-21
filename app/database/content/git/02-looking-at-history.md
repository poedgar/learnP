## History is for reading

A history is only useful if you can find things in it: which commit changed the prices, what the
code looked like last week, what exactly you're about to commit. This lesson covers the commands for
looking: `git log`, `git show` and `git diff`.

## git log, many ways

Plain `git log` shows everything about every commit. A few options make it far more useful:

```git
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
git log --oneline
git log --oneline -n 2
git log --stat -n 1
```

- `--oneline` gives one line per commit: the short hash and the subject.
- `-n 2` (or just `-2`) shows only the two newest commits.
- `--stat` lists the files each commit changed and how many lines.
- `-p` (patch) shows the full changes, line by line.
- `--reverse` puts the oldest first.

You can also filter: `--author="Ada"` keeps commits by matching authors, `--grep="price"` keeps
commits whose message matches, and a file name at the end (`git log -- books.txt`) keeps commits that
touched that file.

## Your own format

`--format` (or `--pretty=format:`) prints exactly the fields you ask for: `%h` short hash, `%H` full
hash, `%s` subject, `%an` author name, `%ae` author email, `%ad` date:

```git
git init -q shop
cd shop
git config user.name "Ada Lovelace"
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git log --format="%h %an: %s"
```

## Naming commits

Anywhere Git wants a commit you can give a hash (the first few characters are enough), a branch
name, or `HEAD`, which means "the commit you're on". Add `~` to step back: `HEAD~1` is the commit
before `HEAD`, `HEAD~2` the one before that.

```git
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
git show HEAD~2:notes.txt
```

## git show

`git show` prints one commit: its details and the changes it made (`HEAD` if you don't name one).
With `commit:path` it prints a file as it was in that commit, as above, which is handy when you want
to see an old version without changing anything.

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
echo "Emma" >> books.txt
git commit -q -am "Add Emma"
git show
```

The lines starting with `+` were added and the lines starting with `-` were removed.

## git diff: what changed?

`git diff` answers "what have I changed that isn't staged yet?". Once you stage the change,
`git diff` goes quiet and `git diff --staged` (also spelt `--cached`) shows it instead: what the
next commit will contain. Checking it before every commit is a good habit.

```git
git init -q shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
echo "Emma" >> books.txt
git diff
git add books.txt
git diff
git diff --staged
```

You can also compare any two commits, `git diff HEAD~2 HEAD`, and add `--stat` or `--name-only` for a
summary instead of every line.
