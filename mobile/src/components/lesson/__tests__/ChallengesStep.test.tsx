import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import type { SqlCheck } from '../../../api/types';
import { useProgressStore } from '../../../progress/store';
import { phpLesson, sqlLesson } from '../../../test/fixtures';
import { ChallengesStep } from '../ChallengesStep';

afterEach(() => jest.restoreAllMocks());

const passingSql = {
  passed: true,
  message: 'Correct! Your query returns exactly the expected result.',
  run: { results: [{ type: 'rows' as const, sql: 'x', columns: ['title', 'author'], rows: [['Dune', 'Herbert']], truncated: false, duration_ms: 1 }], error: null },
  expected: { columns: ['title', 'author'], rows: [['Dune', 'Herbert']] },
};

test('starts at the first unsolved challenge with its starter code', async () => {
  useProgressStore.setState({ lessons: { 1: { read: false, theoryPage: 1, sandboxCode: null, quiz: {}, challenges: { 21: { passed: true, code: 'done' } } } } });
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  expect(screen.getByText('Cheap books')).toBeTruthy();
  expect(screen.getByText('1 of 2 solved')).toBeTruthy();
  expect(screen.getByText('Row order is checked for this challenge.')).toBeTruthy();
});

test('a passing SQL answer is saved and offers the next challenge', async () => {
  jest.spyOn(api, 'checkSql').mockResolvedValue(passingSql);
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'SELECT title, author FROM books');
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText('Correct! Your query returns exactly the expected result.')).toBeTruthy();
  expect(api.checkSql).toHaveBeenCalledWith(21, 'SELECT title, author FROM books');
  expect(useProgressStore.getState().lessons[1].challenges[21]).toEqual({ passed: true, code: 'SELECT title, author FROM books' });

  await fireEvent.press(screen.getByRole('button', { name: 'Next challenge' }));
  expect(screen.getByText('Books under 10.')).toBeTruthy();
});

test('switching challenges while a check is on its way keeps the checked solution', async () => {
  let resolve!: (check: SqlCheck) => void;
  jest.spyOn(api, 'checkSql').mockReturnValue(new Promise((r) => (resolve = r)));
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'SELECT title, author FROM books');
  // fireEvent resolves with the handler's promise, so this press settles only once the check returns.
  const check = fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  await screen.findByRole('button', { name: 'Checking…' });
  await fireEvent.press(screen.getByRole('button', { name: 'Challenge 2: Cheap books' }));
  await act(async () => {
    resolve(passingSql);
    await check;
  });

  expect(useProgressStore.getState().lessons[1].challenges[21]).toEqual({ passed: true, code: 'SELECT title, author FROM books' });
  expect(useProgressStore.getState().lessons[1].challenges[22]).toBeUndefined();
  expect(screen.queryByText(passingSql.message)).toBeNull();
});

test('a failing PHP answer lists the checks', async () => {
  jest.spyOn(api, 'checkPhp').mockResolvedValue({
    passed: false,
    loaded: true,
    error: null,
    output: '',
    checks: [
      { label: 'Book exists', passed: true, message: null },
      { label: 'Book has a title', passed: false, message: 'Expected a title.' },
    ],
  });
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText("1 of 2 checks pass. See what's missing below.")).toBeTruthy();
  expect(screen.getByText('Expected a title.')).toBeTruthy();
  expect(useProgressStore.getState().lessons[2]?.challenges[31]?.passed ?? false).toBe(false);
});

test('when the PHP sandbox is down the message shows and the code stays', async () => {
  jest.spyOn(api, 'checkPhp').mockRejectedValue(new ApiError('The PHP sandbox is unavailable. Try again shortly.', 503));
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Your first class'), '<?php class Book {}');
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText('The PHP sandbox is unavailable. Try again shortly.')).toBeTruthy();
  expect(screen.getByLabelText('Code for challenge: Your first class').props.value).toBe('<?php class Book {}');
});

test('the hint toggles after a failed check, and Start over restores the starter code', async () => {
  jest.spyOn(api, 'checkSql').mockResolvedValue({ ...passingSql, passed: false, message: 'Not quite.' });
  jest.spyOn(api, 'recordAttempt').mockResolvedValue(null);
  jest.spyOn(api, 'recordHint').mockResolvedValue(null);
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Show a hint' }));
  expect(screen.getByText('Put the two column names after SELECT.')).toBeTruthy();

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'nope');
  await fireEvent.press(screen.getByRole('button', { name: 'Start over' }));
  expect(screen.getByLabelText('Code for challenge: Titles and authors').props.value).toBe('SELECT\nFROM books;');
});

test('switching challenges keeps each one’s code', async () => {
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'SELECT 1');
  await fireEvent.press(screen.getByRole('button', { name: 'Challenge 2: Cheap books' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Challenge 1: Titles and authors' }));

  expect(screen.getByLabelText('Code for challenge: Titles and authors').props.value).toBe('SELECT 1');
});

test('Run shows the result without checking', async () => {
  jest.spyOn(api, 'runPhp').mockResolvedValue({ output: 'hello', fatal: false, durationMs: 4 });
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(await screen.findByText('hello')).toBeTruthy();
});

test('a lesson without challenges says so', async () => {
  await render(<ChallengesStep lesson={{ ...sqlLesson.data, challenges: [] }} language="sql" />);

  expect(screen.getByText('This lesson has no challenges yet.')).toBeTruthy();
});

const failingSql = { ...passingSql, passed: false, message: 'Not quite.' };

test('help arrives in steps: a hint after one failed check, the solution after three', async () => {
  jest.spyOn(api, 'checkSql').mockResolvedValue(failingSql);
  const attempt = jest.spyOn(api, 'recordAttempt').mockResolvedValue(null);
  const hintView = jest.spyOn(api, 'recordHint').mockResolvedValue(null);
  jest.spyOn(api, 'solution').mockResolvedValue('SELECT title, author FROM books;');
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  expect(screen.queryByRole('button', { name: 'Show a hint' })).toBeNull();
  expect(screen.getByText('Stuck? Check your answer: a hint appears after your first try.')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Show a hint' }));
  expect(hintView).toHaveBeenCalledWith(21);
  expect(screen.getByText('The solution can be shown after 2 more tries.')).toBeTruthy();
  expect(screen.queryByRole('button', { name: 'Show the solution' })).toBeNull();

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  expect(attempt.mock.calls.map(([id, passed, n]) => [id, passed, n])).toEqual([[21, false, 1], [21, false, 2], [21, false, 3]]);

  await fireEvent.press(await screen.findByRole('button', { name: 'Show the solution' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Put it in the editor' }));
  expect(useProgressStore.getState().lessons[1].challenges[21].code).toBe('SELECT title, author FROM books;');
});

test('a first-try pass is recorded as attempt 1', async () => {
  jest.spyOn(api, 'checkSql').mockResolvedValue(passingSql);
  const attempt = jest.spyOn(api, 'recordAttempt').mockResolvedValue(null);
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(attempt).toHaveBeenCalledWith(21, true, 1);
});
