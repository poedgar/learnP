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

export async function signOut() {
    await flushSync();
    await api.logout();
    auth.user = null;
    stopSync({ clear: true });
}
