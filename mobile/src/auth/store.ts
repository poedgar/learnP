import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Platform } from 'react-native';
import { create } from 'zustand';
import { api, baseUrl } from '../api/client';
import type { User } from '../api/types';
import { flushSync, startSync, stopSync } from '../sync/sync';
import { clearToken, loadToken, saveToken } from './token';

/**
 * Accounts are optional. Signed in, a learner's progress follows them to other devices
 * and the web app; signed out, it stays on this device as before. The web app's
 * resources/js/auth.js does the same with a session cookie instead of a token.
 */
interface AuthState {
  user: User | null;
  /** False until the saved token has been checked, so the UI doesn't flash "Sign in". */
  ready: boolean;
}

export const useAuthStore = create<AuthState>(() => ({ user: null, ready: false }));

/** The name the token is listed under on the server. */
const DEVICE_NAME = `learnP app (${Platform.OS})`;

function signedIn(user: User): void {
  useAuthStore.setState({ user });
  startSync(user.id, { signedOut: signedOutElsewhere });
}

/** The token stopped working (revoked, or the account was deleted). */
function signedOutElsewhere(): void {
  useAuthStore.setState({ user: null });
  stopSync({ clear: false });
  clearToken().catch(() => {});
}

export async function loadUser(): Promise<void> {
  try {
    if (await loadToken()) {
      const user = await api.user();
      // No user for a saved token means it was revoked: drop it.
      if (user) signedIn(user);
      else await clearToken();
    }
  } catch {
    // Offline, the app works signed out until the next launch.
  } finally {
    useAuthStore.setState({ ready: true });
  }
}

export async function register(details: { name: string; email: string; password: string; password_confirmation: string }) {
  const { user, token } = await api.register({ ...details, device_name: DEVICE_NAME });
  await saveToken(token);
  signedIn(user);
}

export async function signIn(credentials: { email: string; password: string }) {
  const { user, token } = await api.login({ ...credentials, device_name: DEVICE_NAME });
  await saveToken(token);
  signedIn(user);
}

/** Fetches the user again, for example to see whether their email has been confirmed. */
export async function refreshUser(): Promise<void> {
  const user = await api.user();
  if (user) useAuthStore.setState({ user });
}

/**
 * Signs in with GitHub in an in-app browser. The server sends the browser back to this app's
 * own URL with a one-time code, which is exchanged for an API token. Returns false if the
 * person closed the browser.
 */
export async function signInWithGithub(): Promise<boolean> {
  const returnTo = Linking.createURL('auth/github');
  const result = await WebBrowser.openAuthSessionAsync(
    `${baseUrl()}/auth/github?return_to=${encodeURIComponent(returnTo)}`,
    returnTo,
  );
  if (result.type !== 'success') return false;

  const { queryParams } = Linking.parse(result.url);
  if (typeof queryParams?.error === 'string') throw new Error(GITHUB_ERRORS[queryParams.error] ?? GITHUB_ERRORS['github-failed']);
  if (typeof queryParams?.code !== 'string') throw new Error(GITHUB_ERRORS['github-failed']);

  const { user, token } = await api.exchangeGithubCode(queryParams.code, DEVICE_NAME);
  await saveToken(token);
  signedIn(user);

  return true;
}

const GITHUB_ERRORS: Record<string, string> = {
  'github-email': 'GitHub did not share an email address with us. Make one visible to apps in your GitHub settings, then try again.',
  'github-linked': 'That email address already belongs to an account linked to a different GitHub account.',
  'github-failed': 'GitHub sign-in did not finish. Please try again.',
};

/** Deletes the account for good; this device's copy of its progress goes with it. */
export async function deleteAccount(confirmation: { password: string } | { confirmation: string }): Promise<void> {
  await api.deleteAccount(confirmation);
  await clearToken();
  useAuthStore.setState({ user: null });
  stopSync({ clear: true });
}

export async function signOut(): Promise<void> {
  await flushSync();
  try {
    await api.logout();
  } catch {
    // Offline: the token is forgotten here either way.
  }
  await clearToken();
  useAuthStore.setState({ user: null });
  stopSync({ clear: true });
}
