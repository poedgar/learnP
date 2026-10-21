import { checkGit, runGit } from '..';

const script = 'git init shop\ncd shop\necho Dune > books.txt\ngit add .\ngit commit -m "Add Dune"';

test('a script runs in the simulated Git, right in the app', () => {
  const result = runGit(script);

  expect(result.entries[0]).toEqual({ level: 'command', text: '$ git init shop' });
  expect(result.entries.some((entry) => /\[main \(root-commit\) [0-9a-f]{7}\] Add Dune/.test(entry.text))).toBe(true);
  expect(result.failed).toEqual([]);
});

test('checks read the repository the script left behind', () => {
  const report = checkGit(script, [
    { label: 'One commit', code: "return repo().commits.length === 1 || 'Expected one commit.';" },
    { label: 'Two commits', code: "return repo().commits.length === 2 || 'Expected two commits.';" },
    { label: 'No code', code: undefined },
  ]);

  expect(report.loaded).toBe(true);
  expect(report.checks.map((check) => [check.passed, check.message])).toEqual([
    [true, null],
    [false, 'Expected two commits.'],
    [false, 'This check did not pass.'],
  ]);
});

const printed = (code: string) =>
  runGit(code)
    .entries.filter((entry) => entry.level !== 'command')
    .map((entry) => entry.text)
    .join('\n');

test('an option with several spellings works under each of them', () => {
  const base = 'git init -q shop\ncd shop\necho Dune > books.txt\ngit add .\ngit commit -q -m "Add Dune"\n';

  expect(printed(`${base}git log --format=%s`)).toBe('Add Dune');
  expect(printed(`${base}git log --pretty=format:%s`)).toBe('Add Dune');
  expect(printed(`${base}echo Emma >> books.txt\ngit add .\ngit diff --staged --name-only`)).toBe('books.txt');
  expect(printed(`${base}echo Emma >> books.txt\ngit add .\ngit diff --cached --name-only`)).toBe('books.txt');
});

test('rebase -q keeps quiet', () => {
  const result = runGit(
    'git init -q shop\ncd shop\necho Dune > books.txt\ngit add .\ngit commit -q -m "Add Dune"\n' +
      'git switch -q -c search\necho x > search.php\ngit add .\ngit commit -q -m "Add search"\n' +
      'git switch -q main\necho Emma >> books.txt\ngit commit -q -am "Add Emma"\ngit switch -q search\ngit rebase -q main',
  );

  expect(result.failed).toEqual([]);
  expect(result.entries.filter((entry) => entry.level !== 'command')).toEqual([]);
});

test('a pushed tag becomes a tag on the server, not a branch', () => {
  const report = checkGit(
    'git init -q --bare server.git\ngit init -q shop\ncd shop\necho Dune > books.txt\ngit add .\ngit commit -q -m "Add Dune"\n' +
      'git remote add origin ../server.git\ngit push -q -u origin main\ngit tag -a v1.0 -m "First release"\ngit push origin v1.0',
    [
      { label: 'tag', code: "const s = repo('/work/server.git'); return ('v1.0' in s.tags && !('v1.0' in s.branches)) || JSON.stringify(s.branches);" },
      { label: 'report', code: "return output.includes('[new tag]') || output;" },
    ],
  );

  expect(report.checks.map((check) => check.message)).toEqual([null, null]);
});
