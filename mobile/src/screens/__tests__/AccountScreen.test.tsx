import { fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { useAuthStore } from '../../auth/store';
import { currentToken } from '../../auth/token';
import { updateLesson, useProgressStore } from '../../progress/store';
import { stopSync } from '../../sync/sync';
import { renderWithClient } from '../../test/render';
import { AccountScreen } from '../AccountScreen';

const user = { id: 7, name: 'Ada', email: 'ada@example.com' };

afterEach(() => {
  stopSync({ clear: true });
  jest.restoreAllMocks();
});

test('signing in saves the token, names the device and goes back', async () => {
  const login = jest.spyOn(api, 'login').mockResolvedValue({ user, token: '1|secret' });
  jest.spyOn(api, 'mergeProgress').mockResolvedValue({ lessons: {}, cards: {} });
  await renderWithClient(<AccountScreen />);

  await fireEvent.changeText(screen.getByLabelText('Email'), 'ada@example.com');
  await fireEvent.changeText(screen.getByLabelText('Password'), 'correct-horse');
  await fireEvent.press(screen.getByRole('button', { name: 'Sign in' }));

  expect(login).toHaveBeenCalledWith({ email: 'ada@example.com', password: 'correct-horse', device_name: expect.stringContaining('learnP app') });
  expect(currentToken()).toBe('1|secret');
  expect(useAuthStore.getState().user).toEqual(user);
  expect(router.back).toHaveBeenCalled();
});

test('registering asks for a name and shows the server’s messages under each field', async () => {
  jest.spyOn(api, 'register').mockRejectedValue(
    new ApiError('The email has already been taken.', 422, { email: ['The email has already been taken.'] }),
  );
  await renderWithClient(<AccountScreen />);

  await fireEvent.press(screen.getByRole('button', { name: 'New here? Create an account' }));
  await fireEvent.changeText(screen.getByLabelText('Name'), 'Ada');
  await fireEvent.press(screen.getByRole('button', { name: 'Create account' }));

  expect(await screen.findByText('The email has already been taken.')).toBeTruthy();
  expect(useAuthStore.getState().user).toBeNull();
});

test('signing out forgets the token and this device’s copy of the progress', async () => {
  jest.spyOn(api, 'logout').mockResolvedValue(null);
  useAuthStore.setState({ user, ready: true });
  updateLesson(1, () => ({ read: true }));
  await renderWithClient(<AccountScreen />);

  expect(screen.getByText('ada@example.com')).toBeTruthy();
  await fireEvent.press(screen.getByRole('button', { name: 'Sign out' }));

  expect(useAuthStore.getState().user).toBeNull();
  expect(currentToken()).toBeNull();
  expect(useProgressStore.getState().lessons).toEqual({});
});
