import AsyncStorage from '@react-native-async-storage/async-storage';
import { AppState, type NativeEventSubscription } from 'react-native';
import { api, ApiError } from '../api/client';
import type { ProgressState } from '../api/types';
import { clearProgress, replaceLesson, useProgressStore, type LessonProgress } from '../progress/store';
import type { Card } from '../review/schedule';
import { clearCards, replaceCard, useReviewStore } from '../review/store';

/**
 * Keeps a signed-in learner's progress and review cards in step with the server.
 * The device copy stays the one the app reads and writes: this pushes what changed
 * here and pulls what other devices saved. A lesson or card changed here and not yet
 * pushed keeps the local copy. The web app's resources/js/sync.js does the same.
 *
 * `synced` records, as JSON, each lesson and card as the server last had it, so local
 * changes show up as differences, even after a restart while offline. `account` is
 * whose progress it is: a different account means folding this device's progress in.
 */
export const SYNCED_STORAGE_KEY = 'learnp:synced:v1';
const PUSH_DELAY_MS = 1500;
const RETRY_DELAY_MS = 30_000;

interface Synced {
  account: number | null;
  lessons: Record<string, string>;
  cards: Record<string, string>;
}

type Changes = Pick<Synced, 'lessons' | 'cards'>;

const empty = (): Synced => ({ account: null, lessons: {}, cards: {} });

let synced = empty();
let unsubscribe: (() => void) | null = null;
let appState: NativeEventSubscription | null = null;
let timer: ReturnType<typeof setTimeout> | undefined;
let pushing = false;
let pushAgain = false;
let onSignedOut = () => {};

async function load(): Promise<Synced> {
  try {
    const saved = JSON.parse((await AsyncStorage.getItem(SYNCED_STORAGE_KEY)) ?? 'null');
    if (saved && typeof saved.lessons === 'object' && typeof saved.cards === 'object') return saved;
  } catch {
    // Invalid JSON; everything counts as unsynced.
  }
  return empty();
}

function save(): void {
  // Unsaved, every lesson looks changed after a restart and is pushed again: harmless.
  AsyncStorage.setItem(SYNCED_STORAGE_KEY, JSON.stringify(synced)).catch(() => {});
}

/** The stores load from AsyncStorage after launch; syncing before that would see nothing. */
function hydrated(store: typeof useProgressStore | typeof useReviewStore): Promise<void> {
  if (store.persist.hasHydrated()) return Promise.resolve();
  return new Promise((resolve) => {
    const stop = store.persist.onFinishHydration(() => {
      stop();
      resolve();
    });
  });
}

const localLessons = () => useProgressStore.getState().lessons;
const localCards = () => useReviewStore.getState().cards;

/** Lessons and cards that differ from the server's copy, as JSON. */
function changes(): Changes {
  const pick = (local: Record<string, unknown>, known: Record<string, string>) =>
    Object.fromEntries(
      Object.entries(local)
        .map(([key, value]) => [key, JSON.stringify(value)])
        .filter(([key, json]) => known[key] !== json),
    );
  return { lessons: pick(localLessons(), synced.lessons), cards: pick(localCards(), synced.cards) };
}

const parsed = (entries: Record<string, string>) =>
  Object.fromEntries(Object.entries(entries).map(([key, json]) => [key, JSON.parse(json)]));

function schedulePush(delay = PUSH_DELAY_MS): void {
  clearTimeout(timer);
  timer = setTimeout(push, delay);
}

function failed(error: unknown): void {
  if (error instanceof ApiError && error.status === 401) {
    onSignedOut();
  } else if (unsubscribe) {
    schedulePush(RETRY_DELAY_MS);
  }
}

async function push(): Promise<void> {
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
function apply(remote: ProgressState, keepLocalChanges: boolean): void {
  const local: Changes = keepLocalChanges ? changes() : { lessons: {}, cards: {} };

  for (const [lessonId, lesson] of Object.entries(remote.lessons)) {
    if (lessonId in local.lessons) continue;
    replaceLesson(Number(lessonId), lesson as Partial<LessonProgress>);
    synced.lessons[lessonId] = JSON.stringify(localLessons()[Number(lessonId)]);
  }
  for (const [key, card] of Object.entries(remote.cards)) {
    if (key in local.cards) continue;
    replaceCard(key, card as Card);
    synced.cards[key] = JSON.stringify(localCards()[key]);
  }
  save();
}

async function pull(): Promise<void> {
  try {
    apply(await api.progress(), true);
  } catch (error) {
    failed(error);
  }
}

/**
 * Starts syncing for a signed-in user. The first time this device syncs with an
 * account, its progress is folded into the account's rather than replacing it.
 */
export async function startSync(userId: number, { signedOut }: { signedOut: () => void }): Promise<void> {
  onSignedOut = signedOut;
  stopListening();
  await Promise.all([hydrated(useProgressStore), hydrated(useReviewStore)]);
  synced = await load();

  const subscriptions = [
    useProgressStore.subscribe((state, previous) => state.lessons !== previous.lessons && schedulePush()),
    useReviewStore.subscribe((state, previous) => state.cards !== previous.cards && schedulePush()),
  ];
  unsubscribe = () => subscriptions.forEach((stop) => stop());
  appState = AppState.addEventListener('change', (state) => {
    if (state === 'active') push().then(pull);
  });

  if (synced.account === userId) {
    await push();
    await pull();
    return;
  }

  try {
    const remote = await api.mergeProgress({ lessons: localLessons(), cards: localCards() });
    synced = { ...empty(), account: userId };
    apply(remote, false);
  } catch (error) {
    failed(error);
  }
}

/** Sends anything not yet saved, so signing out loses nothing. */
export async function flushSync(): Promise<void> {
  clearTimeout(timer);
  await push();
}

function stopListening(): void {
  clearTimeout(timer);
  unsubscribe?.();
  unsubscribe = null;
  appState?.remove();
  appState = null;
}

/** Stops syncing and, on sign-out, clears the account's progress from this device. */
export function stopSync({ clear }: { clear: boolean }): void {
  stopListening();

  if (clear) {
    clearProgress();
    clearCards();
    synced = empty();
    save();
  }
}
