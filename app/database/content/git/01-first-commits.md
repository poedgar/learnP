## Why Git

Code changes all the time, and sooner or later you'll want to know what changed, when and why, go
back to a version that worked, or try an idea without wrecking what you have. **Git** records the
history of a folder as a series of snapshots called **commits**, and it lets many people work on the
same code without overwriting each other. Almost every software team uses it.

## About this course

The code blocks marked `git` in this course are shell scripts: commands, one per line, as you'd type
them in a terminal. Press **Try it** to run one in the sandbox. It runs a small Git inside your browser,
on a fresh, empty machine each time, so nothing you do can break anything. Besides `git`, it
understands a few shell commands for files: `echo`, `cat`, `ls`, `cd`, `mkdir`, `touch`, `rm`, `mv` and
`cp`, with `>` to write a file and `>>` to add to one.

## A repository

A **repository** is a folder whose history Git keeps. `git init` turns a folder into one (or creates
the folder first, if you give it a name); Git stores everything it needs in a hidden `.git` folder
inside:

```git
git init bookshop
cd bookshop
echo "# Corner Books" > README.md
git status
```

`git status` is the command you'll run most. Here it says we're on a **branch** called `main` (more on
branches in lesson 3), there are no commits yet, and `README.md` is **untracked**: Git sees it, but isn't
keeping its history.

## Stage, then commit

Saving a snapshot takes two steps:

1. `git add` puts changes in the **staging area** (also called the index): the list of what the next
   commit will contain.
2. `git commit` takes everything staged and stores it as a new commit, with a message saying what it
   does and why.

```git
git init bookshop
cd bookshop
echo "# Corner Books" > README.md
git add README.md
git commit -m "Add a README"
git log
```

Each commit has a unique **hash** (the long hex string; its first seven characters are usually enough
to refer to it), an author, a date and a message. `git log` lists commits, newest first; add
`--oneline` for one line each.

Why two steps? Because you rarely want to commit *everything* you changed. The staging area lets you
choose: one commit for the bug fix, another for the unrelated typo you noticed on the way.
`git add .` stages every change in the current folder, and `git commit -am "..."` stages changes to
files Git already tracks and commits in one go.

## Who you are

Every commit records its author. Git takes the name and email from its configuration, which you set
once per computer with `git config --global`, or for just one repository without `--global`:

```git
git init bookshop
cd bookshop
git config user.name "Ada Lovelace"
git config user.email ada@example.com
echo "<?php echo 'Hello';" > index.php
git add index.php
git commit -m "Add the home page"
git log --oneline
git log --format="%an <%ae>"
```

## Files Git should ignore

Some files should never be committed: logs, dependencies you can download again (`vendor`,
`node_modules`), and secrets like `.env`. List them in a file called `.gitignore`, one pattern per line,
and commit that file. Ignored files no longer show up in `git status` or get swept up by `git add .`:

```git
git init bookshop
cd bookshop
echo "*.log" > .gitignore
echo ".env" >> .gitignore
echo "debug output" > app.log
echo "SECRET=hunter2" > .env
echo "<?php" > index.php
git add .
git status --short
```

`git status --short` shows one line per file: `A` means added to the staging area. `app.log` and `.env`
aren't there at all.

## Good commit messages

A commit message is a note to your future self and your team. The usual shape is a short **subject**
line (about 50 characters, in the imperative: "Add search", not "Added search"), and, when it helps, a
blank line and a **body** explaining *why*. Each `-m` adds a paragraph:

```git
git init bookshop
cd bookshop
echo "<?php" > search.php
git add search.php
git commit -m "Add book search" -m "Customers kept asking for it, and browsing 400 titles by hand was slow."
git log
```

## Quieter output

`git init` and `git commit` report what they did. Most commands take `-q` (`--quiet`) to skip that, which
keeps an example's output down to the part that matters. You'll see it in quiz questions:

```git
git init -q bookshop
cd bookshop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git log --format=%s
```
