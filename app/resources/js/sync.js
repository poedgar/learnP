import { watch } from 'vue';
import { api } from './api';
import { allProgress, clearProgress, replaceLesson } from './progress';
import { clearCards, replaceCard, reviewCards } from './review';

/*
 * Keeps a signed-in learner's progress and review cards in step with the server.
 * The browser copy stays the one the app reads and writes: this pushes what changed
 * here and pulls what other devices saved. A lesson or card changed here and not yet
 * pushed keeps the local copy. The mobile app's src/sync/sync.ts does the same.
 *
 * `synced` records, as JSON, each lesson and card as the server last had it, so local
 * changes show up as differences, even after a reload while offline. `account` is
 * whose progress it is: a different account means folding this browser's progress in.
 */
const STORAGE_KEY = 'learnp:synced:v1';
const PUSH_DELAY_MS = 1500;
const RETRY_DELAY_MS = 30_000;

const empty = () => ({ account: null, lessons: {}, cards: {} });

function load() {
    try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
        if (saved && typeof saved.lessons === 'object' && typeof saved.cards === 'object') {
            return saved;
        }
    } catch {
        // Storage can be unavailable or hold invalid JSON; everything counts as unsynced.
    }
    return empty();
}

let synced = load();
let stopWatching = null;
let timer = null;
let pushing = false;
let pushAgain = false;
let onSignedOut = () => {};

function save() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(synced));
    } catch {
        // Unsaved, every lesson looks changed after a reload and is pushed again: harmless.
    }
}

/** Lessons and cards that differ from the server's copy, as JSON. */
function changes() {
    const pick = (local, known) =>
        Object.fromEntries(
            Object.entries(local)
                .map(([key, value]) => [key, JSON.stringify(value)])
                .filter(([key, json]) => known[key] !== json),
        );
    return { lessons: pick(allProgress, synced.lessons), cards: pick(reviewCards, synced.cards) };
}

const parsed = (entries) => Object.fromEntries(Object.entries(entries).map(([key, json]) => [key, JSON.parse(json)]));

function schedulePush(delay = PUSH_DELAY_MS) {
    clearTimeout(timer);
    timer = setTimeout(push, delay);
}

function failed(error) {
    if (error.status === 401) {
        onSignedOut();
    } else if (stopWatching) {
        schedulePush(RETRY_DELAY_MS);
    }
}

async function push() {
    if (pushing) {
        pushAgain = true;
        return;
    }
    const pending = changes();
    if (!Object.keys(pending.lessons).length && !Object.keys(pending.cards).length) return;

    pushing = true;
    try {
        await api.saveProgress({ lessons: parsed(pending.lessons), cards: parsed(pending.cards) });
        Object.assign(synced.lessons, pending.lessons);
        Object.assign(synced.cards, pending.cards);
        save();
    } catch (error) {
        failed(error);
    } finally {
        pushing = false;
        if (pushAgain) {
            pushAgain = false;
            schedulePush(0);
        }
    }
}

/** Takes in the server's copy of everything not changed here since the last exchange. */
function apply(remote, { keepLocalChanges }) {
    const local = keepLocalChanges ? changes() : { lessons: {}, cards: {} };

    for (const [lessonId, lesson] of Object.entries(remote.lessons)) {
        if (lessonId in local.lessons) continue;
        replaceLesson(Number(lessonId), lesson);
        synced.lessons[lessonId] = JSON.stringify(allProgress[lessonId]);
    }
    for (const [key, card] of Object.entries(remote.cards)) {
        if (key in local.cards) continue;
        replaceCard(key, card);
        synced.cards[key] = JSON.stringify(reviewCards[key]);
    }
    save();
}

async function pull() {
    try {
        apply(await api.progress(), { keepLocalChanges: true });
    } catch (error) {
        failed(error);
    }
}

function onVisible() {
    if (document.visibilityState === 'visible') {
        push().then(pull);
    }
}

/**
 * Starts syncing for a signed-in user. The first time this browser syncs with an
 * account, its progress is folded into the account's rather than replacing it.
 */
export async function startSync(user, { signedOut }) {
    onSignedOut = signedOut;
    stopWatching?.();
    stopWatching = watch([allProgress, reviewCards], () => schedulePush(), { deep: true });
    document.addEventListener('visibilitychange', onVisible);

    if (synced.account === user.id) {
        await push();
        await pull();
        return;
    }

    try {
        const remote = await api.mergeProgress({ lessons: allProgress, cards: reviewCards });
        synced = { ...empty(), account: user.id };
        apply(remote, { keepLocalChanges: false });
    } catch (error) {
        failed(error);
    }
}

/** Sends anything not yet saved, so signing out loses nothing. */
export async function flushSync() {
    clearTimeout(timer);
    await push();
}

/** Stops syncing and, on sign-out, clears the account's progress from this browser. */
export function stopSync({ clear }) {
    clearTimeout(timer);
    stopWatching?.();
    stopWatching = null;
    document.removeEventListener('visibilitychange', onVisible);

    if (clear) {
        clearProgress();
        clearCards();
        synced = empty();
        save();
    }
}
