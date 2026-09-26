import { fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
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
