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
