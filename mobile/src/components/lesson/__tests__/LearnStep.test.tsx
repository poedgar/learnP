import { fireEvent, render, screen } from '@testing-library/react-native';
import { useProgressStore } from '../../../progress/store';
import { sqlLesson } from '../../../test/fixtures';
import { LearnStep } from '../LearnStep';

const lesson = sqlLesson.data;

function renderStep(overrides: Partial<Parameters<typeof LearnStep>[0]> = {}) {
  const props = { lesson, language: 'sql' as const, onTry: jest.fn(), onFinish: jest.fn(), onSkip: jest.fn(), ...overrides };
  return render(<LearnStep {...props} />).then(() => props);
}

test('shows the first section and moves to the next', async () => {
  await renderStep();

  expect(screen.getByText('Section 1 of 2')).toBeTruthy();
  expect(screen.getByText('Selecting columns')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: 'Next: Filtering rows' }));

  expect(screen.getByText('Section 2 of 2')).toBeTruthy();
  expect(useProgressStore.getState().lessons[1].theoryPage).toBe(2);
});

test('Try sends the code block to the sandbox', async () => {
  const props = await renderStep();

  await fireEvent.press(screen.getByRole('button', { name: 'Try it in the sandbox' }));

  expect(props.onTry).toHaveBeenCalledWith('SELECT title FROM books;');
});

test('the last section offers the sandbox and the quiz', async () => {
  useProgressStore.setState({ lessons: { 1: { read: false, theoryPage: 2, sandboxCode: null, quiz: {}, challenges: {} } } });
  const props = await renderStep();

  await fireEvent.press(screen.getByRole('button', { name: 'Mark as read and open the sandbox' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Go to the quiz' }));

  expect(props.onFinish).toHaveBeenCalled();
  expect(props.onSkip).toHaveBeenCalled();
});

test('a saved page past the end falls back to the last section', async () => {
  useProgressStore.setState({ lessons: { 1: { read: true, theoryPage: 9, sandboxCode: null, quiz: {}, challenges: {} } } });
  await renderStep();

  expect(screen.getByText('Section 2 of 2')).toBeTruthy();
  expect(screen.getByRole('button', { name: 'Open the sandbox' })).toBeTruthy();
});
