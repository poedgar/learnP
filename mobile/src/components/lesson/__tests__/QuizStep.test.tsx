import { act as reactAct } from 'react';
import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import type { QuizAnswer } from '../../../api/types';
import { useProgressStore } from '../../../progress/store';
import { sqlLesson } from '../../../test/fixtures';
import { QuizStep } from '../QuizStep';

const lesson = sqlLesson.data;

afterEach(() => jest.restoreAllMocks());

async function renderQuiz(overrides: Partial<Parameters<typeof QuizStep>[0]> = {}) {
  const props = { lesson, language: 'sql' as const, onNext: jest.fn(), onTry: jest.fn(), ...overrides };
  await render(<QuizStep {...props} />);
  return props;
}

test('a correct answer shows the explanation and is saved', async () => {
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 1, explanation: 'FROM names the table.' });
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer B' }));

  expect(await screen.findByText('Correct.')).toBeTruthy();
  expect(screen.getByText('FROM names the table.')).toBeTruthy();
  expect(api.answer).toHaveBeenCalledWith(11, 1);
  expect(useProgressStore.getState().lessons[1].quiz[11]).toEqual({
    option: 1,
    correct: true,
    correct_option: 1,
    explanation: 'FROM names the table.',
  });
});

test('a wrong answer says Not quite', async () => {
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: false, correct_option: 1, explanation: 'It is FROM.' });
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));

  expect(await screen.findByText('Not quite.')).toBeTruthy();
});

test('tapping twice while the answer is on its way sends one request', async () => {
  let resolve!: (answer: QuizAnswer) => void;
  jest.spyOn(api, 'answer').mockReturnValue(new Promise((r) => (resolve = r)));
  await renderQuiz();

  // fireEvent resolves with the handler's promise, so the first press settles only once the answer arrives.
  const first = fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));
  await screen.findByRole('button', { name: 'Answer B', disabled: true });
  await fireEvent.press(screen.getByRole('button', { name: 'Answer B' }));

  expect(api.answer).toHaveBeenCalledTimes(1);
  await act(async () => {
    resolve({ correct: false, correct_option: 1, explanation: 'It is FROM.' });
    await first;
  });
});

test('two taps in the same frame send one request', async () => {
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 1, explanation: 'FROM names the table.' });
  await renderQuiz();
  const a = screen.getByRole('button', { name: 'Answer A' });
  const b = screen.getByRole('button', { name: 'Answer B' });

  // Both touches end before React re-renders: call the press handlers inside one synchronous act().
  reactAct(() => {
    a.props.onClick({ nativeEvent: {} });
    b.props.onClick({ nativeEvent: {} });
  });
  await screen.findByText('Correct.');

  expect(api.answer).toHaveBeenCalledTimes(1);
});

test('options past F keep counting letters', async () => {
  const options = ['1', '2', '3', '4', '5', '6', '7'];
  await renderQuiz({ lesson: { ...lesson, quiz_questions: [{ id: 11, question: 'Pick one', code: null, options }] } });

  expect(screen.getByRole('button', { name: 'Answer G' })).toBeTruthy();
});

test('an error is shown and the question stays open', async () => {
  jest.spyOn(api, 'answer').mockRejectedValue(new ApiError('Could not reach the server. Check your connection and try again.', 0));
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));

  expect(await screen.findByText('Could not reach the server. Check your connection and try again.')).toBeTruthy();
  expect(useProgressStore.getState().lessons[1]).toBeUndefined();
});

test('resumes at the first unanswered question and offers the challenges when done', async () => {
  useProgressStore.setState({
    lessons: {
      1: {
        read: false,
        theoryPage: 1,
        sandboxCode: null,
        quiz: { 11: { option: 1, correct: true, correct_option: 1, explanation: 'x' } },
        challenges: {},
      },
    },
  });
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 0, explanation: 'One.' });
  const props = await renderQuiz();

  expect(screen.getByText('What does this return?')).toBeTruthy();
  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Go to the challenges' }));

  expect(props.onNext).toHaveBeenCalled();
  expect(screen.getByText(/You got 2 of 2 right/)).toBeTruthy();
});

test('after answering, a question with code can be tried in the sandbox', async () => {
  useProgressStore.setState({
    lessons: {
      1: {
        read: false,
        theoryPage: 1,
        sandboxCode: null,
        quiz: {
          11: { option: 1, correct: true, correct_option: 1, explanation: 'x' },
          12: { option: 0, correct: true, correct_option: 0, explanation: 'y' },
        },
        challenges: {},
      },
    },
  });
  const props = await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Question 2' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Try it in the sandbox' }));

  expect(props.onTry).toHaveBeenCalledWith('SELECT 1;');
});

test('a lesson without questions says so', async () => {
  await renderQuiz({ lesson: { ...lesson, quiz_questions: [] } });

  expect(screen.getByText('This lesson has no quiz yet.')).toBeTruthy();
});
