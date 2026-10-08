import { act, fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { updateLesson } from '../../progress/store';
import { sqlLesson } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { LessonScreen } from '../LessonScreen';

afterEach(() => jest.restoreAllMocks());

test('opens on Learn and switches steps through the URL', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);

  expect(await screen.findByText('Section 1 of 2')).toBeTruthy();
  await fireEvent.press(screen.getByRole('tab', { name: /Quiz/ }));

  expect(router.setParams).toHaveBeenCalledWith({ step: 'quiz' });
});

test('the step param picks the step, and unknown values fall back to Learn', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  const { rerender } = await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="quiz" />);

  expect(await screen.findByText('Which clause picks the table?')).toBeTruthy();

  await rerender(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="bogus" />);
  expect(await screen.findByText('Section 1 of 2')).toBeTruthy();
});

test('tabs show progress', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);
  await screen.findByText('Section 1 of 2');

  await act(async () =>
    updateLesson(1, () => ({
      read: true,
      quiz: { 11: { option: 1, correct: true, correct_option: 1, explanation: '' } },
      challenges: { 21: { passed: true, code: null } },
    })),
  );

  expect(screen.getByRole('tab', { name: 'Learn, Read' })).toBeTruthy();
  expect(screen.getByRole('tab', { name: 'Quiz, 1/2' })).toBeTruthy();
  expect(screen.getByRole('tab', { name: 'Challenges, 1/2' })).toBeTruthy();
});

test('Try from the theory opens the sandbox with that code', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  jest.spyOn(api, 'runSql').mockResolvedValue({ results: [], error: null });
  const { rerender } = await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);

  await fireEvent.press(await screen.findByRole('button', { name: 'Try it in the sandbox' }));
  expect(router.setParams).toHaveBeenCalledWith({ step: 'sandbox' });

  await rerender(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="sandbox" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT title FROM books;');
  expect(api.runSql).toHaveBeenCalledWith(1, 'SELECT title FROM books;');
});

test('an unknown lesson says so', async () => {
  jest.spyOn(api, 'lesson').mockRejectedValue(new ApiError('Not Found', 404));
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="nope" />);

  expect(await screen.findByText("We couldn't find that lesson.")).toBeTruthy();
});
