import { Platform } from 'react-native';
import { create } from 'zustand';
import { api } from '../api/client';
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
