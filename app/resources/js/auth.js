import { reactive } from 'vue';
import { api } from './api';
import { flushSync, startSync, stopSync } from './sync';

/*
 * Accounts are optional. Signed in, a learner's progress follows them to other
 * browsers and the mobile app; signed out, it stays in this browser as before.
 * The session lives in Laravel's cookie, so there is no token to keep here.
 */
export const auth = reactive({ user: null, ready: false });

function signedIn(user) {
    auth.user = user;
    startSync(user, { signedOut: signedOutElsewhere });
}

/** The session ended on the server (it expired, or the account signed out everywhere). */
function signedOutElsewhere() {
    auth.user = null;
    stopSync({ clear: false });
}

export async function loadUser() {
    try {
        const user = await api.user();
        if (user) signedIn(user);
    } catch {
        // Unreachable server: the app stays usable without an account.
    } finally {
        auth.ready = true;
    }
}

export async function register(details) {
    signedIn(await api.register(details));
}

export async function signIn(credentials) {
    signedIn(await api.login(credentials));
}

/** Fetches the user again, for example after their email address was confirmed. */
export async function refreshUser() {
    const user = await api.user();
    if (user) auth.user = user;
}

/** Deletes the account for good; this browser's copy of its progress goes with it. */
export async function deleteAccount(confirmation) {
    await api.deleteAccount(confirmation);
    auth.user = null;
    stopSync({ clear: true });
}

export async function signOut() {
    await flushSync();
    await api.logout();
    auth.user = null;
    stopSync({ clear: true });
}
