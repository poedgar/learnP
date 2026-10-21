import { fireEvent, screen } from '@testing-library/react-native';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Alert, Share } from 'react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { useAuthStore } from '../../auth/store';
import { currentToken } from '../../auth/token';
import { updateLesson, useProgressStore } from '../../progress/store';
import { stopSync } from '../../sync/sync';
import { renderWithClient } from '../../test/render';
import { AccountScreen } from '../AccountScreen';

const user = { id: 7, name: 'Ada', email: 'ada@example.com', email_verified: true, has_password: true, github: false };

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

test('GitHub sign-in trades the one-time code for a token', async () => {
  jest.spyOn(api, 'authOptions').mockResolvedValue({ github: true });
  jest.spyOn(api, 'mergeProgress').mockResolvedValue({ lessons: {}, cards: {} });
  const exchange = jest.spyOn(api, 'exchangeGithubCode').mockResolvedValue({ user, token: '2|github' });
  jest.mocked(WebBrowser.openAuthSessionAsync).mockResolvedValue({ type: 'success', url: 'learnp://auth/github?code=abc123' });
  await renderWithClient(<AccountScreen />);

  await fireEvent.press(await screen.findByRole('button', { name: 'Continue with GitHub' }));

  expect(jest.mocked(WebBrowser.openAuthSessionAsync).mock.calls[0][0]).toBe(
    'http://api.test/auth/github?return_to=learnp%3A%2F%2Fauth%2Fgithub',
  );
  expect(exchange).toHaveBeenCalledWith('abc123', expect.stringContaining('learnP app'));
  expect(currentToken()).toBe('2|github');
  expect(useAuthStore.getState().user).toEqual(user);
});

test('GitHub errors are shown, and closing the browser does nothing', async () => {
  jest.spyOn(api, 'authOptions').mockResolvedValue({ github: true });
  jest.mocked(WebBrowser.openAuthSessionAsync).mockResolvedValueOnce({ type: 'cancel' } as WebBrowser.WebBrowserAuthSessionResult);
  await renderWithClient(<AccountScreen />);

  await fireEvent.press(await screen.findByRole('button', { name: 'Continue with GitHub' }));
  expect(useAuthStore.getState().user).toBeNull();

  jest.mocked(WebBrowser.openAuthSessionAsync).mockResolvedValueOnce({ type: 'success', url: 'learnp://auth/github?error=github-email' });
  await fireEvent.press(screen.getByRole('button', { name: 'Continue with GitHub' }));
  expect(await screen.findByText(/GitHub did not share an email address/)).toBeTruthy();
});

test('forgot password opens the web page', async () => {
  await renderWithClient(<AccountScreen />);

  await fireEvent.press(screen.getByRole('button', { name: 'Forgot your password?' }));

  expect(Linking.openURL).toHaveBeenCalledWith('http://api.test/forgot-password');
});

test('the data export is shared', async () => {
  jest.spyOn(api, 'exportAccount').mockResolvedValue({ account: { email: 'ada@example.com' } });
  const share = jest.spyOn(Share, 'share').mockResolvedValue({ action: 'sharedAction' });
  useAuthStore.setState({ user, ready: true });
  await renderWithClient(<AccountScreen />);

  await fireEvent.press(screen.getByRole('button', { name: 'Export my data' }));

  expect(share.mock.calls[0][0].message).toContain('ada@example.com');
});

test('deleting the account asks first, then removes everything from the device', async () => {
  const remove = jest.spyOn(api, 'deleteAccount').mockResolvedValue(null);
  jest.spyOn(Alert, 'alert').mockImplementation((_title, _message, buttons) => buttons?.find((b) => b.style === 'destructive')?.onPress?.());
  useAuthStore.setState({ user, ready: true });
  updateLesson(1, () => ({ read: true }));
  await renderWithClient(<AccountScreen />);

  await fireEvent.changeText(screen.getByLabelText('Your password'), 'correct-horse');
  await fireEvent.press(screen.getByRole('button', { name: 'Delete my account' }));

  expect(remove).toHaveBeenCalledWith({ password: 'correct-horse' });
  expect(useAuthStore.getState().user).toBeNull();
  expect(useProgressStore.getState().lessons).toEqual({});
});

test('GitHub accounts confirm with their email', async () => {
  const remove = jest.spyOn(api, 'deleteAccount').mockResolvedValue(null);
  jest.spyOn(Alert, 'alert').mockImplementation((_title, _message, buttons) => buttons?.find((b) => b.style === 'destructive')?.onPress?.());
  useAuthStore.setState({ user: { ...user, has_password: false, github: true }, ready: true });
  await renderWithClient(<AccountScreen />);

  await fireEvent.changeText(screen.getByLabelText('Type your email address to confirm'), 'ada@example.com');
  await fireEvent.press(screen.getByRole('button', { name: 'Delete my account' }));

  expect(remove).toHaveBeenCalledWith({ confirmation: 'ada@example.com' });
});
