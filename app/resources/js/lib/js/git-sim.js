/*
 * A small Git, with just enough shell around it, for the Git course. It runs inside the sandbox
 * frame (see frame-runtime.js). The web app, the mobile app (via sources.ts) and the content tests
 * inject it as source text, so createGitShell() must stay self-contained: no imports, and nothing
 * from outside the function.
 *
 * The learner's "code" is a shell script: git commands plus a few commands for files (echo,
 * printf, cat, ls, cd, pwd, mkdir, touch, rm, mv, cp), with > and >> redirects, heredocs, && and ;.
 * Everything lives in memory: files, repositories and commits. Commit times come from a clock that
 * starts at 09:00 UTC on 1 October 2026 and moves on a minute for each commit, so the same script
 * always gives the same hashes. Output follows real Git as closely as is useful.
 *
 *   const shell = createGitShell();
 *   const commands = shell.run(script, (entry) => ...);  // entry: { level: 'command' | 'log' | 'error', text }
 *   shell.capture('git log --oneline');                  // what one command prints, for checks
 *   shell.inspect('alice');                              // a snapshot of a repository, for checks
 *
 * run() returns one record per command: { line, text, exitCode, commented, unsupported }.
 * `commented` says the line had a trailing # comment; `unsupported` marks something this Git
 * doesn't do (an unknown option, say), which the content tests treat as a mistake.
 */
