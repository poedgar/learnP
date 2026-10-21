import { fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { useActivityStore } from '../../progress/activity';
import { useProgressStore } from '../../progress/store';
import { courses } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { HomeScreen } from '../HomeScreen';

afterEach(() => jest.restoreAllMocks());

test('lists courses and opens one', async () => {
  jest.spyOn(api, 'courses').mockResolvedValue(courses);
  await renderWithClient(<HomeScreen />);

  await fireEvent.press(await screen.findByRole('button', { name: /OOP in PHP/ }));

  expect(screen.getByText('Ask questions of your data.')).toBeTruthy();
  expect(router.push).toHaveBeenCalledWith('/courses/php-oop');
});

test('an unreachable server shows the message and Try again refetches', async () => {
  const courses$ = jest
    .spyOn(api, 'courses')
    .mockRejectedValueOnce(new ApiError('Could not reach the server. Check your connection and try again.', 0))
    .mockResolvedValueOnce(courses);
  await renderWithClient(<HomeScreen />);

  expect(await screen.findByText('Could not reach the server. Check your connection and try again.')).toBeTruthy();
  await fireEvent.press(screen.getByRole('button', { name: 'Try again' }));

  expect(await screen.findByText('SQL')).toBeTruthy();
  expect(courses$).toHaveBeenCalledTimes(2);
});

test('courses are grouped into learning paths, with progress and a dashboard', async () => {
  const withLessons = courses.map((course) => ({
    ...course,
    lessons: [{ id: course.id * 10, slug: 'l', title: 'L', position: 1, quiz_questions_count: 0, challenges_count: 0 }],
  }));
  jest.spyOn(api, 'courses').mockResolvedValue(withLessons);
  jest.spyOn(api, 'tracks').mockResolvedValue([{ slug: 'data', title: 'Data', description: 'Ask questions of data.', courses: ['sql'] }]);
  useProgressStore.setState({ lessons: { 10: { read: true, theoryPage: 1, sandboxCode: null, quiz: {}, challenges: {} } } });
  useActivityStore.setState({ recent: { course: 'sql', courseTitle: 'SQL', lesson: 'l', lessonTitle: 'Your first queries' }, days: [] });
  await renderWithClient(<HomeScreen />);

  expect(await screen.findByText('Data')).toBeTruthy();
  expect(screen.getByRole('button', { name: /^SQL, 1 lesson, Finished/ })).toBeTruthy();
  expect(screen.queryByRole('button', { name: /OOP in PHP/ })).toBeNull();
  expect(screen.getByLabelText('Lessons completed: 1')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: /Continue where you left off/ }));
  expect(router.push).toHaveBeenCalledWith('/courses/sql/lessons/l');
});

test('a course label replaces the language', async () => {
  jest.spyOn(api, 'courses').mockResolvedValue([{ ...courses[1], slug: 'docker', title: 'Docker', label: 'Docker' }]);
  await renderWithClient(<HomeScreen />);

  expect(await screen.findByText(/1 lesson · Docker/)).toBeTruthy();
});
