import { fireEvent, render, screen } from '@testing-library/react-native';
import { api } from '../../../api/client';
import { useProgressStore } from '../../../progress/store';
import { jsLesson } from '../../../test/fixtures';
import { ChallengesStep } from '../ChallengesStep';
import { SandboxStep } from '../SandboxStep';

/** JavaScript runs on the device, in the WebView runner; under Jest, its stand-in answers. */
const runner = jest.requireMock('../../../js-runtime/JsRunnerView') as { answer: jest.Mock };

afterEach(() => {
  jest.restoreAllMocks();
  runner.answer.mockClear();
});

test('the sandbox runs JavaScript on the device, not on the server', async () => {
  const runPhp = jest.spyOn(api, 'runPhp');
  await render(<SandboxStep lesson={jsLesson.data} language="javascript" schema={[]} request={null} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(await screen.findByText("ran: console.log('start');")).toBeTruthy();
  expect(runner.answer).toHaveBeenCalledWith(expect.objectContaining({ kind: 'run', language: 'javascript', code: "console.log('start');" }));
  expect(runPhp).not.toHaveBeenCalled();
  expect(screen.getByLabelText('Sandbox JavaScript editor')).toBeTruthy();
});

test('errors and type errors are shown', async () => {
  runner.answer.mockImplementationOnce(() => ({
    entries: [{ level: 'log', text: 'before' }],
    output: 'before',
    error: 'TypeError: x is not a function (line 2)',
    typeErrors: [],
    stillRunning: false,
    durationMs: 3,
    hasPage: false,
  }));
  await render(<SandboxStep lesson={jsLesson.data} language="javascript" schema={[]} request={null} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(await screen.findByText('TypeError: x is not a function (line 2)')).toBeTruthy();
  expect(screen.getByText('Your code stopped with an error.')).toBeTruthy();
});

test('a JavaScript challenge is checked on the device and saved when it passes', async () => {
  runner.answer.mockImplementationOnce(() => ({
    passed: true,
    loaded: true,
    error: null,
    output: '',
    entries: [],
    checks: [{ label: 'Adds', passed: true, message: null }],
  }));
  await render(<ChallengesStep lesson={jsLesson.data} language="javascript" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Add two numbers'), 'const add = (a, b) => a + b;');
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText('Correct! All 1 checks pass.')).toBeTruthy();
  expect(runner.answer).toHaveBeenCalledWith(
    expect.objectContaining({ kind: 'check', code: 'const add = (a, b) => a + b;', checks: jsLesson.data.challenges[0].checks }),
  );
  expect(useProgressStore.getState().lessons[3].challenges[51].passed).toBe(true);
});

test('a failing JavaScript check shows what failed', async () => {
  runner.answer.mockImplementationOnce(() => ({
    passed: false,
    loaded: true,
    error: null,
    output: 'hi',
    entries: [{ level: 'log', text: 'hi' }],
    checks: [{ label: 'Adds', passed: false, message: 'Expected 3, got undefined.' }],
  }));
  await render(<ChallengesStep lesson={jsLesson.data} language="javascript" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText("0 of 1 checks pass. See what's missing below.")).toBeTruthy();
  expect(screen.getByText('Expected 3, got undefined.')).toBeTruthy();
  expect(screen.getByText('hi')).toBeTruthy();
});
