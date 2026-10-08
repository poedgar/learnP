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

test('the hint toggles and Start over restores the starter code', async () => {
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Show a hint' }));
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
