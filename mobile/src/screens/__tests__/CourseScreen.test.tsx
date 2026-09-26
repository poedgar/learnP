import { act, fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { updateLesson } from '../../progress/store';
import { sqlCourse } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { CourseScreen } from '../CourseScreen';

afterEach(() => jest.restoreAllMocks());

test('lists lessons with what they contain and opens one', async () => {
  jest.spyOn(api, 'course').mockResolvedValue(sqlCourse);
  await renderWithClient(<CourseScreen slug="sql" />);

  expect(await screen.findByText('Your first queries')).toBeTruthy();
  expect(screen.getByText('Reading, a live sandbox, 2 quiz questions and 2 challenges.')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: /Your first queries/ }));
  expect(router.push).toHaveBeenCalledWith('/courses/sql/lessons/your-first-queries');
});

test('shows progress for started and completed lessons', async () => {
  jest.spyOn(api, 'course').mockResolvedValue(sqlCourse);
  await renderWithClient(<CourseScreen slug="sql" />);
  await screen.findByText('Your first queries');

  await act(async () => updateLesson(1, () => ({ read: true })));
  expect(screen.getByText('Read · quiz 0/2 · challenges 0/2')).toBeTruthy();

  await act(async () =>
    updateLesson(1, () => ({
      quiz: {
        11: { option: 1, correct: true, correct_option: 1, explanation: '' },
        12: { option: 0, correct: true, correct_option: 0, explanation: '' },
      },
      challenges: { 21: { passed: true, code: null }, 22: { passed: true, code: null } },
    })),
  );
  expect(screen.getByText('Completed.')).toBeTruthy();
});

test('an unknown course says so without a retry button', async () => {
  jest.spyOn(api, 'course').mockRejectedValue(new ApiError('Not Found', 404));
  await renderWithClient(<CourseScreen slug="nope" />);

  expect(await screen.findByText("We couldn't find that course.")).toBeTruthy();
  expect(screen.queryByRole('button', { name: 'Try again' })).toBeNull();
});
