## Sharing a repository

So far every repository has lived in one folder on one computer. To work with other people (or just to
keep a backup), you share it through a **remote**: another copy of the repository, usually on a server
such as GitHub, GitLab or Bitbucket. Everyone **pushes** their commits there and **pulls** everyone
else's.

In this sandbox there's no internet, so a folder plays the server. `git init --bare server.git` makes
a **bare** repository, one with history but no working files, which is exactly what servers hold.
Wherever you'd write a URL like `https://github.com/corner-books/shop.git`, we'll write a path.

## Cloning

`git clone` copies a repository, with all its history, and remembers where it came from under the
name `origin`:

```git
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
git remote -v
```

Alice pushed her commit to the server, and Bob's clone got it. `git remote -v` shows the remotes a
repository knows about.

If you started with `git init` instead of cloning, connect the repository yourself with
`git remote add origin <url>`, then push with `-u` the first time: `git push -u origin main`. The `-u`
makes `origin/main` your branch's **upstream**, so afterwards plain `git push` and `git pull` know where
to go.

## Fetch, pull and remote branches

Git keeps a read-only copy of each remote branch, named like `origin/main`, showing where it was the
last time you talked to the server. `git fetch` updates those copies without touching your own
branches; `git pull` fetches and then merges the upstream into your branch.

```git
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
git fetch
git status
git log --oneline --all
git pull
cat books.txt
```

After `git fetch`, `git status` says Bob's `main` is behind `origin/main`, and `git pull` brings it up
to date.

## When the server has moved on

If someone pushed while you were working, your push is **rejected**: the server won't let you
overwrite commits you haven't seen. Pull their work first, then push again.

```git
# Error: Bob's first push is rejected.
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
echo "Open 9 to 5" > hours.txt
git add hours.txt
git commit -q -m "Add opening hours"
git push
git pull --rebase
git push
git log --oneline
```

`git pull --rebase` replays your unpushed commits on top of what's on the server, which keeps the
history a straight line. Plain `git pull --no-rebase` would make a merge commit instead. (Real Git asks
you to choose once; many people set `git config --global pull.rebase true`.) If both changed the same
lines, you resolve the conflict just as in the last two lessons.

Never "fix" a rejected push with `git push --force` on a shared branch: it throws away other people's
commits on the server.

## Branches on the server

Teams usually don't push straight to `main`. You push a branch, open a **pull request** (GitHub's
name; GitLab says merge request) so others can review it, and the branch is merged on the server when
it's approved:

```git
git init -q --bare server.git
git clone -q server.git alice
cd alice
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git push -q
git switch -c search
echo "<?php" > search.php
git add search.php
git commit -q -m "Add a search page"
git push -u origin search
git branch -a
```

`git branch -a` lists remote branches as well as your own.

## Tags and releases

A **tag** is a permanent name for one commit, typically a release: `v1.0`. An annotated tag
(`-a`, with a message) also records who tagged it and when. Tags aren't pushed with branches; push them
by name, or all at once with `git push --tags`:

```git
git init -q --bare server.git
git clone -q server.git shop
cd shop
echo "Dune" > books.txt
git add books.txt
git commit -q -m "Add the book list"
git push -q
git tag -a v1.0 -m "First release"
git push origin v1.0
git tag
git log --oneline
```