export function createGitShell() {
    const HOME = '/work';
    const VERSION = '2.47.0';
    const files = new Map();
    const dirs = new Set(['/', HOME]);
    const repos = new Map();
    const commits = new Map();
    const globalConfig = new Map([
        ['user.name', 'You'],
        ['user.email', 'you@example.com'],
        ['init.defaultbranch', 'main'],
    ]);
    let cwd = HOME;
    let clock = Date.UTC(2026, 9, 1, 9, 0, 0);

    class ShellError extends Error {
        constructor(message, code = 1, extra = {}) {
            super(message);
            this.code = code;
            Object.assign(this, extra);
        }
    }
    const fatal = (message) => new ShellError(`fatal: ${message}`, 128);
    const unsupported = (message) => new ShellError(message, 1, { unsupported: true });

    // ---- SHA-1, for object names ----

    function sha1(text) {
        const utf8 = unescape(encodeURIComponent(text));
        const length = utf8.length;
        const size = (((length + 9 + 63) / 64) | 0) * 64;
        const bytes = new Uint8Array(size);
        for (let i = 0; i < length; i++) bytes[i] = utf8.charCodeAt(i);
        bytes[length] = 0x80;
        const view = new DataView(bytes.buffer);
        view.setUint32(size - 4, length * 8);
        const h = [0x67452301, 0xefcdab89, 0x98badcfe, 0x10325476, 0xc3d2e1f0];
        const w = new Uint32Array(80);
        for (let offset = 0; offset < size; offset += 64) {
            for (let i = 0; i < 16; i++) w[i] = view.getUint32(offset + i * 4);
            for (let i = 16; i < 80; i++) {
                const x = w[i - 3] ^ w[i - 8] ^ w[i - 14] ^ w[i - 16];
                w[i] = (x << 1) | (x >>> 31);
            }
            let [a, b, c, d, e] = h;
            for (let i = 0; i < 80; i++) {
                let f, k;
                if (i < 20) [f, k] = [(b & c) | (~b & d), 0x5a827999];
                else if (i < 40) [f, k] = [b ^ c ^ d, 0x6ed9eba1];
                else if (i < 60) [f, k] = [(b & c) | (b & d) | (c & d), 0x8f1bbcdc];
                else [f, k] = [b ^ c ^ d, 0xca62c1d6];
                const t = (((a << 5) | (a >>> 27)) + f + e + k + w[i]) >>> 0;
                e = d;
                d = c;
                c = ((b << 30) | (b >>> 2)) >>> 0;
                b = a;
                a = t;
            }
            h[0] = (h[0] + a) >>> 0;
            h[1] = (h[1] + b) >>> 0;
            h[2] = (h[2] + c) >>> 0;
            h[3] = (h[3] + d) >>> 0;
            h[4] = (h[4] + e) >>> 0;
        }
        return h.map((word) => word.toString(16).padStart(8, '0')).join('');
    }

    const byteLength = (text) => unescape(encodeURIComponent(text)).length;
    const blobId = (content) => sha1(`blob ${byteLength(content)}\0${content}`);
    const short = (hash) => hash.slice(0, 7);
    const treeId = (tree) => sha1(`tree\n${[...tree].sort(([a], [b]) => (a < b ? -1 : 1)).map(([path, content]) => `${path}\0${blobId(content)}`).join('\n')}`);

    // ---- Paths and files ----

    function normalize(path) {
        const parts = [];
        for (const part of path.split('/')) {
            if (part === '' || part === '.') continue;
            if (part === '..') parts.pop();
            else parts.push(part);
        }
        return `/${parts.join('/')}`;
    }

    function resolve(path, from = cwd) {
        if (path === '~' || path.startsWith('~/')) path = HOME + path.slice(1);
        return normalize(path.startsWith('/') ? path : `${from}/${path}`);
    }

    const parentOf = (path) => (path === '/' ? '/' : path.slice(0, path.lastIndexOf('/')) || '/');
    const baseName = (path) => path.slice(path.lastIndexOf('/') + 1);
    const inside = (path, dir) => dir === '/' || path === dir || path.startsWith(`${dir}/`);
    const sorted = (list) => [...list].sort((a, b) => (a < b ? -1 : a > b ? 1 : 0));

    function relativePath(from, to) {
        const a = from === '/' ? [] : from.slice(1).split('/');
        const b = to === '/' ? [] : to.slice(1).split('/');
        let i = 0;
        while (i < a.length && i < b.length && a[i] === b[i]) i++;
        return [...a.slice(i).map(() => '..'), ...b.slice(i)].join('/') || '.';
    }

    function makeDirs(path) {
        for (let p = path; !dirs.has(p); p = parentOf(p)) dirs.add(p);
    }

    function writeFile(path, content) {
        makeDirs(parentOf(path));
        files.set(path, content);
    }

    function children(dir) {
        const names = new Set();
        for (const path of [...files.keys(), ...dirs]) {
            if (path !== dir && parentOf(path) === dir) names.add(baseName(path));
        }
        return sorted(names);
    }

    function removeTree(path) {
        for (const file of [...files.keys()]) if (inside(file, path)) files.delete(file);
        for (const dir of [...dirs]) if (dir !== '/' && inside(dir, path)) dirs.delete(dir);
        for (const root of [...repos.keys()]) if (inside(root, path)) repos.delete(root);
    }

    // ---- Repositories ----

    function newRepo(root, bare, branch) {
        return {
            root,
            bare,
            head: { branch, detached: null },
            heads: new Map(),
            tags: new Map(),
            remoteRefs: new Map(),
            remotes: new Map(),
            upstream: new Map(),
            index: new Map(),
            conflicts: new Map(),
            config: new Map(),
            reflog: [],
            stash: [],
            merge: null,
            sequence: null,
            rebase: null,
            origHead: null,
            fetchHead: null,
            previous: null,
        };
    }

    function repoAt(dir) {
        for (let d = dir; ; d = parentOf(d)) {
            if (repos.has(d)) return repos.get(d);
            if (d === '/') return null;
        }
    }

    function needRepo() {
        const repo = repoAt(cwd);
        if (!repo) throw fatal('not a git repository (or any of the parent directories): .git');
        return repo;
    }

    function needWorkTree() {
        const repo = needRepo();
        if (repo.bare) throw fatal('this operation must be run in a work tree');
        return repo;
    }

    const workPath = (repo, rel) => (repo.root === '/' ? `/${rel}` : `${repo.root}/${rel}`);
    const gitDir = (repo) => (repo.bare ? `${repo.root}/` : `${repo.root === '/' ? '' : repo.root}/.git/`);

    /** The repository's working files, by path relative to its root (not .git, nor nested repositories). */
    function worktree(repo) {
        const nested = [...repos.keys()].filter((root) => root !== repo.root && inside(root, repo.root));
        const result = new Map();
        const prefix = repo.root === '/' ? 1 : repo.root.length + 1;
        for (const path of sorted(files.keys())) {
            if (!inside(path, repo.root) || path === repo.root) continue;
            if (inside(path, workPath(repo, '.git')) || nested.some((root) => inside(path, root))) continue;
            result.set(path.slice(prefix), files.get(path));
        }
        return result;
    }

    function setWork(repo, rel, content) {
        if (content === undefined) {
            removeWork(repo, rel);
        } else {
            writeFile(workPath(repo, rel), content);
        }
    }

    function removeWork(repo, rel) {
        const path = workPath(repo, rel);
        files.delete(path);
        for (let dir = parentOf(path); dir !== repo.root && inside(dir, repo.root) && !inside(cwd, dir); dir = parentOf(dir)) {
            if (children(dir).length) break;
            dirs.delete(dir);
        }
    }

    /** A path in the repository, shown relative to where the learner is. */
    function shown(repo, rel) {
        return inside(cwd, repo.root) ? relativePath(cwd, workPath(repo, rel)) : rel;
    }

    function config(repo, key) {
        return repo?.config.get(key) ?? globalConfig.get(key) ?? null;
    }

    // ---- Commits ----

    const subject = (commit) => commit.message.split('\n')[0];
    const headHash = (repo) => (repo.head.branch !== null ? (repo.heads.get(repo.head.branch) ?? null) : repo.head.detached);
    const treeOf = (hash) => (hash ? commits.get(hash).tree : new Map());

    function sameTree(a, b) {
        if (a.size !== b.size) return false;
        for (const [path, content] of a) if (b.get(path) !== content) return false;
        return true;
    }

    function createCommit(repo, { tree, parents, message, author = null }) {
        clock += 60000;
        const committer = { name: config(repo, 'user.name'), email: config(repo, 'user.email'), time: clock };
        const by = author ?? committer;
        const text = [
            `tree ${treeId(tree)}`,
            ...parents.map((parent) => `parent ${parent}`),
            `author ${by.name} <${by.email}> ${by.time / 1000} +0000`,
            `committer ${committer.name} <${committer.email}> ${committer.time / 1000} +0000`,
            '',
            message,
        ].join('\n');
        const hash = sha1(`commit ${byteLength(text)}\0${text}`);
        commits.set(hash, { hash, tree: new Map(tree), parents: [...parents], message, author: by, committer });
        return hash;
    }

    function logRef(repo, hash, message) {
        if (hash) repo.reflog.unshift({ hash, message });
    }

    /** Moves the current branch (or the detached HEAD) to `hash`. */
    function setHead(repo, hash, message) {
        if (repo.head.branch !== null) repo.heads.set(repo.head.branch, hash);
        else repo.head.detached = hash;
        logRef(repo, hash, message);
    }

    function headLabel(repo) {
        return repo.head.branch ?? short(repo.head.detached);
    }

    // ---- Revisions ----

    function baseRef(repo, name) {
        if (name === 'HEAD' || name === '@') return headHash(repo);
        if (name === 'ORIG_HEAD') return repo.origHead;
        if (name === 'MERGE_HEAD') return repo.merge?.theirs ?? null;
        if (name === 'FETCH_HEAD') return repo.fetchHead;
        if (name === 'CHERRY_PICK_HEAD') return repo.sequence?.kind === 'cherry-pick' ? repo.sequence.commit : null;
        if (name === 'stash') return repo.stash[0]?.hash ?? null;
        for (const prefix of ['refs/heads/', 'refs/tags/', 'refs/remotes/']) {
            if (name.startsWith(prefix)) name = name.slice(prefix.length);
        }
        if (repo.heads.has(name)) return repo.heads.get(name);
        if (repo.tags.has(name)) return repo.tags.get(name).hash;
        if (repo.remoteRefs.has(name)) return repo.remoteRefs.get(name);
        if (repo.remotes.has(name)) {
            const remote = repoForRemote(repo, name, false);
            const branch = remote?.head.branch ?? 'main';
            return repo.remoteRefs.get(`${name}/${branch}`) ?? null;
        }
        if (/^[0-9a-f]{4,40}$/.test(name)) {
            const matches = [...commits.keys()].filter((hash) => hash.startsWith(name));
            if (matches.length > 1) throw new ShellError(`error: short object ID ${name} is ambiguous`, 128);
            return matches[0] ?? null;
        }
        return null;
    }

    /** The commit a revision names (HEAD~2, main^2, a1b2c3d, HEAD@{1}, origin/main, v1.0), or null. */
    function resolveRev(repo, spec) {
        const stash = /^stash@\{(\d+)\}$/.exec(spec);
        if (stash) return repo.stash[Number(stash[1])]?.hash ?? null;
        const match = /^(.*?)((?:[~^]\d*|@\{\d+\})*)$/.exec(spec);
        const base = match[1] || 'HEAD';
        let ops = match[2];
        let hash;
        const reflog = /^@\{(\d+)\}/.exec(ops);
        if (reflog) {
            if (base !== 'HEAD' && base !== '@') return null;
            hash = repo.reflog[Number(reflog[1])]?.hash ?? null;
            ops = ops.slice(reflog[0].length);
        } else {
            hash = baseRef(repo, base);
        }
        for (const [, op, digits] of ops.matchAll(/([~^])(\d*)/g)) {
            if (!hash) return null;
            const n = digits === '' ? 1 : Number(digits);
            if (op === '~') {
                for (let i = 0; i < n && hash; i++) hash = commits.get(hash).parents[0] ?? null;
            } else if (n > 0) {
                hash = commits.get(hash).parents[n - 1] ?? null;
            }
        }
        return hash;
    }

    function revOrFatal(repo, spec) {
        const hash = resolveRev(repo, spec);
        if (hash) return hash;
        if ((spec === 'HEAD' || spec === '@') && !headHash(repo)) {
            throw fatal(`your current branch '${repo.head.branch}' does not have any commits yet`);
        }
        throw fatal(`ambiguous argument '${spec}': unknown revision or path not in the working tree.\nUse '--' to separate paths from revisions, like this:\n'git <command> [<revision>...] -- [<file>...]'`);
    }

    // ---- History ----

    function ancestors(hash) {
        const seen = new Set();
        const stack = hash ? [hash] : [];
        while (stack.length) {
            const next = stack.pop();
            if (seen.has(next)) continue;
            seen.add(next);
            stack.push(...commits.get(next).parents);
        }
        return seen;
    }

    const isAncestor = (a, b) => ancestors(b).has(a);
    const byTime = (a, b) => commits.get(b).committer.time - commits.get(a).committer.time;

    function mergeBase(a, b) {
        const fromA = ancestors(a);
        const common = [...ancestors(b)].filter((hash) => fromA.has(hash));
        const best = common.filter((hash) => !common.some((other) => other !== hash && ancestors(other).has(hash)));
        return best.sort(byTime)[0] ?? null;
    }

    /** Parents before children, keeping each line of history together (like git log --topo-order). */
    function topoOrder(hashes) {
        const all = new Set(hashes);
        const waiting = new Map([...all].map((hash) => [hash, 0]));
        for (const hash of all) {
            for (const parent of commits.get(hash).parents) if (all.has(parent)) waiting.set(parent, waiting.get(parent) + 1);
        }
        const stack = [...all].filter((hash) => waiting.get(hash) === 0).sort((a, b) => -byTime(a, b));
        const order = [];
        while (stack.length) {
            const hash = stack.pop();
            order.push(hash);
            const parents = commits.get(hash).parents.filter((parent) => all.has(parent));
            for (const parent of parents) waiting.set(parent, waiting.get(parent) - 1);
            for (const parent of parents) if (waiting.get(parent) === 0) stack.push(parent);
        }
        return order;
    }

    // ---- Diffs ----

    function splitLines(content) {
        if (!content) return [];
        const lines = content.split('\n');
        if (lines[lines.length - 1] === '') lines.pop();
        return lines;
    }

    const joinLines = (lines) => (lines.length ? `${lines.join('\n')}\n` : '');

    /** A line diff: [{ op: ' ' | '-' | '+', a, b }], deletions before insertions. */
    function diffOps(a, b) {
        let start = 0;
        while (start < a.length && start < b.length && a[start] === b[start]) start++;
        let endA = a.length;
        let endB = b.length;
        while (endA > start && endB > start && a[endA - 1] === b[endB - 1]) {
            endA--;
            endB--;
        }
        const n = endA - start;
        const m = endB - start;
        const lcs = Array.from({ length: n + 1 }, () => new Int32Array(m + 1));
        for (let i = n - 1; i >= 0; i--) {
            for (let j = m - 1; j >= 0; j--) {
                lcs[i][j] = a[start + i] === b[start + j] ? lcs[i + 1][j + 1] + 1 : Math.max(lcs[i + 1][j], lcs[i][j + 1]);
            }
        }
        const ops = [];
        for (let k = 0; k < start; k++) ops.push({ op: ' ', a: k, b: k });
        let i = 0;
        let j = 0;
        while (i < n || j < m) {
            if (i < n && j < m && a[start + i] === b[start + j]) {
                ops.push({ op: ' ', a: start + i, b: start + j });
                i++;
                j++;
            } else if (j >= m || (i < n && lcs[i + 1][j] >= lcs[i][j + 1])) {
                ops.push({ op: '-', a: start + i });
                i++;
            } else {
                ops.push({ op: '+', b: start + j });
                j++;
            }
        }
        for (let k = 0; k < a.length - endA; k++) ops.push({ op: ' ', a: endA + k, b: endB + k });
        return ops;
    }

    function hunks(a, b, context = 3) {
        const ops = diffOps(a, b);
        const changed = ops.map((op, i) => (op.op === ' ' ? -1 : i)).filter((i) => i >= 0);
        if (!changed.length) return [];
        const groups = [];
        let first = changed[0];
        let last = changed[0];
        for (const i of changed.slice(1)) {
            if (i - last - 1 > 2 * context) {
                groups.push([first, last]);
                first = i;
            }
            last = i;
        }
        groups.push([first, last]);
        return groups.map(([from, to]) => {
            const start = Math.max(0, from - context);
            const end = Math.min(ops.length - 1, to + context);
            const slice = ops.slice(start, end + 1);
            let oldBefore = 0;
            let newBefore = 0;
            for (const op of ops.slice(0, start)) {
                if (op.op !== '+') oldBefore++;
                if (op.op !== '-') newBefore++;
            }
            const oldCount = slice.filter((op) => op.op !== '+').length;
            const newCount = slice.filter((op) => op.op !== '-').length;
            const range = (before, count) => (count === 0 ? `${before},0` : count === 1 ? `${before + 1}` : `${before + 1},${count}`);
            return {
                header: `@@ -${range(oldBefore, oldCount)} +${range(newBefore, newCount)} @@`,
                lines: slice.map((op) => op.op + (op.op === '+' ? b[op.b] : a[op.a])),
            };
        });
    }

    /** What changed between two trees: [{ path, status: A | M | D | R, before, after, from }], by path. */
    function changes(before, after, filter = null) {
        const list = [];
        for (const path of sorted(new Set([...before.keys(), ...after.keys()]))) {
            if (filter && !filter(path)) continue;
            const old = before.get(path);
            const now = after.get(path);
            if (old === now) continue;
            list.push({ path, status: old === undefined ? 'A' : now === undefined ? 'D' : 'M', before: old, after: now });
        }
        // Exact renames: a deleted file whose content reappears under a new name.
        const used = new Set();
        const result = [];
        for (const change of list) {
            if (change.status === 'D' && change.before !== '') {
                const added = list.find((other) => other.status === 'A' && !used.has(other) && other.after === change.before);
                if (added) {
                    used.add(added);
                    result.push({ path: added.path, from: change.path, status: 'R', before: change.before, after: added.after });
                    continue;
                }
            }
            result.push(change);
        }
        return result.filter((change) => !used.has(change)).sort((x, y) => (x.path < y.path ? -1 : 1));
    }

    function patch(change) {
        const { path, status, before, after, from } = change;
        const lines = [`diff --git a/${from ?? path} b/${path}`];
        if (status === 'R') {
            lines.push('similarity index 100%', `rename from ${from}`, `rename to ${path}`);
            return lines.join('\n');
        }
        if (status === 'A') lines.push('new file mode 100644', `index 0000000..${short(blobId(after))}`);
        else if (status === 'D') lines.push('deleted file mode 100644', `index ${short(blobId(before))}..0000000`);
        else lines.push(`index ${short(blobId(before))}..${short(blobId(after))} 100644`);
        const body = hunks(splitLines(before ?? ''), splitLines(after ?? ''));
        if (body.length) {
            lines.push(status === 'A' ? '--- /dev/null' : `--- a/${path}`, status === 'D' ? '+++ /dev/null' : `+++ b/${path}`);
            for (const hunk of body) lines.push(hunk.header, ...hunk.lines);
        }
        return lines.join('\n');
    }

    function lineCounts(change) {
        if (change.status === 'R') return { add: 0, del: 0 };
        const ops = diffOps(splitLines(change.before ?? ''), splitLines(change.after ?? ''));
        return { add: ops.filter((op) => op.op === '+').length, del: ops.filter((op) => op.op === '-').length };
    }

    function summaryLine(list) {
        const counts = list.map(lineCounts);
        const add = counts.reduce((sum, c) => sum + c.add, 0);
        const del = counts.reduce((sum, c) => sum + c.del, 0);
        const parts = [`${list.length} file${list.length === 1 ? '' : 's'} changed`];
        if (add || !del) parts.push(`${add} insertion${add === 1 ? '' : 's'}(+)`);
        if (del || !add) parts.push(`${del} deletion${del === 1 ? '' : 's'}(-)`);
        return ` ${parts.join(', ')}`;
    }

    function modeLines(list) {
        return list
            .filter((change) => change.status !== 'M')
            .map((change) =>
                change.status === 'A'
                    ? ` create mode 100644 ${change.path}`
                    : change.status === 'D'
                      ? ` delete mode 100644 ${change.path}`
                      : ` rename ${change.from} => ${change.path} (100%)`,
            );
    }

    function diffstat(list) {
        if (!list.length) return [];
        const rows = list.map((change) => ({ name: change.status === 'R' ? `${change.from} => ${change.path}` : change.path, ...lineCounts(change) }));
        const nameWidth = Math.max(...rows.map((row) => row.name.length));
        const numberWidth = Math.max(...rows.map((row) => String(row.add + row.del).length));
        const most = Math.max(...rows.map((row) => row.add + row.del));
        const scale = most > 40 ? 40 / most : 1;
        return rows.map((row) => {
            let plus = Math.round(row.add * scale);
            let minus = Math.round(row.del * scale);
            if (row.add && !plus) plus = 1;
            if (row.del && !minus) minus = 1;
            const bar = plus + minus ? ` ${'+'.repeat(plus)}${'-'.repeat(minus)}` : '';
            return ` ${row.name.padEnd(nameWidth)} | ${String(row.add + row.del).padStart(numberWidth)}${bar}`;
        });
    }

    /** The lines git prints after a merge or fast-forward: per-file stats, totals and new or deleted files. */
    function statLines(list) {
        return list.length ? [...diffstat(list), summaryLine(list), ...modeLines(list)] : [];
    }

    // ---- .gitignore ----

    function ignoreRules(repo) {
        const text = files.get(workPath(repo, '.gitignore'));
        if (text === undefined) return [];
        return splitLines(text)
            .map((line, i) => ({ text: line.trim(), number: i + 1 }))
            .filter((rule) => rule.text && !rule.text.startsWith('#'))
            .map(({ text, number }) => {
                let pattern = text;
                const negate = pattern.startsWith('!');
                if (negate) pattern = pattern.slice(1);
                const dirOnly = pattern.endsWith('/');
                if (dirOnly) pattern = pattern.slice(0, -1);
                const anchored = pattern.includes('/');
                if (pattern.startsWith('/')) pattern = pattern.slice(1);
                return { regex: globRegex(pattern, false), negate, dirOnly, anchored, text, number };
            });
    }

    function globRegex(pattern, crossSlash) {
        let source = '';
        for (let i = 0; i < pattern.length; i++) {
            const ch = pattern[i];
            if (ch === '*' && pattern[i + 1] === '*') {
                source += '.*';
                i++;
            } else if (ch === '*') source += crossSlash ? '.*' : '[^/]*';
            else if (ch === '?') source += crossSlash ? '.' : '[^/]';
            else source += ch.replace(/[.+^${}()|[\]\\]/g, '\\$&');
        }
        return new RegExp(`^${source}$`);
    }

    /** The rule that ignores a path (relative to the repository), or null. */
    function ignoredBy(rules, path) {
        const parts = path.split('/');
        let result = null;
        for (const rule of rules) {
            for (let k = 1; k <= parts.length; k++) {
                if (rule.dirOnly && k === parts.length) continue;
                const candidate = rule.anchored ? parts.slice(0, k).join('/') : parts[k - 1];
                if (rule.regex.test(candidate)) {
                    result = rule.negate ? null : rule;
                    break;
                }
            }
        }
        return result;
    }

    // ---- Status ----

    function statusOf(repo) {
        const head = treeOf(headHash(repo));
        const work = worktree(repo);
        const rules = ignoreRules(repo);
        const staged = changes(head, repo.index, (path) => !repo.conflicts.has(path));
        const unstaged = [];
        for (const path of sorted(repo.index.keys())) {
            if (repo.conflicts.has(path)) continue;
            const content = repo.index.get(path);
            if (!work.has(path)) unstaged.push({ path, status: 'D', before: content });
            else if (work.get(path) !== content) unstaged.push({ path, status: 'M', before: content, after: work.get(path) });
        }
        const untracked = [...work.keys()].filter((path) => !repo.index.has(path) && !repo.conflicts.has(path) && !ignoredBy(rules, path));
        return { staged, unstaged, untracked, conflicted: sorted(repo.conflicts.keys()) };
    }

    /** Untracked paths, with wholly untracked directories shown once, as "dir/". */
    function collapseUntracked(repo, paths) {
        const tracked = [...repo.index.keys(), ...repo.conflicts.keys()];
        const result = new Set();
        for (const path of paths) {
            const parts = path.split('/');
            let entry = path;
            for (let k = 1; k < parts.length; k++) {
                const dir = parts.slice(0, k).join('/');
                if (!tracked.some((t) => t.startsWith(`${dir}/`))) {
                    entry = `${dir}/`;
                    break;
                }
            }
            result.add(entry);
        }
        return sorted(result);
    }

    function tracking(repo, branch) {
        const upstream = branch !== null ? repo.upstream.get(branch) : null;
        if (!upstream) return null;
        const name = `${upstream.remote}/${upstream.branch}`;
        const theirs = repo.remoteRefs.get(name);
        const ours = repo.heads.get(branch);
        if (theirs === undefined || ours === undefined) return { name, gone: true, ahead: 0, behind: 0 };
        const mine = ancestors(ours);
        const other = ancestors(theirs);
        return { name, gone: false, ahead: [...mine].filter((h) => !other.has(h)).length, behind: [...other].filter((h) => !mine.has(h)).length };
    }

    function trackingLines(repo) {
        const info = tracking(repo, repo.head.branch);
        if (!info) return [];
        if (info.gone) return [`Your branch is based on '${info.name}', but the upstream is gone.`, '  (use "git branch --unset-upstream" to fixup)'];
        const commitsWord = (n) => `${n} commit${n === 1 ? '' : 's'}`;
        if (!info.ahead && !info.behind) return [`Your branch is up to date with '${info.name}'.`];
        if (!info.behind) return [`Your branch is ahead of '${info.name}' by ${commitsWord(info.ahead)}.`, '  (use "git push" to publish your local commits)'];
        if (!info.ahead) return [`Your branch is behind '${info.name}' by ${commitsWord(info.behind)}, and can be fast-forwarded.`, '  (use "git pull" to update your local branch)'];
        return [
            `Your branch and '${info.name}' have diverged,`,
            `and have ${info.ahead} and ${info.behind} different commits each, respectively.`,
            '  (use "git pull" if you want to integrate the remote branch with yours)',
        ];
    }

    const LABELS = { A: 'new file:', M: 'modified:', D: 'deleted:', R: 'renamed:' };

    function longStatus(repo) {
        const head = headHash(repo);
        const s = statusOf(repo);
        const out = [];
        if (repo.rebase) {
            out.push(`rebase in progress; onto ${short(repo.rebase.onto)}`);
            out.push(`You are currently rebasing ${repo.rebase.branch ? `branch '${repo.rebase.branch}'` : 'a detached HEAD'} on '${short(repo.rebase.onto)}'.`);
            if (s.conflicted.length) {
                out.push('  (fix conflicts and then run "git rebase --continue")', '  (use "git rebase --skip" to skip this patch)', '  (use "git rebase --abort" to check out the original branch)');
            } else {
                out.push('  (all conflicts fixed: run "git rebase --continue")');
            }
            out.push('');
        } else {
            out.push(repo.head.branch !== null ? `On branch ${repo.head.branch}` : `HEAD detached at ${short(repo.head.detached)}`);
            const lines = trackingLines(repo);
            if (lines.length) out.push(...lines, '');
        }
        if (!head) out.push('', 'No commits yet', '');
        if (repo.merge) {
            if (s.conflicted.length) out.push('You have unmerged paths.', '  (fix conflicts and run "git commit")', '  (use "git merge --abort" to abort the merge)', '');
            else out.push('All conflicts fixed but you are still merging.', '  (use "git commit" to conclude merge)', '');
        }
        if (repo.sequence) {
            const { kind, commit } = repo.sequence;
            out.push(`You are currently ${kind === 'revert' ? 'reverting' : 'cherry-picking'} commit ${short(commit)}.`);
            if (s.conflicted.length) out.push(`  (fix conflicts and run "git ${kind} --continue")`, `  (use "git ${kind} --skip" to skip this patch)`, `  (use "git ${kind} --abort" to cancel the ${kind} operation)`);
            else out.push(`  (all conflicts fixed: run "git ${kind} --continue")`);
            out.push('');
        }
        if (s.conflicted.length) {
            out.push('Unmerged paths:', '  (use "git add <file>..." to mark resolution)');
            for (const path of s.conflicted) out.push(`\t${`${repo.conflicts.get(path)}:`.padEnd(17)}${shown(repo, path)}`);
            out.push('');
        }
        if (s.staged.length) {
            out.push('Changes to be committed:', head ? '  (use "git restore --staged <file>..." to unstage)' : '  (use "git rm --cached <file>..." to unstage)');
            for (const change of s.staged) {
                const name = change.status === 'R' ? `${shown(repo, change.from)} -> ${shown(repo, change.path)}` : shown(repo, change.path);
                out.push(`\t${LABELS[change.status].padEnd(12)}${name}`);
            }
            out.push('');
        }
        if (s.unstaged.length) {
            const removed = s.unstaged.some((change) => change.status === 'D');
            out.push('Changes not staged for commit:', `  (use "git ${removed ? 'add/rm' : 'add'} <file>..." to update what will be committed)`, '  (use "git restore <file>..." to discard changes in working directory)');
            for (const change of s.unstaged) out.push(`\t${LABELS[change.status].padEnd(12)}${shown(repo, change.path)}`);
            out.push('');
        }
        if (s.untracked.length) {
            out.push('Untracked files:', '  (use "git add <file>..." to include in what will be committed)');
            for (const path of collapseUntracked(repo, s.untracked)) out.push(`\t${shown(repo, path)}${path.endsWith('/') && !shown(repo, path).endsWith('/') ? '/' : ''}`);
            out.push('');
        }
        if (!s.staged.length) {
            if (s.unstaged.length || s.conflicted.length) out.push('no changes added to commit (use "git add" and/or "git commit -a")');
            else if (s.untracked.length) out.push('nothing added to commit but untracked files present (use "git add" to track)');
            else if (repo.merge || repo.sequence || repo.rebase) out.push('nothing to commit, working tree clean');
            else if (!head) out.push('nothing to commit (create/copy files and use "git add" to track)');
            else out.push('nothing to commit, working tree clean');
        }
        while (out[out.length - 1] === '') out.pop();
        return out;
    }

    function shortStatus(repo, withBranch) {
        const s = statusOf(repo);
        const out = [];
        if (withBranch) {
            const branch = repo.head.branch;
            if (!headHash(repo)) out.push(`## No commits yet on ${branch}`);
            else if (branch === null) out.push('## HEAD (no branch)');
            else {
                const info = tracking(repo, branch);
                const parts = info && !info.gone ? [info.ahead && `ahead ${info.ahead}`, info.behind && `behind ${info.behind}`].filter(Boolean) : [];
                out.push(`## ${branch}${info ? `...${info.name}` : ''}${parts.length ? ` [${parts.join(', ')}]` : ''}`);
            }
        }
        const rows = new Map();
        const codes = { 'both modified': 'UU', 'both added': 'AA', 'deleted by us': 'DU', 'deleted by them': 'UD' };
        for (const path of s.conflicted) rows.set(path, `${codes[repo.conflicts.get(path)]} ${shown(repo, path)}`);
        for (const change of s.staged) rows.set(change.path, [change.status, ' ', change.status === 'R' ? `${shown(repo, change.from)} -> ${shown(repo, change.path)}` : shown(repo, change.path)]);
        for (const change of s.unstaged) {
            const row = rows.get(change.path);
            if (Array.isArray(row)) row[1] = change.status;
            else rows.set(change.path, [' ', change.status, shown(repo, change.path)]);
        }
        for (const path of sorted(rows.keys())) {
            const row = rows.get(path);
            out.push(Array.isArray(row) ? `${row[0]}${row[1]} ${row[2]}` : row);
        }
        for (const path of collapseUntracked(repo, s.untracked)) out.push(`?? ${shown(repo, path)}`);
        return out;
    }

    // ---- Moving between commits ----

    function localChange(repo, work, path, current) {
        return repo.index.get(path) !== current.get(path) || work.get(path) !== repo.index.get(path);
    }

    /**
     * Updates the index and working files from the current HEAD's tree to `target`'s, keeping local
     * changes to files that don't differ between the two (like git switch). Throws, changing
     * nothing, if local changes or untracked files would be overwritten.
     */
    function moveTo(repo, target, action = 'checkout') {
        const current = treeOf(headHash(repo));
        const next = treeOf(target);
        const work = worktree(repo);
        const blocked = [];
        const untracked = [];
        const paths = sorted(new Set([...current.keys(), ...next.keys()]));
        for (const path of paths) {
            if (current.get(path) === next.get(path)) continue;
            if (!repo.index.has(path) && !current.has(path)) {
                if (work.has(path) && work.get(path) !== next.get(path)) untracked.push(path);
                continue;
            }
            if (localChange(repo, work, path, current) && !(repo.index.get(path) === next.get(path) && work.get(path) === next.get(path))) {
                blocked.push(path);
            }
        }
        const advice = action === 'checkout' ? 'switch branches' : action;
        if (blocked.length) {
            throw new ShellError(`error: Your local changes to the following files would be overwritten by ${action}:\n${blocked.map((p) => `\t${shown(repo, p)}`).join('\n')}\nPlease commit your changes or stash them before you ${advice}.\nAborting`, 1);
        }
        if (untracked.length) {
            throw new ShellError(`error: The following untracked working tree files would be overwritten by ${action}:\n${untracked.map((p) => `\t${shown(repo, p)}`).join('\n')}\nPlease move or remove them before you ${advice}.\nAborting`, 1);
        }
        for (const path of paths) {
            if (current.get(path) === next.get(path)) continue;
            const value = next.get(path);
            if (value === undefined) repo.index.delete(path);
            else repo.index.set(path, value);
            setWork(repo, path, value);
        }
    }

    /** Makes the index and tracked working files match `target` exactly (like git reset --hard). */
    function resetHard(repo, target) {
        const next = treeOf(target);
        for (const path of new Set([...repo.index.keys(), ...repo.conflicts.keys(), ...treeOf(headHash(repo)).keys(), ...next.keys()])) {
            const value = next.get(path);
            if (value === undefined) repo.index.delete(path);
            else repo.index.set(path, value);
            setWork(repo, path, value);
        }
        repo.conflicts = new Map();
    }

    function stage(repo, path, content) {
        if (content === undefined) repo.index.delete(path);
        else repo.index.set(path, content);
        repo.conflicts.delete(path);
    }

    // ---- Three-way merges ----

    function matchMap(base, other) {
        const map = new Array(base.length).fill(-1);
        for (const op of diffOps(base, other)) if (op.op === ' ') map[op.a] = op.b;
        return map;
    }

    const sameLines = (a, b) => a.length === b.length && a.every((line, i) => line === b[i]);

    /** Merges two edits of `base` line by line (like diff3), with conflict markers where they clash. */
    function mergeLines(base, ours, theirs, oursLabel, theirsLabel) {
        const toOurs = matchMap(base, ours);
        const toTheirs = matchMap(base, theirs);
        const out = [];
        let conflict = false;
        let i = 0;
        let o = 0;
        let t = 0;
        const chunk = (baseEnd, oursEnd, theirsEnd) => {
            const B = base.slice(i, baseEnd);
            const O = ours.slice(o, oursEnd);
            const T = theirs.slice(t, theirsEnd);
            if (sameLines(O, T)) out.push(...O);
            else if (sameLines(B, O)) out.push(...T);
            else if (sameLines(B, T)) out.push(...O);
            else {
                let p = 0;
                while (p < O.length && p < T.length && O[p] === T[p]) p++;
                let s = 0;
                while (s < O.length - p && s < T.length - p && O[O.length - 1 - s] === T[T.length - 1 - s]) s++;
                out.push(...O.slice(0, p), `<<<<<<< ${oursLabel}`, ...O.slice(p, O.length - s), '=======', ...T.slice(p, T.length - s), `>>>>>>> ${theirsLabel}`, ...O.slice(O.length - s));
                conflict = true;
            }
        };
        for (let k = 0; k < base.length; k++) {
            if (toOurs[k] < o || toTheirs[k] < t) continue;
            chunk(k, toOurs[k], toTheirs[k]);
            out.push(base[k]);
            i = k + 1;
            o = toOurs[k] + 1;
            t = toTheirs[k] + 1;
        }
        chunk(base.length, ours.length, theirs.length);
        return { text: joinLines(out), conflict };
    }

    /**
     * Merges `theirs` into `ours` relative to `base` (all trees). Returns the index after the merge
     * (`tree`, with our version of conflicted files), the working files (`work`), the conflicts and
     * the messages git prints.
     */
    function mergeTrees(base, ours, theirs, oursLabel, theirsLabel) {
        const tree = new Map();
        const work = new Map();
        const conflicts = new Map();
        const messages = [];
        for (const path of sorted(new Set([...base.keys(), ...ours.keys(), ...theirs.keys()]))) {
            const b = base.get(path);
            const o = ours.get(path);
            const t = theirs.get(path);
            let value;
            if (o === t) value = o;
            else if (b === o) value = t;
            else if (b === t) value = o;
            else if (o === undefined || t === undefined) {
                conflicts.set(path, o === undefined ? 'deleted by us' : 'deleted by them');
                messages.push(
                    o === undefined
                        ? `CONFLICT (modify/delete): ${path} deleted in ${oursLabel} and modified in ${theirsLabel}.  Version ${theirsLabel} of ${path} left in tree.`
                        : `CONFLICT (modify/delete): ${path} deleted in ${theirsLabel} and modified in ${oursLabel}.  Version ${oursLabel} of ${path} left in tree.`,
                );
                if (o !== undefined) tree.set(path, o);
                work.set(path, o ?? t);
                continue;
            } else {
                messages.push(`Auto-merging ${path}`);
                const merged = mergeLines(splitLines(b ?? ''), splitLines(o), splitLines(t), oursLabel, theirsLabel);
                if (merged.conflict) {
                    conflicts.set(path, b === undefined ? 'both added' : 'both modified');
                    messages.push(`CONFLICT (${b === undefined ? 'add/add' : 'content'}): Merge conflict in ${path}`);
                    tree.set(path, o);
                    work.set(path, merged.text);
                    continue;
                }
                value = merged.text;
            }
            if (value !== undefined) {
                tree.set(path, value);
                work.set(path, value);
            }
        }
        return { tree, work, conflicts, messages };
    }

    /** Refuses a merge-like operation that would overwrite staged work or local changes. */
    function checkCanMerge(repo, result, action) {
        const head = treeOf(headHash(repo));
        const work = worktree(repo);
        const staged = changes(head, repo.index);
        if (staged.length) {
            throw new ShellError(`error: Your local changes to the following files would be overwritten by ${action}:\n${staged.map((c) => `\t${shown(repo, c.path)}`).join('\n')}\nPlease commit your changes or stash them before you ${action}.\nAborting`, 1);
        }
        const touched = sorted(new Set([...result.work.keys(), ...head.keys()])).filter((path) => result.work.get(path) !== head.get(path) || result.conflicts.has(path));
        const dirty = touched.filter((path) => head.has(path) && work.get(path) !== head.get(path));
        if (dirty.length) {
            throw new ShellError(`error: Your local changes to the following files would be overwritten by ${action}:\n${dirty.map((p) => `\t${shown(repo, p)}`).join('\n')}\nPlease commit your changes or stash them before you ${action}.\nAborting`, 1);
        }
        const untracked = touched.filter((path) => !head.has(path) && work.has(path) && work.get(path) !== result.work.get(path));
        if (untracked.length) {
            throw new ShellError(`error: The following untracked working tree files would be overwritten by ${action}:\n${untracked.map((p) => `\t${shown(repo, p)}`).join('\n')}\nPlease move or remove them before you ${action}.\nAborting`, 1);
        }
        return touched;
    }

    function applyMerge(repo, result, touched) {
        for (const path of touched) {
            const indexValue = result.tree.get(path);
            if (indexValue === undefined) repo.index.delete(path);
            else repo.index.set(path, indexValue);
            setWork(repo, path, result.work.get(path));
        }
        repo.conflicts = new Map(result.conflicts);
    }

    // ---- Options ----

    /**
     * Parses git-style options. `spec` maps names ('-m|--message') to 'flag', 'count', 'value' or
     * 'list'. Returns { flags, positional, paths } where paths are the arguments after `--` (or null).
     */
    function parseArgs(command, args, spec, { numbers = false } = {}) {
        const lookup = new Map();
        for (const [names, type] of Object.entries(spec)) {
            const list = names.split('|');
            const key = list[list.length - 1].replace(/^-+/, '');
            for (const name of list) lookup.set(name, { key, type });
        }
        const flags = {};
        const positional = [];
        let paths = null;
        const set = (option, value, name) => {
            if (option.type === 'flag') flags[option.key] = true;
            else if (option.type === 'count') flags[option.key] = (flags[option.key] ?? 0) + 1;
            else if (value === undefined) throw fatal(`option '${name.replace(/^-+/, '')}' requires a value`);
            else if (option.type === 'list') (flags[option.key] ??= []).push(value);
            else flags[option.key] = value;
        };
        const unknown = (name) => {
            const known = [...lookup.keys()].filter((n) => n.startsWith('--')).join(', ');
            return unsupported(`error: unknown option '${name.replace(/^-+/, '')}'\n(This sandbox's git ${command} understands: ${known || 'no options'}.)`);
        };
        for (let i = 0; i < args.length; i++) {
            const arg = args[i];
            if (paths) paths.push(arg);
            else if (arg === '--') paths = [];
            else if (numbers && /^-\d+$/.test(arg)) flags.count = arg.slice(1);
            else if (arg.startsWith('--')) {
                const eq = arg.indexOf('=');
                const name = eq === -1 ? arg : arg.slice(0, eq);
                const option = lookup.get(name);
                if (!option) throw unknown(name);
                const takesValue = option.type === 'value' || option.type === 'list';
                set(option, takesValue ? (eq === -1 ? args[++i] : arg.slice(eq + 1)) : undefined, name);
            } else if (arg.startsWith('-') && arg.length > 1) {
                for (let k = 1; k < arg.length; k++) {
                    const name = `-${arg[k]}`;
                    const option = lookup.get(name);
                    if (!option) throw unknown(name);
                    if (option.type === 'value' || option.type === 'list') {
                        set(option, arg.slice(k + 1) || args[++i], name);
                        break;
                    }
                    set(option, undefined, name);
                }
            } else positional.push(arg);
        }
        return { flags, positional, paths };
    }

    /** Pathspecs to tests on repository paths. A spec with * or ? is a glob, where * also matches /. */
    function pathspecs(repo, specs) {
        const here = toRepoPath(repo, '.');
        return specs.map((spec) => {
            if (/[*?]/.test(spec)) {
                const regex = globRegex(here ? `${here}/${spec}` : spec, true);
                return { spec, exact: null, test: (path) => regex.test(path) };
            }
            const rel = toRepoPath(repo, spec);
            return { spec, exact: rel, test: (path) => rel === '' || path === rel || path.startsWith(`${rel}/`) };
        });
    }

    function toRepoPath(repo, spec) {
        const abs = resolve(spec);
        if (!inside(abs, repo.root)) throw fatal(`${spec}: '${spec}' is outside repository at '${repo.root}'`);
        return abs === repo.root ? '' : abs.slice(repo.root === '/' ? 1 : repo.root.length + 1);
    }

    function joinMessages(list) {
        return list.map((m) => m.replace(/\s+$/, '')).join('\n\n').replace(/^\n+/, '');
    }

    // ---- Formatting ----

    const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function formatDate(ms) {
        const d = new Date(ms);
        const pad = (n) => String(n).padStart(2, '0');
        return `${DAYS[d.getUTCDay()]} ${MONTHS[d.getUTCMonth()]} ${d.getUTCDate()} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())} ${d.getUTCFullYear()} +0000`;
    }

    function decorations(repo) {
        const map = new Map();
        const add = (hash, label) => {
            if (!hash) return;
            if (!map.has(hash)) map.set(hash, []);
            map.get(hash).push(label);
        };
        add(headHash(repo), repo.head.branch !== null ? `HEAD -> ${repo.head.branch}` : 'HEAD');
        for (const name of sorted(repo.tags.keys())) add(repo.tags.get(name).hash, `tag: ${name}`);
        for (const name of sorted(repo.heads.keys())) if (name !== repo.head.branch) add(repo.heads.get(name), name);
        for (const name of sorted(repo.remoteRefs.keys())) add(repo.remoteRefs.get(name), name);
        return map;
    }

    function formatCommit(repo, hash, { oneline = false, format = null, decorate = true, decor = null } = {}) {
        const commit = commits.get(hash);
        const labels = decorate ? (decor ?? decorations(repo)).get(hash) : null;
        const deco = labels ? ` (${labels.join(', ')})` : '';
        if (format !== null) {
            const values = {
                H: hash,
                h: short(hash),
                s: subject(commit),
                b: commit.message.split('\n').slice(2).join('\n'),
                an: commit.author.name,
                ae: commit.author.email,
                ad: formatDate(commit.author.time),
                d: deco,
                D: labels ? labels.join(', ') : '',
                P: commit.parents.join(' '),
                p: commit.parents.map(short).join(' '),
                n: '\n',
                '%': '%',
            };
            return format.replace(/%(an|ae|ad|[HhsbdDPpn%])/g, (_, key) => values[key]).split('\n');
        }
        if (oneline) return [`${short(hash)}${deco} ${subject(commit)}`];
        const lines = [`commit ${hash}${deco}`];
        if (commit.parents.length > 1) lines.push(`Merge: ${commit.parents.map(short).join(' ')}`);
        lines.push(`Author: ${commit.author.name} <${commit.author.email}>`, `Date:   ${formatDate(commit.author.time)}`, '');
        for (const line of commit.message.split('\n')) lines.push(line ? `    ${line}` : '');
        return lines;
    }

    /** Draws the commit graph of git log --graph beside each commit's lines. */
    function graph(order, render) {
        const shownSet = new Set(order);
        const columns = [];
        const out = [];
        for (const hash of order) {
            let index = columns.indexOf(hash);
            if (index === -1) {
                columns.push(hash);
                index = columns.length - 1;
            }
            const parents = commits.get(hash).parents.filter((parent) => shownSet.has(parent));
            const extra = parents.slice(1).filter((parent) => !columns.includes(parent));
            const width = columns.length + extra.length;
            const cells = columns.map((_, i) => (i === index ? '*' : '|')).join(' ');
            const text = render(hash);
            out.push(`${cells.padEnd(width * 2 - 1)} ${text[0]}`.trimEnd());
            if (parents.length === 0) columns.splice(index, 1);
            else columns[index] = parents[0];
            if (extra.length) {
                const before = columns.length;
                columns.splice(index + 1, 0, ...extra);
                const chars = new Array(columns.length * 2).fill(' ');
                for (let i = 0; i <= index; i++) chars[i * 2] = '|';
                for (let e = 0; e < extra.length; e++) chars[index * 2 + 1 + e * 2] = '\\';
                for (let j = index + 1; j < before; j++) chars[(j + extra.length) * 2 - 1] = '\\';
                out.push(chars.join('').trimEnd());
            }
            const bars = columns.map(() => '|').join(' ');
            for (const line of text.slice(1)) out.push(`${bars}${line ? ` ${line}` : ''}`.trimEnd());
            for (;;) {
                const j = columns.findIndex((h, i) => columns.indexOf(h) !== i);
                if (j === -1) break;
                const chars = new Array(columns.length * 2).fill(' ');
                for (let i = 0; i < j; i++) chars[i * 2] = '|';
                for (let i = j; i < columns.length; i++) chars[i * 2 - 1] = '/';
                columns.splice(j, 1);
                out.push(chars.join('').trimEnd());
            }
        }
        return out;
    }

    function commitReport(repo, hash, { showDate = false } = {}) {
        const commit = commits.get(hash);
        const where = repo.head.branch !== null ? repo.head.branch : 'detached HEAD';
        const lines = [`[${where}${commit.parents.length === 0 ? ' (root-commit)' : ''} ${short(hash)}] ${subject(commit)}`];
        if (showDate) lines.push(` Date: ${formatDate(commit.author.time)}`);
        if (commit.parents.length > 1) return lines;
        const list = changes(treeOf(commit.parents[0] ?? null), commit.tree);
        if (list.length) lines.push(summaryLine(list), ...modeLines(list));
        return lines;
    }

    // ---- Shell input ----

    function tokenize(line) {
        const tokens = [];
        let word = null;
        let glob = false;
        let commentAt = -1;
        const flush = () => {
            if (word !== null) tokens.push({ type: 'word', value: word, glob });
            word = null;
            glob = false;
        };
        let i = 0;
        while (i < line.length) {
            const ch = line[i];
            if (ch === ' ' || ch === '\t') {
                flush();
                i++;
            } else if (ch === '#' && word === null) {
                commentAt = i;
                break;
            } else if (ch === "'") {
                const end = line.indexOf("'", i + 1);
                if (end === -1) throw new ShellError('syntax error: unterminated quoted string', 2, { unterminated: true });
                word = (word ?? '') + line.slice(i + 1, end);
                i = end + 1;
            } else if (ch === '"') {
                let j = i + 1;
                let text = '';
                while (j < line.length && line[j] !== '"') {
                    if (line[j] === '\\' && j + 1 < line.length && '"\\$`'.includes(line[j + 1])) {
                        text += line[j + 1];
                        j += 2;
                    } else {
                        text += line[j];
                        j++;
                    }
                }
                if (j >= line.length) throw new ShellError('syntax error: unterminated quoted string', 2, { unterminated: true });
                word = (word ?? '') + text;
                i = j + 1;
            } else if (ch === '\\') {
                word = (word ?? '') + (line[i + 1] ?? '');
                i += 2;
            } else {
                const op = ['&&', '||', '>>', '<<', ';', '|', '>', '<', '&'].find((o) => line.startsWith(o, i));
                if (op) {
                    flush();
                    tokens.push({ type: 'op', value: op });
                    i += op.length;
                } else {
                    if (ch === '*' || ch === '?') glob = true;
                    word = (word ?? '') + ch;
                    i++;
                }
            }
        }
        flush();
        return { tokens, commentAt };
    }

    function expandGlob(pattern) {
        const regex = globRegex(pattern, false);
        const absolute = pattern.startsWith('/');
        const found = [];
        for (const path of new Set([...files.keys(), ...dirs])) {
            if (!inside(path, absolute ? '/' : cwd) || path === cwd) continue;
            const rel = absolute ? path : relativePath(cwd, path);
            if (rel.startsWith('..')) continue;
            if (rel.split('/').some((part, i) => part.startsWith('.') && !pattern.split('/')[i]?.startsWith('.'))) continue;
            if (regex.test(rel)) found.push(rel);
        }
        return found.length ? sorted(found) : [pattern];
    }

    // ---- Shell commands ----

    function output() {
        const chunks = [];
        const push = (level, text) => {
            const last = chunks[chunks.length - 1];
            if (last && last.level === level) last.text += text;
            else chunks.push({ level, text });
        };
        return {
            chunks,
            write: (text) => push('log', text),
            out: (...lines) => lines.forEach((line) => push('log', `${line}\n`)),
            err: (...lines) => lines.forEach((line) => push('error', `${line}\n`)),
        };
    }

    /** Keeps a command's errors but drops its other output (for -q). */
    function quietly(io) {
        return { ...io, write: () => {}, out: () => {} };
    }

    function unescapeText(text) {
        return text.replace(/\\(.)/g, (_, ch) => ({ n: '\n', t: '\t', '\\': '\\', a: '', e: '' })[ch] ?? `\\${ch}`);
    }

    function needFile(name, command) {
        const path = resolve(name);
        if (dirs.has(path)) throw new ShellError(`${command}: ${name}: Is a directory`);
        if (!files.has(path)) throw new ShellError(`${command}: ${name}: No such file or directory`);
        return path;
    }

    const EDITORS = ['nano', 'vim', 'vi', 'emacs', 'code', 'edit', 'pico', 'subl'];

    const shellCommands = {
        echo(args, io) {
            let newline = true;
            let escapes = false;
            while (args.length && /^-[neE]+$/.test(args[0])) {
                const flag = args.shift();
                if (flag.includes('n')) newline = false;
                if (flag.includes('e')) escapes = true;
            }
            const text = args.join(' ');
            io.write((escapes ? unescapeText(text) : text) + (newline ? '\n' : ''));
            return 0;
        },
        printf(args, io) {
            if (!args.length) throw new ShellError('printf: usage: printf format [arguments]', 2);
            const [format, ...values] = args;
            let text = '';
            let index = 0;
            do {
                text += unescapeText(format).replace(/%([sd%])/g, (_, kind) => (kind === '%' ? '%' : (values[index++] ?? '')));
            } while (index > 0 && index < values.length);
            io.write(text);
            return 0;
        },
        cat(args, io, stdin) {
            if (!args.length) {
                io.write(stdin ?? '');
                return 0;
            }
            let code = 0;
            for (const name of args) {
                try {
                    io.write(files.get(needFile(name, 'cat')));
                } catch (error) {
                    io.err(error.message);
                    code = 1;
                }
            }
            return code;
        },
        ls(args, io) {
            const options = args.filter((a) => a.startsWith('-') && a.length > 1).join('');
            const all = options.includes('a');
            const almost = options.includes('A');
            const mark = options.includes('F') || options.includes('p');
            const perLine = options.includes('1') || options.includes('l');
            const targets = args.filter((a) => !a.startsWith('-') || a.length === 1);
            let code = 0;
            const listing = (dir) => {
                const repo = repos.get(dir);
                let names = repo?.bare ? ['HEAD', 'config', 'description', 'hooks', 'info', 'objects', 'refs'] : children(dir);
                if (repo && !repo.bare) names = sorted([...names, '.git']);
                if (!all && !almost) names = names.filter((name) => !name.startsWith('.'));
                if (all) names = ['.', '..', ...names];
                if (mark) names = names.map((name) => (dirs.has(resolve(name, dir)) || name === '.git' ? `${name}/` : name));
                return names;
            };
            const lines = [];
            for (const target of targets.length ? targets : ['.']) {
                const path = resolve(target);
                if (files.has(path)) lines.push({ target, names: [target] });
                else if (dirs.has(path)) lines.push({ target, names: listing(path), dir: true });
                else {
                    io.err(`ls: ${target}: No such file or directory`);
                    code = 1;
                }
            }
            lines.forEach((entry, i) => {
                if (targets.length > 1 && entry.dir) io.out(`${i ? '\n' : ''}${entry.target}:`);
                if (entry.names.length) io.out(entry.names.join(perLine ? '\n' : '  '));
            });
            return code;
        },
        cd(args) {
            const target = resolve(args[0] ?? '~');
            if (files.has(target)) throw new ShellError(`cd: not a directory: ${args[0]}`);
            if (!dirs.has(target)) throw new ShellError(`cd: no such file or directory: ${args[0]}`);
            cwd = target;
            return 0;
        },
        pwd(args, io) {
            io.out(cwd);
            return 0;
        },
        mkdir(args, io) {
            const parents = args.includes('-p');
            let code = 0;
            for (const name of args.filter((a) => !a.startsWith('-'))) {
                const path = resolve(name);
                if (dirs.has(path) || files.has(path)) {
                    if (!parents) {
                        io.err(`mkdir: ${name}: File exists`);
                        code = 1;
                    }
                } else if (!parents && !dirs.has(parentOf(path))) {
                    io.err(`mkdir: ${name}: No such file or directory`);
                    code = 1;
                } else makeDirs(path);
            }
            return code;
        },
        touch(args, io) {
            for (const name of args) {
                const path = resolve(name);
                if (!dirs.has(parentOf(path))) {
                    io.err(`touch: ${name}: No such file or directory`);
                    return 1;
                }
                if (!files.has(path) && !dirs.has(path)) files.set(path, '');
            }
            return 0;
        },
        rm(args, io) {
            const options = args.filter((a) => a.startsWith('-') && a.length > 1).join('');
            const recursive = /[rR]/.test(options);
            const force = options.includes('f');
            let code = 0;
            for (const name of args.filter((a) => !a.startsWith('-') || a.length === 1)) {
                const path = resolve(name);
                const repo = repos.get(parentOf(path));
                if (baseName(path) === '.git' && repo && !repo.bare) {
                    if (!recursive) {
                        io.err(`rm: ${name}: is a directory`);
                        code = 1;
                    } else repos.delete(repo.root);
                } else if (files.has(path)) files.delete(path);
                else if (dirs.has(path)) {
                    if (!recursive) {
                        io.err(`rm: ${name}: is a directory`);
                        code = 1;
                    } else if (inside(cwd, path)) {
                        io.err(`rm: ${name}: you are inside it; cd out of it first`);
                        code = 1;
                    } else removeTree(path);
                } else if (!force) {
                    io.err(`rm: ${name}: No such file or directory`);
                    code = 1;
                }
            }
            return code;
        },
        mv(args, io) {
            if (args.length < 2) throw new ShellError('usage: mv source target', 64);
            const target = resolve(args[args.length - 1]);
            const sources = args.slice(0, -1);
            if (sources.length > 1 && !dirs.has(target)) throw new ShellError(`mv: ${args[args.length - 1]} is not a directory`);
            for (const name of sources) {
                const from = resolve(name);
                const to = dirs.has(target) ? `${target === '/' ? '' : target}/${baseName(from)}` : target;
                if (files.has(from)) {
                    if (!dirs.has(parentOf(to))) throw new ShellError(`mv: rename ${name} to ${args[args.length - 1]}: No such file or directory`);
                    const content = files.get(from);
                    files.delete(from);
                    files.set(to, content);
                } else if (dirs.has(from)) {
                    if (inside(to, from)) throw new ShellError(`mv: can't move ${name} into itself`);
                    for (const path of [...files.keys()]) {
                        if (inside(path, from)) {
                            const content = files.get(path);
                            files.delete(path);
                            files.set(to + path.slice(from.length), content);
                        }
                    }
                    for (const dir of [...dirs]) {
                        if (inside(dir, from)) {
                            dirs.delete(dir);
                            dirs.add(to + dir.slice(from.length));
                        }
                    }
                    for (const root of [...repos.keys()]) {
                        if (inside(root, from)) {
                            const repo = repos.get(root);
                            repos.delete(root);
                            repo.root = to + root.slice(from.length);
                            repos.set(repo.root, repo);
                        }
                    }
                    if (inside(cwd, from)) cwd = to + cwd.slice(from.length);
                } else {
                    io.err(`mv: ${name}: No such file or directory`);
                    return 1;
                }
            }
            return 0;
        },
        cp(args, io) {
            const recursive = args.some((a) => /^-[a-zA-Z]*[rR]/.test(a));
            const names = args.filter((a) => !a.startsWith('-'));
            if (names.length < 2) throw new ShellError('usage: cp source target', 64);
            const target = resolve(names[names.length - 1]);
            for (const name of names.slice(0, -1)) {
                const from = resolve(name);
                const to = dirs.has(target) ? `${target === '/' ? '' : target}/${baseName(from)}` : target;
                if (files.has(from)) writeFile(to, files.get(from));
                else if (dirs.has(from)) {
                    if (!recursive) {
                        io.err(`cp: ${name} is a directory (not copied).`);
                        return 1;
                    }
                    makeDirs(to);
                    for (const path of [...files.keys()]) if (inside(path, from)) writeFile(to + path.slice(from.length), files.get(path));
                } else {
                    io.err(`cp: ${name}: No such file or directory`);
                    return 1;
                }
            }
            return 0;
        },
        clear() {
            return 0;
        },
        help(args, io) {
            io.out(
                'This sandbox has a small shell and a Git that lives in memory.',
                '',
                'Files:  echo, printf, cat, ls, cd, pwd, mkdir, touch, rm, mv, cp',
                '        write to a file with > (replace) or >> (append), or a heredoc:',
                "        cat > notes.txt <<'EOF' ... EOF",
                '        chain commands with && or ;',
                '',
                'Git:    init status add rm mv commit log show diff restore switch checkout branch',
                '        merge rebase cherry-pick revert reset stash tag remote clone fetch pull push',
                '        config reflog clean ls-files check-ignore rev-parse',
                '',
                "There's no text editor, so give commit messages with -m.",
            );
            return 0;
        },
        git(args, io) {
            return runGit(args, io);
        },
    };

    // ---- Git ----

    const UNSUPPORTED = ['bisect', 'blame', 'worktree', 'submodule', 'gc', 'am', 'apply', 'format-patch', 'notes', 'grep', 'describe', 'archive', 'bundle', 'filter-branch', 'sparse-checkout', 'maintenance', 'range-diff', 'request-pull', 'send-email', 'shortlog', 'whatchanged', 'citool', 'gui', 'gitk', 'instaweb', 'replace', 'fsck', 'prune', 'repack', 'mergetool', 'difftool', 'switch-branch'];

    function runGit(args, io) {
        args = [...args];
        let dir = null;
        while (args[0] === '-C') {
            args.shift();
            dir = args.shift();
        }
        if (dir !== null) {
            const saved = cwd;
            const target = resolve(dir);
            if (!dirs.has(target)) throw fatal(`cannot change to '${dir}': No such file or directory`);
            cwd = target;
            try {
                return runGit(args, io);
            } finally {
                cwd = saved;
            }
        }
        const [name, ...rest] = args;
        if (!name || name === '--help' || name === 'help' || name === '-h') {
            io.out('usage: git <command> [<args>]', '', "Run 'help' to see the commands this sandbox's git understands.");
            return name ? 0 : 1;
        }
        if (name === '--version' || name === 'version') {
            io.out(`git version ${VERSION} (learnP sandbox)`);
            return 0;
        }
        const command = gitCommands[name];
        if (command) return command(rest, io) ?? 0;
        if (UNSUPPORTED.includes(name)) throw unsupported(`git ${name} isn't available in this sandbox's git.`);
        throw new ShellError(`git: '${name}' is not a git command. See 'git --help'.`, 1);
    }

    function validBranchName(name) {
        if (!name || name.startsWith('-') || name.endsWith('/') || name.endsWith('.lock') || /\.\.|[\s~^:?*[\\]|@\{|^\.|\/\./.test(name) || name === 'HEAD') {
            throw fatal(`'${name}' is not a valid branch name`);
        }
    }

    function remoteBranchFor(repo, name) {
        const matches = [...repo.remoteRefs.keys()].filter((ref) => ref.slice(ref.indexOf('/') + 1) === name);
        return matches.length === 1 ? matches[0] : null;
    }

    function setUpstream(repo, branch, ref, io) {
        const slash = ref.indexOf('/');
        repo.upstream.set(branch, { remote: ref.slice(0, slash), branch: ref.slice(slash + 1) });
        io?.out(`branch '${branch}' set up to track '${ref}'.`);
    }

    function orphanWarning(repo, from, to, io) {
        const kept = new Set();
        for (const hash of [...repo.heads.values(), ...repo.remoteRefs.values(), ...[...repo.tags.values()].map((t) => t.hash), to]) {
            for (const h of ancestors(hash)) kept.add(h);
        }
        const lost = [...ancestors(from)].filter((h) => !kept.has(h)).sort(byTime);
        if (lost.length) {
            io.out(
                `Warning: you are leaving ${lost.length} commit${lost.length === 1 ? '' : 's'} behind, not connected to`,
                'any of your branches:',
                '',
                ...lost.slice(0, 5).map((h) => `  ${short(h)} ${subject(commits.get(h))}`),
                '',
                'If you want to keep it by creating a new branch, this may be a good time',
                'to do so with:',
                '',
                ` git branch <new-branch-name> ${short(lost[0])}`,
                '',
            );
        } else {
            io.out(`Previous HEAD position was ${short(from)} ${subject(commits.get(from))}`);
        }
    }

    function busy(repo) {
        if (repo.merge) throw fatal('cannot switch branch while merging\nConsider "git merge --quit" or "git merge --abort".');
        if (repo.rebase) throw fatal('cannot switch branch while rebasing\nConsider "git rebase --quit" or "git rebase --abort".');
    }

    function switchBranch(repo, name, io, command) {
        busy(repo);
        if (name === '-') {
            name = repo.previous;
            if (!name) throw fatal('invalid reference: @{-1}');
            if (!repo.heads.has(name)) return detach(repo, revOrFatal(repo, name), name, io);
        }
        if (!repo.heads.has(name)) {
            const remote = remoteBranchFor(repo, name);
            if (!remote) {
                if (command === 'switch') throw fatal(`invalid reference: ${name}`);
                throw new ShellError(`error: pathspec '${name}' did not match any file(s) known to git`, 1);
            }
            return createBranch(repo, name, remote, io, false);
        }
        if (repo.head.branch === name) {
            io.out(`Already on '${name}'`);
            io.out(...trackingLines(repo));
            return 0;
        }
        const from = headHash(repo);
        const target = repo.heads.get(name);
        moveTo(repo, target);
        if (repo.head.branch === null && from) orphanWarning(repo, from, target, io);
        const previous = headLabel(repo);
        repo.previous = previous;
        repo.head = { branch: name, detached: null };
        logRef(repo, target, `checkout: moving from ${previous} to ${name}`);
        io.out(`Switched to branch '${name}'`);
        io.out(...trackingLines(repo));
        return 0;
    }

    function createBranch(repo, name, start, io, force) {
        busy(repo);
        validBranchName(name);
        if (repo.heads.has(name) && !force) throw fatal(`a branch named '${name}' already exists`);
        const target = start ? revOrFatal(repo, start) : headHash(repo);
        const previous = repo.head.branch ?? (repo.head.detached ? short(repo.head.detached) : null);
        if (target) moveTo(repo, target);
        if (target) repo.heads.set(name, target);
        if (start && repo.remoteRefs.has(start)) setUpstream(repo, name, start, io);
        repo.previous = previous;
        repo.head = { branch: name, detached: null };
        logRef(repo, target, `checkout: moving from ${previous} to ${name}`);
        io.out(`Switched to a new branch '${name}'`);
        return 0;
    }

    function detach(repo, hash, label, io) {
        busy(repo);
        const from = headHash(repo);
        moveTo(repo, hash);
        const wasDetached = repo.head.branch === null;
        if (wasDetached && from && from !== hash) orphanWarning(repo, from, hash, io);
        repo.previous = headLabel(repo);
        logRef(repo, hash, `checkout: moving from ${repo.previous} to ${label}`);
        repo.head = { branch: null, detached: hash };
        if (!wasDetached) {
            io.out(
                `Note: switching to '${label}'.`,
                '',
                "You are in 'detached HEAD' state. You can look around, make experimental",
                'changes and commit them, and you can discard any commits you make in this',
                'state without impacting any branches by switching back to a branch.',
                '',
                'If you want to create a new branch to retain commits you create, you may',
                'do so (now or later) by using -c with the switch command. Example:',
                '',
                '  git switch -c <new-branch-name>',
                '',
                'Or undo this operation with:',
                '',
                '  git switch -',
                '',
            );
        }
        io.out(`HEAD is now at ${short(hash)} ${subject(commits.get(hash))}`);
        return 0;
    }

    /** Restores working files (and, from a commit, the index too), as git checkout -- paths does. */
    function restoreFrom(repo, specs, sourceHash, { staged = false, worktree: toWork = true, command = 'restore' } = {}) {
        const source = sourceHash !== null ? treeOf(sourceHash) : null;
        const head = treeOf(headHash(repo));
        const tests = pathspecs(repo, specs);
        const candidates = new Set([...repo.index.keys(), ...repo.conflicts.keys(), ...(source ? source.keys() : []), ...(staged ? head.keys() : [])]);
        for (const test of tests) {
            if (![...candidates].some((path) => test.test(path))) {
                throw new ShellError(`error: pathspec '${test.spec}' did not match any file(s) known to git`, 1);
            }
        }
        for (const path of sorted(candidates)) {
            if (!tests.some((test) => test.test(path))) continue;
            if (repo.conflicts.has(path) && !source && !staged) throw new ShellError(`error: path '${shown(repo, path)}' is unmerged`, 1);
            if (staged) stage(repo, path, (source ?? head).get(path));
            if (toWork) {
                const value = source ? source.get(path) : repo.index.get(path);
                if (value === undefined && !source && !staged) continue;
                if (command === 'checkout' && source) stage(repo, path, value);
                setWork(repo, path, value);
            }
        }
        return 0;
    }

    function unstagedList(repo) {
        const s = statusOf(repo);
        return s.unstaged.length ? ['Unstaged changes after reset:', ...s.unstaged.map((c) => `${c.status}\t${shown(repo, c.path)}`)] : [];
    }

    function mergeMessage(repo, name) {
        const what = repo.heads.has(name)
            ? `branch '${name}'`
            : repo.remoteRefs.has(name)
              ? `remote-tracking branch '${name}'`
              : repo.tags.has(name)
                ? `tag '${name}'`
                : `commit '${name}'`;
        const into = repo.head.branch && !['main', 'master'].includes(repo.head.branch) ? ` into ${repo.head.branch}` : '';
        return `Merge ${what}${into}`;
    }

    /** Merges commit `theirs` into HEAD: fast-forward, or a three-way merge with a merge commit. */
    function mergeInto(repo, theirs, label, io, { noFf = false, ffOnly = false, squash = false, noCommit = false, message = null, reflogPrefix = 'merge' } = {}) {
        const head = headHash(repo);
        if (!head) {
            moveTo(repo, theirs, 'merge');
            setHead(repo, theirs, `${reflogPrefix} ${label}: Fast-forward`);
            return 0;
        }
        if (isAncestor(theirs, head)) {
            io.out('Already up to date.');
            return 0;
        }
        if (isAncestor(head, theirs) && !noFf && !squash) {
            moveTo(repo, theirs, 'merge');
            repo.origHead = head;
            setHead(repo, theirs, `${reflogPrefix} ${label}: Fast-forward`);
            io.out(`Updating ${short(head)}..${short(theirs)}`, 'Fast-forward', ...statLines(changes(treeOf(head), treeOf(theirs))));
            return 0;
        }
        if (ffOnly) throw fatal('Not possible to fast-forward, aborting.');
        const base = mergeBase(head, theirs);
        const result = mergeTrees(treeOf(base), treeOf(head), treeOf(theirs), 'HEAD', label);
        const touched = checkCanMerge(repo, result, 'merge');
        applyMerge(repo, result, touched);
        io.out(...result.messages);
        const text = message ?? mergeMessage(repo, label);
        repo.origHead = head;
        if (squash) {
            io.out('Squash commit -- not updating HEAD');
            if (result.conflicts.size) {
                io.out('Automatic merge failed; fix conflicts and then commit the result.');
                return 1;
            }
            return 0;
        }
        repo.merge = { theirs, message: text, label };
        if (result.conflicts.size) {
            io.out('Automatic merge failed; fix conflicts and then commit the result.');
            return 1;
        }
        if (noCommit) {
            io.out('Automatic merge went well; stopped before committing as requested');
            return 0;
        }
        const hash = createCommit(repo, { tree: new Map(repo.index), parents: [head, theirs], message: text });
        repo.merge = null;
        setHead(repo, hash, `${reflogPrefix} ${label}: Merge made by the 'ort' strategy.`);
        io.out("Merge made by the 'ort' strategy.", ...statLines(changes(treeOf(head), treeOf(hash))));
        return 0;
    }

    function patchId(hash) {
        const commit = commits.get(hash);
        return changes(treeOf(commit.parents[0] ?? null), commit.tree)
            .map((change) => `${change.path}\n${diffOps(splitLines(change.before ?? ''), splitLines(change.after ?? '')).filter((op) => op.op !== ' ').length}\n${change.after ?? ''}\n${change.before ?? ''}`)
            .join('\0');
    }

    function rebaseOnto(repo, label, upstream, onto, io) {
        const head = headHash(repo);
        if (!head) throw fatal('your current branch has no commits yet');
        const s = statusOf(repo);
        if (s.staged.length) throw new ShellError('error: cannot rebase: Your index contains uncommitted changes.\nerror: Please commit or stash them.', 1);
        if (s.unstaged.length) throw new ShellError('error: cannot rebase: You have unstaged changes.\nerror: Please commit or stash them.', 1);
        const branch = repo.head.branch;
        const exclude = ancestors(upstream);
        const mine = [...ancestors(head)].filter((h) => !exclude.has(h) && commits.get(h).parents.length <= 1).sort((a, b) => -byTime(a, b));
        if (onto === upstream && isAncestor(upstream, head)) {
            io.out(`Current branch ${branch ?? 'HEAD'} is up to date.`);
            return 0;
        }
        const theirs = new Set([...ancestors(onto)].filter((h) => !ancestors(head).has(h)).map(patchId));
        const todo = [];
        for (const hash of mine) {
            if (theirs.has(patchId(hash))) {
                io.err(`warning: skipped previously applied commit ${short(hash)}`, 'hint: use --reapply-cherry-picks to include skipped commits');
            } else todo.push(hash);
        }
        repo.rebase = { branch, onto, origHead: head, todo, current: null, stoppedAt: null, label };
        moveTo(repo, onto, 'rebase');
        repo.head = { branch: null, detached: onto };
        logRef(repo, onto, `rebase (start): checkout ${label}`);
        return rebaseSteps(repo, io);
    }

    function rebaseSteps(repo, io) {
        const state = repo.rebase;
        while (state.todo.length) {
            const hash = state.todo.shift();
            const commit = commits.get(hash);
            const head = headHash(repo);
            const result = mergeTrees(treeOf(commit.parents[0] ?? null), treeOf(head), commit.tree, 'HEAD', `${short(hash)} (${subject(commit)})`);
            const touched = sorted(new Set([...result.work.keys(), ...treeOf(head).keys()])).filter((path) => result.work.get(path) !== treeOf(head).get(path) || result.conflicts.has(path));
            applyMerge(repo, result, touched);
            if (result.conflicts.size) {
                state.current = hash;
                state.stoppedAt = head;
                io.out(...result.messages);
                io.err(
                    `error: could not apply ${short(hash)}... ${subject(commit)}`,
                    'hint: Resolve all conflicts manually, mark them as resolved with',
                    'hint: "git add/rm <conflicted_files>", then run "git rebase --continue".',
                    'hint: You can instead skip this commit: run "git rebase --skip".',
                    'hint: To abort and get back to the state before "git rebase", run "git rebase --abort".',
                );
                io.out(`Could not apply ${short(hash)}... ${subject(commit)}`);
                return 1;
            }
            if (sameTree(repo.index, treeOf(head))) continue;
            const created = createCommit(repo, { tree: new Map(repo.index), parents: [head], message: commit.message, author: commit.author });
            repo.head.detached = created;
            logRef(repo, created, `rebase (pick): ${subject(commit)}`);
        }
        const final = headHash(repo);
        if (state.branch) {
            repo.heads.set(state.branch, final);
            repo.head = { branch: state.branch, detached: null };
            logRef(repo, final, `rebase (finish): returning to refs/heads/${state.branch}`);
        }
        repo.origHead = state.origHead;
        repo.rebase = null;
        io.out(`Successfully rebased and updated ${state.branch ? `refs/heads/${state.branch}` : 'detached HEAD'}.`);
        return 0;
    }

    /** Applies one commit (cherry-pick) or its opposite (revert) on top of HEAD. */
    function pick(repo, kind, hash, io, { noCommit = false, mainline = null } = {}) {
        const commit = commits.get(hash);
        if (commit.parents.length > 1 && !mainline) {
            throw new ShellError(`error: commit ${hash} is a merge but no -m option was given.\nfatal: ${kind} failed`, 128);
        }
        const parent = commit.parents[(mainline ?? 1) - 1] ?? null;
        const head = headHash(repo);
        const [base, theirs] = kind === 'revert' ? [commit.tree, treeOf(parent)] : [treeOf(parent), commit.tree];
        const label = kind === 'revert' ? `parent of ${short(hash)} (${subject(commit)})` : `${short(hash)} (${subject(commit)})`;
        const result = mergeTrees(base, treeOf(head), theirs, 'HEAD', label);
        const touched = checkCanMerge(repo, result, kind);
        applyMerge(repo, result, touched);
        const message = kind === 'revert' ? `Revert "${subject(commit)}"\n\nThis reverts commit ${hash}.` : commit.message;
        if (result.conflicts.size) {
            repo.sequence = { kind, commit: hash, message, author: kind === 'cherry-pick' ? commit.author : null };
            io.out(...result.messages);
            io.err(
                `error: could not ${kind === 'revert' ? 'revert' : 'apply'} ${short(hash)}... ${subject(commit)}`,
                'hint: After resolving the conflicts, mark them with',
                'hint: "git add/rm <pathspec>", then run',
                `hint: "git ${kind} --continue".`,
                `hint: You can instead skip this commit with "git ${kind} --skip".`,
                `hint: To abort and get back to the state before "git ${kind}",`,
                `hint: run "git ${kind} --abort".`,
            );
            return 1;
        }
        if (noCommit) return 0;
        if (sameTree(repo.index, treeOf(head))) {
            io.err(
                `The previous ${kind} is now empty, possibly due to conflict resolution.`,
                'If you wish to commit it anyway, use:',
                '',
                '    git commit --allow-empty',
            );
            return 1;
        }
        io.out(...result.messages.filter((m) => m.startsWith('Auto-merging')));
        const created = createCommit(repo, { tree: new Map(repo.index), parents: head ? [head] : [], message, author: kind === 'cherry-pick' ? commit.author : null });
        setHead(repo, created, `${kind}: ${subject(commits.get(created))}`);
        io.out(...commitReport(repo, created, { showDate: kind === 'cherry-pick' }));
        return 0;
    }

    function sequence(repo, kind, args, io) {
        const { flags, positional } = parseArgs(kind, args, { '--continue': 'flag', '--abort': 'flag', '--skip': 'flag', '--no-edit': 'flag', '-n|--no-commit': 'flag', '-m|--mainline': 'value', '-x': 'flag' });
        const state = repo.sequence;
        if (flags.abort || flags.skip || flags.continue) {
            if (!state || state.kind !== kind) throw fatal(`no ${kind} in progress`);
            if (flags.abort) {
                resetHard(repo, headHash(repo));
                repo.sequence = null;
                return 0;
            }
            if (flags.skip) {
                resetHard(repo, headHash(repo));
                repo.sequence = null;
                return runList(state.todo ?? []);
            }
            if (repo.conflicts.size) {
                throw new ShellError(`error: Committing is not possible because you have unmerged files.\nhint: Fix them up in the work tree, and then use 'git add/rm <file>'\nhint: as appropriate to mark resolution and make a commit.\nfatal: Exiting because of an unresolved conflict.`, 128);
            }
            const head = headHash(repo);
            if (!sameTree(repo.index, treeOf(head))) {
                const created = createCommit(repo, { tree: new Map(repo.index), parents: [head], message: state.message, author: state.author });
                setHead(repo, created, `${kind}: ${subject(commits.get(created))}`);
                io.out(...commitReport(repo, created));
            }
            repo.sequence = null;
            return runList(state.todo ?? []);
        }
        if (repo.sequence) throw new ShellError(`error: ${repo.sequence.kind} is already in progress\nhint: try "git ${repo.sequence.kind} (--continue | --abort | --skip)"\nfatal: ${kind} failed`, 128);
        if (!positional.length) throw fatal('empty commit set passed');
        const hashes = [];
        for (const spec of positional) {
            if (spec.includes('..')) {
                const [from, to] = spec.split('..');
                const excluded = ancestors(revOrFatal(repo, from || 'HEAD'));
                hashes.push(...[...ancestors(revOrFatal(repo, to || 'HEAD'))].filter((h) => !excluded.has(h)).sort((a, b) => -byTime(a, b)));
            } else hashes.push(revOrFatal(repo, spec));
        }
        if (kind === 'revert' && positional.every((spec) => !spec.includes('..'))) hashes.sort(byTime);
        return runList(hashes);

        function runList(list) {
            for (let i = 0; i < list.length; i++) {
                const code = pick(repo, kind, list[i], io, { noCommit: Boolean(flags['no-commit']), mainline: flags.mainline ? Number(flags.mainline) : null });
                if (code !== 0) {
                    if (repo.sequence) repo.sequence.todo = list.slice(i + 1);
                    return code;
                }
            }
            return 0;
        }
    }

    function repoForRemote(repo, name, required = true) {
        const remote = repo.remotes.get(name);
        const other = remote ? repos.get(resolve(remote.url, repo.root)) : null;
        if (!other && required) {
            throw fatal(`'${remote?.url ?? name}' does not appear to be a git repository\nfatal: Could not read from remote repository.\n\nPlease make sure you have the correct access rights\nand the repository exists.`);
        }
        return other ?? null;
    }

    function fetch(repo, name, io, { prune = false } = {}) {
        const other = repoForRemote(repo, name);
        const url = repo.remotes.get(name).url;
        const lines = [];
        for (const branch of sorted(other.heads.keys())) {
            const hash = other.heads.get(branch);
            const key = `${name}/${branch}`;
            const old = repo.remoteRefs.get(key);
            if (old === hash) continue;
            if (old === undefined) lines.push(['*', '[new branch]', branch, key, '']);
            else if (isAncestor(old, hash)) lines.push([' ', `${short(old)}..${short(hash)}`, branch, key, '']);
            else lines.push(['+', `${short(old)}...${short(hash)}`, branch, key, '  (forced update)']);
            repo.remoteRefs.set(key, hash);
        }
        if (prune) {
            for (const key of sorted(repo.remoteRefs.keys())) {
                if (key.startsWith(`${name}/`) && !other.heads.has(key.slice(name.length + 1))) {
                    repo.remoteRefs.delete(key);
                    lines.push(['-', '[deleted]', '(none)', key, '']);
                }
            }
        }
        for (const tag of sorted(other.tags.keys())) {
            if (!repo.tags.has(tag)) {
                repo.tags.set(tag, other.tags.get(tag));
                lines.push(['*', '[new tag]', tag, tag, '']);
            }
        }
        const upstream = repo.head.branch !== null ? repo.upstream.get(repo.head.branch) : null;
        repo.fetchHead = upstream && upstream.remote === name ? (other.heads.get(upstream.branch) ?? null) : (other.heads.get(other.head.branch) ?? null);
        if (lines.length) {
            const width = Math.max(...lines.map((line) => line[2].length));
            io.out(`From ${url}`, ...lines.map(([flag, summary, from, to, note]) => ` ${flag} ${summary.padEnd(17)} ${from.padEnd(width)} -> ${to}${note}`));
        }
        return other;
    }

    function checkBare(repo) {
        if (repo.bare) throw fatal('this operation must be run in a work tree');
    }

    const gitCommands = {
        init(args, io) {
            const { flags, positional } = parseArgs('init', args, { '--bare': 'flag', '-b|--initial-branch': 'value', '-q|--quiet': 'flag' });
            const root = resolve(positional[0] ?? '.');
            if (files.has(root)) throw fatal(`cannot mkdir ${positional[0]}: File exists`);
            makeDirs(root);
            if (repos.has(root)) {
                if (!flags.quiet) io.out(`Reinitialized existing Git repository in ${gitDir(repos.get(root))}`);
                return 0;
            }
            const branch = flags['initial-branch'] ?? config(null, 'init.defaultbranch') ?? 'main';
            validBranchName(branch);
            repos.set(root, newRepo(root, Boolean(flags.bare), branch));
            if (!flags.quiet) io.out(`Initialized empty Git repository in ${gitDir(repos.get(root))}`);
            return 0;
        },

        status(args, io) {
            const repo = needWorkTree();
            const { flags } = parseArgs('status', args, { '-s|--short': 'flag', '-b|--branch': 'flag', '--porcelain': 'flag', '--long': 'flag' });
            const lines = flags.short || flags.porcelain ? shortStatus(repo, flags.branch) : longStatus(repo);
            if (lines.length) io.out(...lines);
            return 0;
        },

        add(args, io) {
            const repo = needWorkTree();
            const { flags, positional, paths } = parseArgs('add', args, { '-A|--all': 'flag', '-u|--update': 'flag', '-f|--force': 'flag', '-n|--dry-run': 'flag', '-v|--verbose': 'flag' });
            const specs = [...positional, ...(paths ?? [])];
            if (!specs.length && !flags.all && !flags.update) {
                io.out('Nothing specified, nothing added.', "hint: Maybe you wanted to say 'git add .'?");
                return 0;
            }
            const work = worktree(repo);
            const rules = ignoreRules(repo);
            const tests = specs.length ? pathspecs(repo, specs) : [{ spec: null, exact: null, test: () => true }];
            const plan = [];
            const ignored = [];
            const matched = new Set();
            for (const path of sorted(new Set([...work.keys(), ...repo.index.keys(), ...repo.conflicts.keys()]))) {
                const hits = tests.filter((test) => test.test(path));
                if (!hits.length) continue;
                const tracked = repo.index.has(path) || repo.conflicts.has(path);
                if (!tracked) {
                    if (flags.update || !work.has(path)) continue;
                    if (!flags.force && ignoredBy(rules, path)) {
                        if (hits.some((test) => test.exact === path)) {
                            ignored.push(path);
                            hits.forEach((test) => matched.add(test.spec));
                        }
                        continue;
                    }
                }
                hits.forEach((test) => matched.add(test.spec));
                if (work.get(path) !== repo.index.get(path) || repo.conflicts.has(path)) plan.push(path);
            }
            for (const test of tests) {
                if (test.spec !== null && !matched.has(test.spec)) throw fatal(`pathspec '${test.spec}' did not match any files`);
            }
            for (const path of plan) {
                if (flags.verbose || flags['dry-run']) io.out(work.has(path) ? `add '${shown(repo, path)}'` : `remove '${shown(repo, path)}'`);
                if (!flags['dry-run']) stage(repo, path, work.get(path));
            }
            if (ignored.length) {
                io.err('The following paths are ignored by one of your .gitignore files:', ...ignored.map((p) => shown(repo, p)), 'hint: Use -f if you really want to add them.');
                return 1;
            }
            return 0;
        },

        rm(args, io) {
            const repo = needWorkTree();
            const { flags, positional, paths } = parseArgs('rm', args, { '--cached': 'flag', '-r': 'flag', '-f|--force': 'flag', '-q|--quiet': 'flag' });
            const specs = [...positional, ...(paths ?? [])];
            if (!specs.length) throw fatal('No pathspec was given. Which files should I remove?');
            const head = treeOf(headHash(repo));
            const work = worktree(repo);
            const chosen = [];
            for (const test of pathspecs(repo, specs)) {
                const hits = sorted(repo.index.keys()).filter((path) => test.test(path));
                if (!hits.length) throw fatal(`pathspec '${test.spec}' did not match any files`);
                if (!flags.r && hits.some((path) => path !== test.exact) && test.exact !== null) throw fatal(`not removing '${test.spec}' recursively without -r`);
                chosen.push(...hits);
            }
            if (!flags.force) {
                for (const path of chosen) {
                    const stagedChange = repo.index.get(path) !== head.get(path);
                    const localChange = work.has(path) && work.get(path) !== repo.index.get(path);
                    let problem = null;
                    if (stagedChange && localChange) problem = 'has staged content different from both the\nfile and the HEAD';
                    else if (!flags.cached && localChange) problem = 'has local modifications';
                    else if (!flags.cached && stagedChange && head.has(path)) problem = 'has changes staged in the index';
                    if (problem) {
                        throw new ShellError(`error: the following file ${problem}:\n    ${shown(repo, path)}\n(use ${problem.startsWith('has staged content') ? '-f to force removal' : '--cached to keep the file, or -f to force removal'})`, 1);
                    }
                }
            }
            for (const path of new Set(chosen)) {
                repo.index.delete(path);
                if (!flags.cached) removeWork(repo, path);
                if (!flags.quiet) io.out(`rm '${shown(repo, path)}'`);
            }
            return 0;
        },

        mv(args, io) {
            const repo = needWorkTree();
            const { positional } = parseArgs('mv', args, { '-f|--force': 'flag', '-v|--verbose': 'flag' });
            if (positional.length !== 2) throw new ShellError('usage: git mv [<options>] <source>... <destination>', 129);
            const [fromSpec, toSpec] = positional;
            const from = toRepoPath(repo, fromSpec);
            let to = toRepoPath(repo, toSpec);
            if (dirs.has(workPath(repo, to))) to = to ? `${to}/${baseName(workPath(repo, from))}` : baseName(workPath(repo, from));
            const moving = sorted(repo.index.keys()).filter((path) => path === from || path.startsWith(`${from}/`));
            if (!moving.length) throw fatal(`not under version control, source=${fromSpec}, destination=${toSpec}`);
            if (files.has(workPath(repo, to))) throw fatal(`destination exists, source=${fromSpec}, destination=${toSpec}`);
            for (const path of moving) {
                const target = to + path.slice(from.length);
                repo.index.set(target, repo.index.get(path));
                repo.index.delete(path);
                const content = files.get(workPath(repo, path));
                if (content !== undefined) {
                    removeWork(repo, path);
                    setWork(repo, target, content);
                }
            }
            return 0;
        },

        commit(args, io) {
            const repo = needWorkTree();
            const { flags } = parseArgs('commit', args, { '-m|--message': 'list', '-a|--all': 'flag', '--amend': 'flag', '--no-edit': 'flag', '--allow-empty': 'flag', '-q|--quiet': 'flag', '-v|--verbose': 'flag' });
            if (repo.conflicts.size) {
                throw new ShellError(`error: Committing is not possible because you have unmerged files.\nhint: Fix them up in the work tree, and then use 'git add/rm <file>'\nhint: as appropriate to mark resolution and make a commit.\nfatal: Exiting because of an unresolved conflict.`, 128);
            }
            const head = headHash(repo);
            if (flags.amend && !head) throw fatal('You have nothing to amend.');
            if (flags.all) {
                const work = worktree(repo);
                for (const path of [...repo.index.keys()]) stage(repo, path, work.get(path));
            }
            let message = flags.message ? joinMessages(flags.message) : null;
            if (message === null) {
                if (flags.amend) message = commits.get(head).message;
                else if (repo.merge) message = repo.merge.message;
                else if (repo.sequence) message = repo.sequence.message;
                else if (repo.rebase?.current) message = commits.get(repo.rebase.current).message;
                else throw new ShellError('error: there\'s no text editor in this sandbox, so give the message with -m:\n    git commit -m "Describe your change"', 1);
            }
            if (message.trim() === '') throw new ShellError('Aborting commit due to empty commit message.', 1);
            const tree = new Map(repo.index);
            const parents = flags.amend ? [...commits.get(head).parents] : head ? [head] : [];
            if (repo.merge && !flags.amend) parents.push(repo.merge.theirs);
            const parentTree = treeOf(parents[0] ?? null);
            if (!flags['allow-empty'] && !flags.amend && !repo.merge && sameTree(tree, parentTree)) {
                io.out(...longStatus(repo));
                return 1;
            }
            const author = flags.amend ? commits.get(head).author : (repo.sequence?.author ?? (repo.rebase?.current ? commits.get(repo.rebase.current).author : null));
            const hash = createCommit(repo, { tree, parents, message, author });
            const kind = flags.amend ? 'commit (amend)' : !head ? 'commit (initial)' : repo.merge ? 'commit (merge)' : 'commit';
            setHead(repo, hash, `${kind}: ${subject(commits.get(hash))}`);
            repo.merge = null;
            repo.sequence = null;
            if (!flags.quiet) io.out(...commitReport(repo, hash, { showDate: Boolean(flags.amend) }));
            return 0;
        },

        log(args, io) {
            const repo = needRepo();
            const { flags, positional, paths } = parseArgs(
                'log',
                args,
                {
                    '--oneline': 'flag',
                    '--graph': 'flag',
                    '--all': 'flag',
                    '-n|--max-count': 'value',
                    '--decorate': 'flag',
                    '--no-decorate': 'flag',
                    '-p|--patch': 'flag',
                    '--stat': 'flag',
                    '--reverse': 'flag',
                    '--format|--pretty': 'value',
                    '--author': 'value',
                    '--grep': 'value',
                    '--first-parent': 'flag',
                    '--name-only': 'flag',
                    '--merges': 'flag',
                    '--no-merges': 'flag',
                },
                { numbers: true },
            );
            const filterPaths = [...(paths ?? [])];
            const include = [];
            const exclude = [];
            for (const spec of positional) {
                const symmetric = spec.split('...');
                const range = spec.split('..');
                if (symmetric.length === 2) {
                    const a = revOrFatal(repo, symmetric[0] || 'HEAD');
                    const b = revOrFatal(repo, symmetric[1] || 'HEAD');
                    include.push(a, b);
                    exclude.push(...ancestors(mergeBase(a, b)));
                } else if (range.length === 2) {
                    exclude.push(...ancestors(revOrFatal(repo, range[0] || 'HEAD')));
                    include.push(revOrFatal(repo, range[1] || 'HEAD'));
                } else if (spec.startsWith('^')) {
                    exclude.push(...ancestors(revOrFatal(repo, spec.slice(1))));
                } else {
                    const hash = resolveRev(repo, spec);
                    if (hash) include.push(hash);
                    else if (!repo.bare && [...worktree(repo).keys(), ...repo.index.keys()].some((path) => pathspecs(repo, [spec])[0].test(path))) filterPaths.push(spec);
                    else revOrFatal(repo, spec);
                }
            }
            if (flags.all) {
                include.push(...repo.heads.values(), ...repo.remoteRefs.values(), ...[...repo.tags.values()].map((tag) => tag.hash));
                if (headHash(repo)) include.push(headHash(repo));
            }
            if (!include.length) {
                if (!positional.some((spec) => spec.startsWith('^'))) include.push(revOrFatal(repo, 'HEAD'));
            }
            const excluded = new Set(exclude);
            let hashes = new Set();
            for (const tip of include) {
                if (flags['first-parent']) {
                    for (let h = tip; h; h = commits.get(h).parents[0] ?? null) hashes.add(h);
                } else for (const h of ancestors(tip)) hashes.add(h);
            }
            hashes = [...hashes].filter((h) => !excluded.has(h));
            const tests = filterPaths.length && !repo.bare ? pathspecs(repo, filterPaths) : null;
            const keep = (hash) => {
                const commit = commits.get(hash);
                if (flags.merges && commit.parents.length < 2) return false;
                if (flags['no-merges'] && commit.parents.length > 1) return false;
                if (flags.author && !new RegExp(flags.author, 'i').test(`${commit.author.name} <${commit.author.email}>`)) return false;
                if (flags.grep && !new RegExp(flags.grep, 'i').test(commit.message)) return false;
                if (tests) {
                    const changed = changes(treeOf(commit.parents[0] ?? null), commit.tree);
                    if (!changed.some((change) => tests.some((test) => test.test(change.path) || (change.from && test.test(change.from))))) return false;
                }
                return true;
            };
            let order = flags.graph ? topoOrder(hashes) : [...hashes].sort(byTime);
            order = order.filter(keep);
            const limit = flags.count ?? flags['max-count'];
            if (limit !== undefined) order = order.slice(0, Number(limit));
            if (flags.reverse) order.reverse();
            if (!order.length) return 0;
            let format = flags.format ?? null;
            let oneline = Boolean(flags.oneline);
            if (format === 'oneline') {
                format = '%H%d %s';
            } else if (format !== null) {
                format = format.replace(/^t?format:/, '');
                if (['medium', 'full', 'short'].includes(format)) format = null;
            }
            const decor = decorations(repo);
            const render = (hash) => {
                const lines = formatCommit(repo, hash, { oneline, format, decorate: !flags['no-decorate'], decor });
                const commit = commits.get(hash);
                const list = commit.parents.length > 1 ? [] : changes(treeOf(commit.parents[0] ?? null), commit.tree, tests ? (p) => tests.some((t) => t.test(p)) : null);
                if (flags['name-only'] && list.length) lines.push(...(oneline ? [] : ['']), ...list.map((c) => c.path));
                if (flags.stat && list.length) lines.push(...(oneline ? [] : ['']), ...diffstat(list), summaryLine(list));
                if (flags.patch && list.length) lines.push(...(oneline ? [] : ['']), ...list.map(patch).join('\n').split('\n'));
                return lines;
            };
            const separated = !oneline && format === null;
            if (flags.graph) {
                const lines = graph(order, (hash) => [...render(hash), ...(separated ? [''] : [])]);
                while (lines.length && lines[lines.length - 1] === '') lines.pop();
                io.out(...lines);
            } else {
                const lines = [];
                order.forEach((hash, i) => {
                    if (separated && i) lines.push('');
                    lines.push(...render(hash));
                });
                io.out(...lines);
            }
            return 0;
        },

        show(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('show', args, { '--stat': 'flag', '--oneline': 'flag', '--name-only': 'flag', '-s|--no-patch': 'flag', '--format|--pretty': 'value' });
            const specs = positional.length ? positional : ['HEAD'];
            const blocks = [];
            for (const spec of specs) {
                const colon = spec.indexOf(':');
                if (colon !== -1) {
                    const rev = spec.slice(0, colon) || 'HEAD';
                    const path = spec.slice(colon + 1).replace(/^\.\//, '');
                    const hash = revOrFatal(repo, rev);
                    const content = treeOf(hash).get(path);
                    if (content === undefined) throw fatal(`path '${path}' does not exist in '${rev}'`);
                    io.write(content);
                    continue;
                }
                const tag = repo.tags.get(spec);
                const lines = [];
                if (tag?.annotation) {
                    const { tagger, message } = tag.annotation;
                    lines.push(`tag ${spec}`, `Tagger: ${tagger.name} <${tagger.email}>`, `Date:   ${formatDate(tagger.time)}`, '', ...message.split('\n'), '');
                }
                const hash = revOrFatal(repo, spec);
                const commit = commits.get(hash);
                const format = flags.format ? flags.format.replace(/^t?format:/, '') : null;
                lines.push(...formatCommit(repo, hash, { oneline: Boolean(flags.oneline), format }));
                const list = commit.parents.length > 1 ? [] : changes(treeOf(commit.parents[0] ?? null), commit.tree);
                if (!flags['no-patch'] && list.length) {
                    if (flags['name-only']) lines.push(...(flags.oneline ? [] : ['']), ...list.map((c) => c.path));
                    else if (flags.stat) lines.push(...(flags.oneline ? [] : ['']), ...diffstat(list), summaryLine(list));
                    else lines.push(...(flags.oneline ? [] : ['']), ...list.map(patch).join('\n').split('\n'));
                }
                blocks.push(lines);
            }
            if (blocks.length) io.out(...blocks.flatMap((block, i) => (i ? ['', ...block] : block)));
            return 0;
        },

        diff(args, io) {
            const repo = needRepo();
            const { flags, positional, paths } = parseArgs('diff', args, { '--staged|--cached': 'flag', '--stat': 'flag', '--name-only': 'flag', '--name-status': 'flag', '--quiet|--exit-code': 'flag' });
            const filterSpecs = [...(paths ?? [])];
            const revs = [];
            for (const spec of positional) {
                if (spec.includes('..') || resolveRev(repo, spec)) revs.push(spec);
                else if (!repo.bare && [...worktree(repo).keys(), ...repo.index.keys()].some((path) => pathspecs(repo, [spec])[0].test(path))) filterSpecs.push(spec);
                else revOrFatal(repo, spec);
            }
            const tracked = () => {
                checkBare(repo);
                const work = worktree(repo);
                return new Map([...repo.index.keys(), ...repo.conflicts.keys()].filter((p) => work.has(p)).map((p) => [p, work.get(p)]));
            };
            let before;
            let after;
            if (revs.length === 1 && revs[0].includes('...')) {
                const [a, b] = revs[0].split('...');
                const ha = revOrFatal(repo, a || 'HEAD');
                const hb = revOrFatal(repo, b || 'HEAD');
                before = treeOf(mergeBase(ha, hb));
                after = treeOf(hb);
            } else if (revs.length === 1 && revs[0].includes('..')) {
                const [a, b] = revs[0].split('..');
                before = treeOf(revOrFatal(repo, a || 'HEAD'));
                after = treeOf(revOrFatal(repo, b || 'HEAD'));
            } else if (revs.length === 2) {
                before = treeOf(revOrFatal(repo, revs[0]));
                after = treeOf(revOrFatal(repo, revs[1]));
            } else if (revs.length === 1) {
                before = treeOf(revOrFatal(repo, revs[0]));
                after = flags.staged ? repo.index : tracked();
            } else if (flags.staged) {
                before = treeOf(headHash(repo));
                after = repo.index;
            } else {
                before = new Map(repo.index);
                for (const path of repo.conflicts.keys()) if (!before.has(path)) before.set(path, '');
                after = tracked();
            }
            const tests = filterSpecs.length ? pathspecs(repo, filterSpecs) : null;
            const list = changes(before, after, tests ? (path) => tests.some((test) => test.test(path)) : null);
            if (flags.quiet) return list.length ? 1 : 0;
            if (!list.length) return 0;
            if (flags['name-only']) io.out(...list.map((c) => c.path));
            else if (flags['name-status']) io.out(...list.map((c) => (c.status === 'R' ? `R100\t${c.from}\t${c.path}` : `${c.status}\t${c.path}`)));
            else if (flags.stat) io.out(...diffstat(list), summaryLine(list));
            else io.out(...list.map(patch));
            return 0;
        },

        restore(args, io) {
            const repo = needWorkTree();
            const { flags, positional, paths } = parseArgs('restore', args, { '-S|--staged': 'flag', '-W|--worktree': 'flag', '-s|--source': 'value', '-q|--quiet': 'flag' });
            const specs = [...positional, ...(paths ?? [])];
            if (!specs.length) throw fatal('you must specify path(s) to restore');
            const staged = Boolean(flags.staged);
            const toWork = Boolean(flags.worktree) || !staged;
            const source = flags.source ? revOrFatal(repo, flags.source) : null;
            return restoreFrom(repo, specs, source, { staged, worktree: toWork });
        },

        checkout(args, io) {
            const repo = needWorkTree();
            const { flags, positional, paths } = parseArgs('checkout', args, { '-b': 'value', '-B': 'value', '--detach': 'flag', '-f|--force': 'flag', '-q|--quiet': 'flag', '-t|--track': 'flag' });
            if (flags.quiet) io = quietly(io);
            if (paths !== null) {
                if (positional.length > 1) throw fatal('only one reference expected');
                return restoreFrom(repo, paths, positional[0] ? revOrFatal(repo, positional[0]) : null, { command: 'checkout' });
            }
            if (flags.b || flags.B) return createBranch(repo, flags.b ?? flags.B, positional[0] ?? null, io, Boolean(flags.B));
            if (!positional.length) {
                io.out(...trackingLines(repo));
                return 0;
            }
            const [target, ...rest] = positional;
            if (!rest.length && !flags.detach && (target === '-' || repo.heads.has(target) || (!resolveRev(repo, target) && remoteBranchFor(repo, target)))) {
                return switchBranch(repo, target, io, 'checkout');
            }
            const hash = resolveRev(repo, target);
            if (hash && !rest.length) return detach(repo, hash, target, io);
            if (hash) return restoreFrom(repo, rest, hash, { command: 'checkout' });
            return restoreFrom(repo, positional, null, { command: 'checkout' });
        },

        switch(args, io) {
            const repo = needWorkTree();
            const { flags, positional } = parseArgs('switch', args, { '-c|--create': 'value', '-C|--force-create': 'value', '-d|--detach': 'flag', '-f|--force|--discard-changes': 'flag', '-t|--track': 'flag', '-q|--quiet': 'flag' });
            if (flags.quiet) io = quietly(io);
            if (flags.create || flags['force-create']) return createBranch(repo, flags.create ?? flags['force-create'], positional[0] ?? null, io, Boolean(flags['force-create']));
            if (flags.detach) return detach(repo, revOrFatal(repo, positional[0] ?? 'HEAD'), positional[0] ?? 'HEAD', io);
            const [name] = positional;
            if (!name) throw fatal('missing branch or commit argument');
            if (name !== '-' && !repo.heads.has(name) && !remoteBranchFor(repo, name) && resolveRev(repo, name)) {
                throw fatal(`a branch is expected, got ${repo.tags.has(name) ? 'tag' : 'commit'} '${name}'\nhint: If you want to detach HEAD at the commit, try again with the --detach option.`);
            }
            return switchBranch(repo, name, io, 'switch');
        },

        branch(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('branch', args, {
                '-d|--delete': 'flag',
                '-D': 'flag',
                '-m|--move': 'flag',
                '-M': 'flag',
                '-a|--all': 'flag',
                '-r|--remotes': 'flag',
                '-v|--verbose': 'count',
                '--show-current': 'flag',
                '-u|--set-upstream-to': 'value',
                '--unset-upstream': 'flag',
                '-f|--force': 'flag',
                '--merged': 'flag',
                '--no-merged': 'flag',
                '-l|--list': 'flag',
            });
            const head = headHash(repo);
            if (flags['show-current']) {
                if (repo.head.branch !== null) io.out(repo.head.branch);
                return 0;
            }
            if (flags.delete || flags.D) {
                if (!positional.length) throw fatal('branch name required');
                let code = 0;
                for (const name of positional) {
                    if (flags.remotes) {
                        if (!repo.remoteRefs.has(name)) {
                            io.err(`error: remote-tracking branch '${name}' not found.`);
                            code = 1;
                            continue;
                        }
                        io.out(`Deleted remote-tracking branch ${name} (was ${short(repo.remoteRefs.get(name))}).`);
                        repo.remoteRefs.delete(name);
                        continue;
                    }
                    if (!repo.heads.has(name)) {
                        io.err(`error: branch '${name}' not found.`);
                        code = 1;
                        continue;
                    }
                    if (repo.head.branch === name) {
                        io.err(`error: Cannot delete branch '${name}' checked out at '${repo.root}'`);
                        code = 1;
                        continue;
                    }
                    const tip = repo.heads.get(name);
                    const upstream = repo.upstream.get(name);
                    const upstreamTip = upstream ? repo.remoteRefs.get(`${upstream.remote}/${upstream.branch}`) : null;
                    const merged = (head && isAncestor(tip, head)) || (upstreamTip && isAncestor(tip, upstreamTip));
                    if (!flags.D && !flags.force && !merged) {
                        io.err(`error: the branch '${name}' is not fully merged`, `hint: If you are sure you want to delete it, run 'git branch -D ${name}'`);
                        code = 1;
                        continue;
                    }
                    repo.heads.delete(name);
                    repo.upstream.delete(name);
                    io.out(`Deleted branch ${name} (was ${short(tip)}).`);
                }
                return code;
            }
            if (flags.move || flags.M) {
                const [oldName, newName] = positional.length === 1 ? [repo.head.branch, positional[0]] : positional;
                if (!oldName || !newName) throw fatal('branch name required');
                validBranchName(newName);
                if (repo.heads.has(newName) && !flags.M) throw fatal(`a branch named '${newName}' already exists`);
                if (!repo.heads.has(oldName) && repo.head.branch !== oldName) throw fatal(`no branch named '${oldName}'`);
                if (repo.heads.has(oldName)) {
                    repo.heads.set(newName, repo.heads.get(oldName));
                    repo.heads.delete(oldName);
                }
                if (repo.upstream.has(oldName)) {
                    repo.upstream.set(newName, repo.upstream.get(oldName));
                    repo.upstream.delete(oldName);
                }
                if (repo.head.branch === oldName) repo.head.branch = newName;
                return 0;
            }
            if (flags['set-upstream-to'] !== undefined) {
                const ref = flags['set-upstream-to'];
                const branch = positional[0] ?? repo.head.branch;
                if (!repo.remoteRefs.has(ref)) throw fatal(`the requested upstream branch '${ref}' does not exist`);
                setUpstream(repo, branch, ref, io);
                return 0;
            }
            if (flags['unset-upstream']) {
                repo.upstream.delete(positional[0] ?? repo.head.branch);
                return 0;
            }
            if (positional.length && !flags.list) {
                const [name, start] = positional;
                validBranchName(name);
                if (repo.heads.has(name) && !flags.force) throw fatal(`a branch named '${name}' already exists`);
                const target = start ? resolveRev(repo, start) : head;
                if (!target) throw fatal(`not a valid object name: '${start ?? repo.head.branch}'`);
                if (repo.head.branch === name && flags.force) throw fatal(`cannot force update the branch '${name}' used by worktree at '${repo.root}'`);
                repo.heads.set(name, target);
                if (start && repo.remoteRefs.has(start)) setUpstream(repo, name, start, io);
                return 0;
            }
            const rows = [];
            if (!flags.remotes) {
                if (repo.head.branch === null && head) rows.push({ current: true, name: `(HEAD detached at ${short(head)})`, hash: head, branch: null });
                for (const name of sorted(repo.heads.keys())) rows.push({ current: name === repo.head.branch, name, hash: repo.heads.get(name), branch: name });
            }
            if (flags.all || flags.remotes) {
                for (const name of sorted(repo.remoteRefs.keys())) rows.push({ current: false, name: flags.all ? `remotes/${name}` : name, hash: repo.remoteRefs.get(name), branch: null });
            }
            const filtered = rows.filter((row) => (!flags.merged || (head && isAncestor(row.hash, head))) && (!flags['no-merged'] || !(head && isAncestor(row.hash, head))));
            const width = Math.max(0, ...filtered.map((row) => row.name.length));
            io.out(
                ...filtered.map((row) => {
                    const mark = row.current ? '* ' : '  ';
                    if (!flags.verbose) return `${mark}${row.name}`;
                    const info = row.branch ? tracking(repo, row.branch) : null;
                    let track = '';
                    if (info) {
                        const parts = info.gone ? ['gone'] : [info.ahead && `ahead ${info.ahead}`, info.behind && `behind ${info.behind}`].filter(Boolean);
                        if (flags.verbose > 1) track = `[${info.name}${parts.length ? `: ${parts.join(', ')}` : ''}] `;
                        else if (parts.length) track = `[${parts.join(', ')}] `;
                    }
                    return `${mark}${row.name.padEnd(width)} ${short(row.hash)} ${track}${subject(commits.get(row.hash))}`;
                }),
            );
            return 0;
        },

        merge(args, io) {
            const repo = needWorkTree();
            const { flags, positional } = parseArgs('merge', args, { '--no-ff': 'flag', '--ff-only': 'flag', '--ff': 'flag', '-m|--message': 'list', '--no-edit': 'flag', '--squash': 'flag', '--abort': 'flag', '--continue': 'flag', '--no-commit': 'flag', '-q|--quiet': 'flag' });
            if (flags.quiet) io = quietly(io);
            if (flags.abort) {
                if (!repo.merge) throw fatal('There is no merge to abort (MERGE_HEAD missing).');
                resetHard(repo, headHash(repo));
                repo.merge = null;
                return 0;
            }
            if (flags.continue) {
                if (!repo.merge) throw fatal('There is no merge in progress (MERGE_HEAD missing).');
                return gitCommands.commit([], io);
            }
            if (repo.merge || repo.conflicts.size) {
                if (repo.conflicts.size) throw new ShellError(`error: Merging is not possible because you have unmerged files.\nhint: Fix them up in the work tree, and then use 'git add/rm <file>'\nhint: as appropriate to mark resolution and make a commit.\nfatal: Exiting because of an unresolved conflict.`, 128);
                throw fatal('You have not concluded your merge (MERGE_HEAD exists).\nPlease, commit your changes before you merge.');
            }
            let [name] = positional;
            if (!name) {
                const upstream = repo.head.branch !== null ? repo.upstream.get(repo.head.branch) : null;
                if (!upstream) throw fatal('No remote for the current branch.');
                name = `${upstream.remote}/${upstream.branch}`;
            }
            const theirs = resolveRev(repo, name);
            if (!theirs) throw new ShellError(`merge: ${name} - not something we can merge`, 1);
            return mergeInto(repo, theirs, name, io, {
                noFf: Boolean(flags['no-ff']),
                ffOnly: Boolean(flags['ff-only']),
                squash: Boolean(flags.squash),
                noCommit: Boolean(flags['no-commit']),
                message: flags.message ? joinMessages(flags.message) : null,
            });
        },

        reset(args, io) {
            const repo = needWorkTree();
            const { flags, positional, paths } = parseArgs('reset', args, { '--soft': 'flag', '--mixed': 'flag', '--hard': 'flag', '-q|--quiet': 'flag' });
            let rev = 'HEAD';
            let specs = [...(paths ?? [])];
            if (positional.length) {
                if (resolveRev(repo, positional[0]) || (positional[0] === 'HEAD' && !headHash(repo))) {
                    rev = positional[0];
                    specs.push(...positional.slice(1));
                } else {
                    const tests = pathspecs(repo, [positional[0]]);
                    const known = [...repo.index.keys(), ...treeOf(headHash(repo)).keys()].some((p) => tests[0].test(p));
                    if (!known) revOrFatal(repo, positional[0]);
                    specs = [...positional, ...specs];
                }
            }
            if (specs.length) {
                if (flags.soft || flags.hard) throw fatal(`Cannot do ${flags.soft ? 'soft' : 'hard'} reset with paths.`);
                const source = treeOf(rev === 'HEAD' ? headHash(repo) : revOrFatal(repo, rev));
                const tests = pathspecs(repo, specs);
                for (const path of new Set([...repo.index.keys(), ...source.keys()])) {
                    if (tests.some((test) => test.test(path))) stage(repo, path, source.get(path));
                }
                if (!flags.quiet) io.out(...unstagedList(repo));
                return 0;
            }
            const old = headHash(repo);
            const target = old || rev !== 'HEAD' ? revOrFatal(repo, rev) : null;
            if (old) repo.origHead = old;
            if (target) setHead(repo, target, `reset: moving to ${rev}`);
            if (flags.hard) {
                const next = treeOf(target);
                for (const path of new Set([...repo.index.keys(), ...repo.conflicts.keys(), ...treeOf(old).keys(), ...next.keys()])) {
                    const value = next.get(path);
                    if (value === undefined) repo.index.delete(path);
                    else repo.index.set(path, value);
                    setWork(repo, path, value);
                }
                repo.conflicts = new Map();
            } else if (!flags.soft) {
                repo.index = new Map(treeOf(target));
                repo.conflicts = new Map();
            }
            if (!flags.soft) {
                repo.merge = null;
                repo.sequence = null;
            }
            if (flags.quiet) return 0;
            if (flags.hard && target) io.out(`HEAD is now at ${short(target)} ${subject(commits.get(target))}`);
            else if (!flags.soft && !flags.hard) io.out(...unstagedList(repo));
            return 0;
        },

        revert(args, io) {
            return sequence(needWorkTree(), 'revert', args, io);
        },

        'cherry-pick'(args, io) {
            return sequence(needWorkTree(), 'cherry-pick', args, io);
        },

        rebase(args, io) {
            const repo = needWorkTree();
            const { flags, positional } = parseArgs('rebase', args, { '--continue': 'flag', '--abort': 'flag', '--skip': 'flag', '--onto': 'value', '-i|--interactive': 'flag', '-q|--quiet': 'flag' });
            if (flags.interactive) throw unsupported("git rebase -i opens a text editor to list the commits, and this sandbox doesn't have one.");
            const state = repo.rebase;
            if (flags.abort) {
                if (!state) throw fatal('No rebase in progress?');
                repo.head = { branch: null, detached: headHash(repo) };
                resetHard(repo, state.origHead);
                repo.head = state.branch ? { branch: state.branch, detached: null } : { branch: null, detached: state.origHead };
                if (state.branch) repo.heads.set(state.branch, state.origHead);
                logRef(repo, state.origHead, `rebase (abort): returning to ${state.branch ? `refs/heads/${state.branch}` : short(state.origHead)}`);
                repo.rebase = null;
                return 0;
            }
            if (flags.continue || flags.skip) {
                if (!state) throw fatal('No rebase in progress?');
                if (state.current) {
                    if (flags.skip) {
                        resetHard(repo, headHash(repo));
                    } else {
                        if (repo.conflicts.size) {
                            throw new ShellError(`${sorted(repo.conflicts.keys()).map((p) => `${shown(repo, p)}: needs merge`).join('\n')}\nYou must edit all merge conflicts and then\nmark them as resolved using git add`, 1);
                        }
                        const head = headHash(repo);
                        if (head === state.stoppedAt && !sameTree(repo.index, treeOf(head))) {
                            const commit = commits.get(state.current);
                            const created = createCommit(repo, { tree: new Map(repo.index), parents: [head], message: commit.message, author: commit.author });
                            repo.head.detached = created;
                            logRef(repo, created, `rebase (continue): ${subject(commit)}`);
                        }
                    }
                    state.current = null;
                }
                return rebaseSteps(repo, io);
            }
            if (state) throw fatal('It looks like a rebase is already in progress.\nUse "git rebase (--continue | --skip | --abort)".');
            if (repo.merge) throw fatal('You have not concluded your merge (MERGE_HEAD exists).');
            let [spec, branch] = positional;
            if (branch) {
                const code = switchBranch(repo, branch, io, 'switch');
                if (code) return code;
            }
            if (!spec) {
                const upstream = repo.head.branch !== null ? repo.upstream.get(repo.head.branch) : null;
                if (!upstream) {
                    throw new ShellError(
                        'There is no tracking information for the current branch.\nPlease specify which branch you want to rebase against.\n\n    git rebase <branch>\n',
                        1,
                    );
                }
                spec = `${upstream.remote}/${upstream.branch}`;
            }
            const upstream = revOrFatal(repo, spec);
            const onto = flags.onto ? revOrFatal(repo, flags.onto) : upstream;
            return rebaseOnto(repo, flags.onto ?? spec, upstream, onto, io);
        },

        stash(args, io) {
            const repo = needWorkTree();
            const subcommands = ['push', 'save', 'list', 'show', 'pop', 'apply', 'drop', 'clear'];
            const sub = subcommands.includes(args[0]) ? args[0] : 'push';
            const rest = sub === args[0] ? args.slice(1) : args;
            const entryAt = (spec) => {
                if (!repo.stash.length) throw new ShellError('No stash entries found.', 1);
                const n = spec === undefined ? 0 : /^\d+$/.test(spec) ? Number(spec) : Number((/^stash@\{(\d+)\}$/.exec(spec) ?? [])[1]);
                if (!Number.isInteger(n) || !repo.stash[n]) throw new ShellError(`error: ${spec} is not a valid reference`, 1);
                return n;
            };
            if (sub === 'list') {
                if (repo.stash.length) io.out(...repo.stash.map((entry, i) => `stash@{${i}}: ${entry.label}`));
                return 0;
            }
            if (sub === 'clear') {
                repo.stash = [];
                return 0;
            }
            if (sub === 'drop') {
                const { positional } = parseArgs('stash drop', rest, { '-q|--quiet': 'flag' });
                const n = entryAt(positional[0]);
                const [entry] = repo.stash.splice(n, 1);
                io.out(`Dropped refs/stash@{${n}} (${entry.hash})`);
                return 0;
            }
            if (sub === 'show') {
                const { flags, positional } = parseArgs('stash show', rest, { '-p|--patch': 'flag', '--stat': 'flag' });
                const entry = repo.stash[entryAt(positional[0])];
                const list = changes(treeOf(entry.base), entry.work);
                if (list.length) io.out(...(flags.patch ? list.map(patch) : [...diffstat(list), summaryLine(list)]));
                return 0;
            }
            if (sub === 'pop' || sub === 'apply') {
                const { flags, positional } = parseArgs(`stash ${sub}`, rest, { '--index': 'flag', '-q|--quiet': 'flag' });
                const n = entryAt(positional[0]);
                const entry = repo.stash[n];
                const head = headHash(repo);
                const result = mergeTrees(treeOf(entry.base), treeOf(head), entry.work, 'Updated upstream', 'Stashed changes');
                const work = worktree(repo);
                for (const path of entry.untracked.keys()) {
                    if (work.has(path)) throw new ShellError(`${shown(repo, path)} already exists, no checkout\nerror: could not restore untracked files from stash`, 1);
                }
                const touched = checkCanMerge(repo, result, 'merge');
                applyMerge(repo, result, touched);
                for (const path of touched) {
                    if (result.conflicts.has(path)) continue;
                    const fromIndex = entry.index.get(path);
                    const isNew = !treeOf(entry.base).has(path);
                    if (flags.index || isNew) {
                        if (fromIndex !== undefined) repo.index.set(path, fromIndex);
                    } else {
                        const original = treeOf(head).get(path);
                        if (original === undefined) repo.index.delete(path);
                        else repo.index.set(path, original);
                    }
                }
                for (const [path, content] of entry.untracked) setWork(repo, path, content);
                const conflicted = result.conflicts.size > 0;
                io.out(...result.messages);
                if (conflicted) {
                    if (sub === 'pop') io.out('On branch ' + headLabel(repo), 'The stash entry is kept in case you need it again.');
                    return 1;
                }
                io.out(...longStatus(repo));
                if (sub === 'pop') {
                    repo.stash.splice(n, 1);
                    io.out(`Dropped refs/stash@{${n}} (${entry.hash})`);
                }
                return 0;
            }
            const { flags, positional } = parseArgs('stash', rest, { '-m|--message': 'value', '-u|--include-untracked': 'flag', '-q|--quiet': 'flag' });
            if (flags.quiet) io = quietly(io);
            if (repo.conflicts.size) throw new ShellError(`${sorted(repo.conflicts.keys()).map((p) => `${shown(repo, p)}: needs merge`).join('\n')}\nerror: could not save the current index state`, 1);
            const head = headHash(repo);
            if (!head) throw new ShellError('You do not have the initial commit yet', 1);
            const work = worktree(repo);
            const s = statusOf(repo);
            const untracked = new Map(flags['include-untracked'] ? s.untracked.map((path) => [path, work.get(path)]) : []);
            if (!s.staged.length && !s.unstaged.length && !untracked.size) {
                io.out('No local changes to save');
                return 0;
            }
            const workTree = new Map();
            for (const path of repo.index.keys()) if (work.has(path)) workTree.set(path, work.get(path));
            const message = sub === 'save' && positional.length ? positional.join(' ') : flags.message;
            const label = message ? `On ${headLabel(repo)}: ${message}` : `WIP on ${headLabel(repo)}: ${short(head)} ${subject(commits.get(head))}`;
            const hash = createCommit(repo, { tree: workTree, parents: [head], message: label });
            repo.stash.unshift({ hash, base: head, index: new Map(repo.index), work: workTree, untracked, label });
            resetHard(repo, head);
            for (const path of untracked.keys()) removeWork(repo, path);
            io.out(`Saved working directory and index state ${label}`);
            return 0;
        },

        tag(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('tag', args, { '-a|--annotate': 'flag', '-m|--message': 'list', '-d|--delete': 'flag', '-l|--list': 'flag', '-f|--force': 'flag', '-n': 'flag' });
            if (flags.delete) {
                let code = 0;
                for (const name of positional) {
                    if (!repo.tags.has(name)) {
                        io.err(`error: tag '${name}' not found.`);
                        code = 1;
                        continue;
                    }
                    io.out(`Deleted tag '${name}' (was ${short(repo.tags.get(name).hash)})`);
                    repo.tags.delete(name);
                }
                return code;
            }
            if (!positional.length || flags.list) {
                const pattern = positional[0] ? globRegex(positional[0], true) : null;
                const names = sorted(repo.tags.keys()).filter((name) => !pattern || pattern.test(name));
                if (names.length) {
                    io.out(...names.map((name) => (flags.n ? `${name.padEnd(15)} ${repo.tags.get(name).annotation ? repo.tags.get(name).annotation.message.split('\n')[0] : subject(commits.get(repo.tags.get(name).hash))}` : name)));
                }
                return 0;
            }
            const [name, rev] = positional;
            if (!name || /\s|\.\.|[~^:?*[\\]/.test(name)) throw fatal(`'${name}' is not a valid tag name.`);
            if (repo.tags.has(name) && !flags.force) throw fatal(`tag '${name}' already exists`);
            const hash = revOrFatal(repo, rev ?? 'HEAD');
            let annotation = null;
            if (flags.annotate || flags.message) {
                if (!flags.message) throw new ShellError('error: there\'s no text editor in this sandbox, so give the tag message with -m:\n    git tag -a v1.0 -m "First release"', 1);
                clock += 60000;
                annotation = { message: joinMessages(flags.message), tagger: { name: config(repo, 'user.name'), email: config(repo, 'user.email'), time: clock } };
            }
            repo.tags.set(name, { hash, annotation });
            return 0;
        },

        remote(args, io) {
            const repo = needRepo();
            const [sub, ...rest] = args;
            if (!sub || sub === '-v' || sub === '--verbose') {
                const names = sorted(repo.remotes.keys());
                if (names.length) io.out(...(sub ? names.flatMap((name) => [`${name}\t${repo.remotes.get(name).url} (fetch)`, `${name}\t${repo.remotes.get(name).url} (push)`]) : names));
                return 0;
            }
            if (sub === 'add') {
                const { positional } = parseArgs('remote add', rest, {});
                const [name, url] = positional;
                if (!name || !url) throw new ShellError('usage: git remote add <name> <url>', 129);
                if (repo.remotes.has(name)) throw new ShellError(`error: remote ${name} already exists.`, 3);
                repo.remotes.set(name, { url });
                return 0;
            }
            if (sub === 'remove' || sub === 'rm') {
                const [name] = rest;
                if (!repo.remotes.has(name)) throw new ShellError(`error: No such remote: '${name}'`, 2);
                repo.remotes.delete(name);
                for (const key of [...repo.remoteRefs.keys()]) if (key.startsWith(`${name}/`)) repo.remoteRefs.delete(key);
                for (const [branch, upstream] of [...repo.upstream]) if (upstream.remote === name) repo.upstream.delete(branch);
                return 0;
            }
            if (sub === 'rename') {
                const [from, to] = rest;
                if (!repo.remotes.has(from)) throw new ShellError(`error: No such remote: '${from}'`, 2);
                repo.remotes.set(to, repo.remotes.get(from));
                repo.remotes.delete(from);
                for (const key of [...repo.remoteRefs.keys()]) {
                    if (key.startsWith(`${from}/`)) {
                        repo.remoteRefs.set(`${to}/${key.slice(from.length + 1)}`, repo.remoteRefs.get(key));
                        repo.remoteRefs.delete(key);
                    }
                }
                for (const upstream of repo.upstream.values()) if (upstream.remote === from) upstream.remote = to;
                return 0;
            }
            if (sub === 'get-url') {
                const remote = repo.remotes.get(rest[0]);
                if (!remote) throw new ShellError(`error: No such remote '${rest[0]}'`, 2);
                io.out(remote.url);
                return 0;
            }
            if (sub === 'set-url') {
                const remote = repo.remotes.get(rest[0]);
                if (!remote) throw new ShellError(`error: No such remote '${rest[0]}'`, 2);
                remote.url = rest[1];
                return 0;
            }
            throw unsupported(`git remote ${sub} isn't available in this sandbox's git. It understands add, remove, rename, get-url, set-url and -v.`);
        },

        clone(args, io) {
            const { flags, positional } = parseArgs('clone', args, { '--bare': 'flag', '-b|--branch': 'value', '-q|--quiet': 'flag' });
            const [source, target] = positional;
            if (!source) throw fatal('You must specify a repository to clone.');
            const from = resolve(source);
            const src = repos.get(from);
            if (!src) throw fatal(`repository '${source}' does not exist`);
            const name = target ?? (flags.bare ? baseName(from) : baseName(from).replace(/\.git$/, ''));
            const dest = resolve(name);
            if (files.has(dest) || (dirs.has(dest) && children(dest).length)) throw fatal(`destination path '${name}' already exists and is not an empty directory.`);
            if (!flags.quiet) io.out(`Cloning into ${flags.bare ? 'bare repository ' : ''}'${name}'...`);
            makeDirs(dest);
            const headBranch = src.heads.has(src.head.branch) ? src.head.branch : (sorted(src.heads.keys())[0] ?? src.head.branch ?? 'main');
            const branch = flags.branch ?? headBranch;
            if (flags.branch && !src.heads.has(branch)) throw fatal(`Remote branch ${branch} not found in upstream origin`);
            const repo = newRepo(dest, Boolean(flags.bare), branch);
            repos.set(dest, repo);
            for (const [tag, info] of src.tags) repo.tags.set(tag, info);
            if (flags.bare) {
                for (const [b, hash] of src.heads) repo.heads.set(b, hash);
                if (!flags.quiet) io.out('done.');
                return 0;
            }
            repo.remotes.set('origin', { url: from });
            for (const [b, hash] of src.heads) repo.remoteRefs.set(`origin/${b}`, hash);
            repo.upstream.set(branch, { remote: 'origin', branch });
            if (!src.heads.size) {
                io.err('warning: You appear to have cloned an empty repository.');
                return 0;
            }
            const hash = src.heads.get(branch);
            repo.heads.set(branch, hash);
            for (const [path, content] of treeOf(hash)) {
                repo.index.set(path, content);
                setWork(repo, path, content);
            }
            logRef(repo, hash, `clone: from ${from}`);
            if (!flags.quiet) io.out('done.');
            return 0;
        },

        fetch(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('fetch', args, { '--all': 'flag', '-p|--prune': 'flag', '-q|--quiet': 'flag', '--tags': 'flag' });
            const names = flags.all ? sorted(repo.remotes.keys()) : [positional[0] ?? (repo.head.branch !== null ? repo.upstream.get(repo.head.branch)?.remote : null) ?? (repo.remotes.has('origin') ? 'origin' : null)];
            if (!names[0]) throw fatal('No remote repository specified.  Please, specify either a URL or a\nremote name from which new revisions should be fetched.');
            for (const name of names) fetch(repo, name, flags.quiet ? output() : io, { prune: Boolean(flags.prune) });
            return 0;
        },

        pull(args, io) {
            const repo = needWorkTree();
            const { flags, positional } = parseArgs('pull', args, { '-r|--rebase': 'flag', '--no-rebase': 'flag', '--ff-only': 'flag', '--ff': 'flag', '--no-ff': 'flag', '-q|--quiet': 'flag' });
            if (flags.quiet) io = quietly(io);
            const branch = repo.head.branch;
            const upstream = branch !== null ? repo.upstream.get(branch) : null;
            let remote = positional[0];
            let remoteBranch = positional[1];
            if (!remote) {
                if (!upstream) {
                    throw new ShellError(
                        `There is no tracking information for the current branch.\nPlease specify which branch you want to merge with.\nSee git-pull(1) for details.\n\n    git pull <remote> <branch>\n\nIf you wish to set tracking information for this branch you can do so with:\n\n    git branch --set-upstream-to=origin/<branch> ${branch ?? '<branch>'}\n`,
                        1,
                    );
                }
                remote = upstream.remote;
                remoteBranch = upstream.branch;
            }
            if (!remoteBranch) {
                if (!upstream || upstream.remote !== remote) throw fatal('You asked to pull from the remote \'' + remote + "', but did not specify\na branch. Because this is not the default configured remote\nfor your current branch, you must specify a branch on the command line.");
                remoteBranch = upstream.branch;
            }
            fetch(repo, remote, io);
            const ref = `${remote}/${remoteBranch}`;
            const target = repo.remoteRefs.get(ref);
            if (!target) throw fatal(`couldn't find remote ref ${remoteBranch}`);
            const head = headHash(repo);
            if (!head) {
                moveTo(repo, target, 'merge');
                setHead(repo, target, `pull: Fast-forward`);
                return 0;
            }
            if (isAncestor(target, head)) {
                io.out('Already up to date.');
                return 0;
            }
            if (isAncestor(head, target) && !flags.rebase) return mergeInto(repo, target, ref, io, { reflogPrefix: 'pull' });
            const setting = config(repo, 'pull.rebase');
            const rebase = flags.rebase ? true : flags['no-rebase'] ? false : setting === 'true' ? true : setting === 'false' ? false : null;
            const ffOnly = flags['ff-only'] || (!flags.rebase && !flags['no-rebase'] && config(repo, 'pull.ff') === 'only');
            if (ffOnly) throw fatal('Not possible to fast-forward, aborting.');
            if (rebase === null) {
                throw new ShellError(
                    [
                        'hint: You have divergent branches and need to specify how to reconcile them.',
                        'hint: You can do so by running one of the following commands sometime before',
                        'hint: your next pull:',
                        'hint:',
                        'hint:   git config pull.rebase false  # merge',
                        'hint:   git config pull.rebase true   # rebase',
                        'hint:   git config pull.ff only       # fast-forward only',
                        'hint:',
                        'hint: You can replace "git config" with "git config --global" to set a default',
                        'hint: preference for all repositories. You can also pass --rebase, --no-rebase,',
                        'hint: or --ff-only on the command line to override the configured default per',
                        'hint: invocation.',
                        'fatal: Need to specify how to reconcile divergent branches.',
                    ].join('\n'),
                    128,
                );
            }
            if (rebase) return rebaseOnto(repo, ref, target, target, io);
            return mergeInto(repo, target, ref, io, { message: `Merge branch '${remoteBranch}' of ${repo.remotes.get(remote).url}`, reflogPrefix: 'pull', noFf: Boolean(flags['no-ff']) });
        },

        push(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('push', args, { '-u|--set-upstream': 'flag', '-f|--force': 'flag', '--force-with-lease': 'flag', '--tags': 'flag', '-d|--delete': 'flag', '--all': 'flag', '-q|--quiet': 'flag' });
            if (flags.quiet) io = quietly(io);
            const branch = repo.head.branch;
            const upstream = branch !== null ? repo.upstream.get(branch) : null;
            let [name, ...refspecs] = positional;
            if (!name) name = upstream?.remote ?? (repo.remotes.size === 1 ? [...repo.remotes.keys()][0] : null);
            if (!name) throw fatal('No configured push destination.\nEither specify the URL from the command-line or configure a remote repository using\n\n    git remote add <name> <url>\n\nand then push using the remote name\n\n    git push <name>\n');
            if (!repo.remotes.has(name)) {
                throw fatal(`'${name}' does not appear to be a git repository\nfatal: Could not read from remote repository.\n\nPlease make sure you have the correct access rights\nand the repository exists.`);
            }
            if (!refspecs.length && !flags.tags && !flags.all) {
                if (branch === null) throw fatal('You are not currently on a branch.\nTo push the history leading to the current (detached HEAD)\nstate now, use\n\n    git push ' + name + ' HEAD:<name-of-remote-branch>\n');
                if (!upstream && !flags['set-upstream']) {
                    throw fatal(`The current branch ${branch} has no upstream branch.\nTo push the current branch and set the remote as upstream, use\n\n    git push --set-upstream ${name} ${branch}\n\nTo have this happen automatically for branches without a tracking\nupstream, see 'push.autoSetupRemote' in 'git help config'.\n`);
                }
                refspecs = [upstream && upstream.remote === name ? `${branch}:${upstream.branch}` : branch];
            }
            if (flags.all) refspecs = sorted(repo.heads.keys());
            const other = repoForRemote(repo, name);
            const url = repo.remotes.get(name).url;
            const lines = [];
            const after = [];
            let failed = false;
            let reason = null;
            for (const spec of refspecs) {
                let [src, dst] = spec.includes(':') ? spec.split(':') : [spec, spec];
                if (flags.delete) [src, dst] = ['', src];
                if (src === '') {
                    if (!other.heads.has(dst)) {
                        io.err(`error: unable to delete '${dst}': remote ref does not exist`);
                        failed = true;
                        continue;
                    }
                    other.heads.delete(dst);
                    repo.remoteRefs.delete(`${name}/${dst}`);
                    lines.push(['-', '[deleted]', dst]);
                    continue;
                }
                const srcName = src === 'HEAD' ? branch : src;
                if (dst === 'HEAD') dst = branch;
                const hash = repo.heads.get(src) ?? (src === 'HEAD' ? headHash(repo) : resolveRev(repo, src));
                if (!hash) {
                    io.err(`error: src refspec ${src} does not match any`);
                    failed = true;
                    continue;
                }
                const old = other.heads.get(dst);
                if (old === hash) {
                    if (flags['set-upstream']) after.push([srcName, `${name}/${dst}`]);
                    continue;
                }
                if (!other.bare && other.head.branch === dst) {
                    lines.push(['!', '[remote rejected]', `${srcName} -> ${dst} (branch is currently checked out)`]);
                    failed = true;
                    continue;
                }
                const forced = old !== undefined && !isAncestor(old, hash);
                if (forced && !flags.force && !(flags['force-with-lease'] && repo.remoteRefs.get(`${name}/${dst}`) === old)) {
                    const fetched = repo.remoteRefs.get(`${name}/${dst}`) === old;
                    lines.push(['!', '[rejected]', `${srcName} -> ${dst} (${fetched ? 'non-fast-forward' : 'fetch first'})`]);
                    failed = true;
                    reason = fetched ? 'behind' : 'fetch';
                    continue;
                }
                other.heads.set(dst, hash);
                repo.remoteRefs.set(`${name}/${dst}`, hash);
                lines.push(old === undefined ? ['*', '[new branch]', `${srcName} -> ${dst}`] : forced ? ['+', `${short(old)}...${short(hash)}`, `${srcName} -> ${dst} (forced update)`] : [' ', `${short(old)}..${short(hash)}`, `${srcName} -> ${dst}`]);
                if (flags['set-upstream']) after.push([srcName, `${name}/${dst}`]);
            }
            if (flags.tags) {
                for (const tag of sorted(repo.tags.keys())) {
                    if (other.tags.get(tag)?.hash === repo.tags.get(tag).hash) continue;
                    other.tags.set(tag, repo.tags.get(tag));
                    lines.push(['*', '[new tag]', `${tag} -> ${tag}`]);
                }
            }
            if (!lines.length && !failed) {
                for (const [b, ref] of after) setUpstream(repo, b, ref, io);
                io.err('Everything up-to-date');
                return 0;
            }
            if (lines.length) {
                io.out(`To ${url}`);
                for (const [flag, summary, rest] of lines) {
                    const text = ` ${flag} ${summary.padEnd(17)} ${rest}`;
                    if (flag === '!') io.err(text);
                    else io.out(text);
                }
            }
            for (const [b, ref] of after) setUpstream(repo, b, ref, io);
            if (failed) {
                io.err(`error: failed to push some refs to '${url}'`);
                if (reason === 'fetch') {
                    io.err(
                        'hint: Updates were rejected because the remote contains work that you do not',
                        'hint: have locally. This is usually caused by another repository pushing to',
                        'hint: the same ref. If you want to integrate the remote changes, use',
                        "hint: 'git pull' before pushing again.",
                        "hint: See the 'Note about fast-forwards' in 'git push --help' for details.",
                    );
                } else if (reason === 'behind') {
                    io.err(
                        'hint: Updates were rejected because the tip of your current branch is behind',
                        'hint: its remote counterpart. If you want to integrate the remote changes,',
                        "hint: use 'git pull' before pushing again.",
                        "hint: See the 'Note about fast-forwards' in 'git push --help' for details.",
                    );
                }
                return 1;
            }
            return 0;
        },

        config(args, io) {
            const { flags, positional } = parseArgs('config', args, { '--global': 'flag', '--local': 'flag', '--get': 'flag', '-l|--list': 'flag', '--unset': 'flag' });
            const repo = flags.global ? null : repoAt(cwd);
            if (flags.list) {
                const entries = [...globalConfig, ...(repo ? repo.config : [])];
                io.out(...entries.map(([key, value]) => `${key}=${value}`));
                return 0;
            }
            const [key, value] = positional;
            if (!key) throw new ShellError('usage: git config [<options>]', 129);
            if (!/^[A-Za-z][\w-]*(\.[^.\s]+)*\.[A-Za-z][\w-]*$/.test(key)) throw new ShellError(`error: key does not contain a section: ${key}`, 1);
            const name = key.toLowerCase();
            if (!flags.global && !repo && (value !== undefined || flags.unset)) throw fatal('not in a git directory');
            const store = repo && !flags.global ? repo.config : globalConfig;
            if (flags.unset) {
                store.delete(name);
                return 0;
            }
            if (value === undefined) {
                const found = flags.global ? globalConfig.get(name) : config(repo, name);
                if (found === undefined || found === null) return 1;
                io.out(found);
                return 0;
            }
            store.set(name, value);
            return 0;
        },

        reflog(args, io) {
            const repo = needRepo();
            const rest = args[0] === 'show' ? args.slice(1) : args;
            const { flags } = parseArgs('reflog', rest, { '-n|--max-count': 'value' }, { numbers: true });
            const limit = Number(flags.count ?? flags['max-count'] ?? Infinity);
            const decor = decorations(repo);
            const lines = repo.reflog.slice(0, limit).map((entry, i) => {
                const labels = decor.get(entry.hash);
                return `${short(entry.hash)}${labels ? ` (${labels.join(', ')})` : ''} HEAD@{${i}}: ${entry.message}`;
            });
            if (lines.length) io.out(...lines);
            return 0;
        },

        clean(args, io) {
            const repo = needWorkTree();
            const { flags } = parseArgs('clean', args, { '-n|--dry-run': 'flag', '-f|--force': 'flag', '-d': 'flag', '-x': 'flag', '-q|--quiet': 'flag' });
            if (!flags.force && !flags['dry-run']) throw fatal('clean.requireForce is true and -f not given: refusing to clean');
            const work = worktree(repo);
            const rules = flags.x ? [] : ignoreRules(repo);
            const candidates = [...work.keys()].filter((path) => !repo.index.has(path) && !ignoredBy(rules, path));
            for (const entry of collapseUntracked(repo, candidates)) {
                if (entry.endsWith('/') && !flags.d) continue;
                io.out(`${flags['dry-run'] ? 'Would remove' : 'Removing'} ${shown(repo, entry)}${entry.endsWith('/') && !shown(repo, entry).endsWith('/') ? '/' : ''}`);
                if (flags['dry-run']) continue;
                if (entry.endsWith('/')) removeTree(workPath(repo, entry.slice(0, -1)));
                else removeWork(repo, entry);
            }
            return 0;
        },

        'ls-files'(args, io) {
            const repo = needWorkTree();
            parseArgs('ls-files', args, {});
            const paths = sorted(new Set([...repo.index.keys(), ...repo.conflicts.keys()]));
            if (paths.length) io.out(...paths.map((p) => shown(repo, p)));
            return 0;
        },

        'check-ignore'(args, io) {
            const repo = needWorkTree();
            const { flags, positional } = parseArgs('check-ignore', args, { '-v|--verbose': 'flag' });
            const rules = ignoreRules(repo);
            let any = false;
            for (const spec of positional) {
                const rule = ignoredBy(rules, toRepoPath(repo, spec));
                if (!rule) continue;
                any = true;
                io.out(flags.verbose ? `.gitignore:${rule.number}:${rule.text}\t${spec}` : spec);
            }
            return any ? 0 : 1;
        },

        'rev-parse'(args, io) {
            const repo = needRepo();
            const { flags, positional } = parseArgs('rev-parse', args, { '--short': 'flag', '--abbrev-ref': 'flag', '--show-toplevel': 'flag', '--is-inside-work-tree': 'flag', '--git-dir': 'flag' });
            if (flags['show-toplevel']) io.out(repo.root);
            if (flags['git-dir']) io.out(gitDir(repo).replace(/\/$/, ''));
            if (flags['is-inside-work-tree']) io.out(String(!repo.bare));
            for (const spec of positional) {
                if (flags['abbrev-ref'] && (spec === 'HEAD' || spec === '@')) {
                    io.out(repo.head.branch ?? 'HEAD');
                    continue;
                }
                const hash = revOrFatal(repo, spec);
                io.out(flags.short ? short(hash) : hash);
            }
            return 0;
        },
    };

    // ---- Running scripts ----

    /** Runs one command line (already tokenized). Returns { code, unsupported }. */
    function runLine(tokens, stdin, emit) {
        const bad = tokens.find((token) => token.type === 'op' && ['|', '<', '&'].includes(token.value));
        if (bad) {
            const what = { '|': 'Pipes (|)', '<': 'Input redirects (<)', '&': 'Background jobs (&)' }[bad.value];
            emit({ level: 'error', text: `${what} aren't available in this sandbox's shell.` });
            return { code: 2, unsupported: true };
        }
        const segments = [];
        let current = { tokens: [], connector: null };
        for (const token of tokens) {
            if (token.type === 'op' && [';', '&&', '||'].includes(token.value)) {
                segments.push(current);
                current = { tokens: [], connector: token.value };
            } else current.tokens.push(token);
        }
        segments.push(current);
        let code = 0;
        let unsupportedSeen = false;
        for (const segment of segments) {
            if ((segment.connector === '&&' && code !== 0) || (segment.connector === '||' && code === 0)) continue;
            if (!segment.tokens.length) continue;
            const result = runSimple(segment.tokens, stdin, emit);
            code = result.code;
            unsupportedSeen ||= result.unsupported;
        }
        return { code, unsupported: unsupportedSeen };
    }

    function runSimple(tokens, stdin, emit) {
        const words = [];
        let redirect = null;
        for (let i = 0; i < tokens.length; i++) {
            const token = tokens[i];
            if (token.type === 'op') {
                const target = tokens[i + 1];
                if (!target || target.type !== 'word') {
                    emit({ level: 'error', text: `syntax error near unexpected token '${token.value}'` });
                    return { code: 2, unsupported: false };
                }
                redirect = { append: token.value === '>>', path: target.value };
                i++;
            } else if (token.glob) words.push(...expandGlob(token.value));
            else words.push(token.value);
        }
        const io = output();
        let code = 0;
        let unsupportedSeen = false;
        if (words.length) {
            const [name, ...args] = words;
            try {
                if (EDITORS.includes(name)) {
                    throw new ShellError(`${name}: there's no text editor in this sandbox. Write files with echo "text" > file, or a heredoc:\n  cat > notes.txt <<'EOF'\n  ...\n  EOF`, 127);
                }
                const command = Object.hasOwn(shellCommands, name) ? shellCommands[name] : null;
                if (!command) throw new ShellError(`${name}: command not found`, 127);
                code = command(args, io, stdin) ?? 0;
            } catch (error) {
                if (!(error instanceof ShellError)) throw error;
                io.err(error.message);
                code = error.code;
                unsupportedSeen = Boolean(error.unsupported);
            }
        }
        if (redirect) {
            const path = resolve(redirect.path);
            const text = io.chunks.filter((chunk) => chunk.level === 'log').map((chunk) => chunk.text).join('');
            io.chunks.splice(0, io.chunks.length, ...io.chunks.filter((chunk) => chunk.level !== 'log'));
            if (dirs.has(path)) {
                io.err(`${redirect.path}: Is a directory`);
                code = 1;
            } else if (!dirs.has(parentOf(path))) {
                io.err(`${redirect.path}: No such file or directory`);
                code = 1;
            } else files.set(path, (redirect.append ? (files.get(path) ?? '') : '') + text);
        }
        for (const chunk of io.chunks) {
            const text = chunk.text.replace(/\n+$/, '');
            if (chunk.text !== '') emit({ level: chunk.level, text });
        }
        return { code, unsupported: unsupportedSeen };
    }

    function run(script, emit = () => {}) {
        const lines = String(script).replace(/\r\n?/g, '\n').split('\n');
        const records = [];
        for (let n = 0; n < lines.length; n++) {
            const lineNumber = n + 1;
            let text = lines[n];
            let parsed = null;
            let failure = null;
            for (;;) {
                if (/\\$/.test(text) && !/\\\\$/.test(text) && n + 1 < lines.length) {
                    text = `${text.slice(0, -1)}${lines[++n]}`;
                    continue;
                }
                try {
                    parsed = tokenize(text);
                    break;
                } catch (error) {
                    if (error.unterminated && n + 1 < lines.length) {
                        text += `\n${lines[++n]}`;
                        continue;
                    }
                    failure = error;
                    break;
                }
            }
            if (failure) {
                emit({ level: 'command', text: `$ ${text}` });
                emit({ level: 'error', text: failure.message });
                records.push({ line: lineNumber, text, exitCode: 2, commented: false, unsupported: false });
                continue;
            }
            const display = parsed.commentAt === -1 ? text.trimEnd() : text.slice(0, parsed.commentAt).trimEnd();
            if (!parsed.tokens.length) continue;
            let stdin = null;
            const echoed = [display];
            const heredoc = parsed.tokens.findIndex((token) => token.type === 'op' && token.value === '<<');
            if (heredoc !== -1) {
                const delimiter = parsed.tokens[heredoc + 1];
                if (!delimiter || delimiter.type !== 'word') {
                    emit({ level: 'command', text: `$ ${display}` });
                    emit({ level: 'error', text: "syntax error near unexpected token '<<'" });
                    records.push({ line: lineNumber, text: display, exitCode: 2, commented: parsed.commentAt !== -1, unsupported: false });
                    continue;
                }
                const body = [];
                while (n + 1 < lines.length && lines[n + 1].trim() !== delimiter.value) body.push(lines[++n]);
                if (n + 1 < lines.length) n++;
                stdin = joinLines(body);
                parsed.tokens.splice(heredoc, 2);
                echoed.push(...body.map((line) => `> ${line}`), `> ${delimiter.value}`);
            }
            emit({ level: 'command', text: `$ ${echoed.join('\n')}` });
            const result = runLine(parsed.tokens, stdin, emit);
            records.push({ line: lineNumber, text: display, exitCode: result.code, commented: parsed.commentAt !== -1, unsupported: result.unsupported });
        }
        return records;
    }

    /** Runs one command and returns what it printed (both streams), without a trailing newline. */
    function capture(line) {
        const entries = [];
        run(line, (entry) => {
            if (entry.level !== 'command') entries.push(entry.text);
        });
        return entries.join('\n');
    }

    /** A snapshot of a repository for challenge checks: the one at `dir`, or the one you're in. */
    function inspect(dir = null) {
        const repo = dir !== null ? (repos.get(resolve(dir)) ?? null) : (repoAt(cwd) ?? repos.get(HOME) ?? (repos.size === 1 ? [...repos.values()][0] : null));
        if (!repo) throw new Error(dir !== null ? `There's no repository at ${dir}.` : "There's no repository here. Did you run git init?");
        const head = headHash(repo);
        const info = (hash) => {
            const commit = commits.get(hash);
            return {
                hash,
                short: short(hash),
                message: subject(commit),
                fullMessage: commit.message,
                parents: [...commit.parents],
                author: commit.author.name,
                files: Object.fromEntries(commit.tree),
                changed: changes(treeOf(commit.parents[0] ?? null), commit.tree).map((change) => change.path),
            };
        };
        const resolveSpec = (rev) => {
            try {
                return resolveRev(repo, rev);
            } catch {
                return null;
            }
        };
        const log = (rev = 'HEAD') => {
            const hash = resolveSpec(rev);
            return hash ? [...ancestors(hash)].sort(byTime).map(info) : [];
        };
        const s = repo.bare ? null : statusOf(repo);
        return {
            root: repo.root,
            bare: repo.bare,
            branch: repo.head.branch,
            detached: repo.head.branch === null,
            head,
            branches: Object.fromEntries(repo.heads),
            tags: Object.fromEntries([...repo.tags].map(([name, tag]) => [name, tag.hash])),
            annotatedTags: [...repo.tags].filter(([, tag]) => tag.annotation).map(([name]) => name),
            remoteBranches: Object.fromEntries(repo.remoteRefs),
            remotes: Object.fromEntries([...repo.remotes].map(([name, remote]) => [name, remote.url])),
            upstream(branch = repo.head.branch) {
                const upstream = repo.upstream.get(branch);
                return upstream ? `${upstream.remote}/${upstream.branch}` : null;
            },
            commits: log(),
            log,
            commit: (rev = 'HEAD') => {
                const hash = resolveSpec(rev);
                return hash ? info(hash) : null;
            },
            resolve: resolveSpec,
            isAncestor: (a, b) => {
                const x = resolveSpec(a);
                const y = resolveSpec(b);
                return Boolean(x && y && isAncestor(x, y));
            },
            files: repo.bare ? {} : Object.fromEntries(worktree(repo)),
            file: (path) => files.get(workPath(repo, path)) ?? null,
            index: Object.fromEntries(repo.index),
            status: s && {
                staged: s.staged.map((change) => change.path),
                unstaged: s.unstaged.map((change) => change.path),
                untracked: s.untracked,
                conflicted: s.conflicted,
            },
            clean: s ? !s.staged.length && !s.unstaged.length && !s.untracked.length && !s.conflicted.length : true,
            merging: Boolean(repo.merge),
            rebasing: Boolean(repo.rebase),
            cherryPicking: repo.sequence?.kind === 'cherry-pick',
            reverting: repo.sequence?.kind === 'revert',
            stash: repo.stash.map((entry) => entry.label),
            config: (key) => config(repo, key.toLowerCase()),
            reflog: repo.reflog.map((entry) => entry.message),
            ignored: (path) => Boolean(ignoredBy(ignoreRules(repo), path)),
        };
    }

    return { run, capture, inspect, cwd: () => cwd };
}
