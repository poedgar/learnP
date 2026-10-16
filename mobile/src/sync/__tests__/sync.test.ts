import { api, ApiError } from '../../api/client';
import { updateLesson, useProgressStore } from '../../progress/store';
import { useReviewStore } from '../../review/store';
import { flushSync, startSync, stopSync } from '../sync';

const card = { due: 1000, interval: 1, ease: 2.5, reps: 1, lapses: 0 };
const signedOut = jest.fn();

afterEach(() => {
  stopSync({ clear: true });
  jest.restoreAllMocks();
});

test('the first sync with an account folds this device’s progress in and keeps what comes back', async () => {
  updateLesson(1, () => ({ read: true }));
  const merge = jest.spyOn(api, 'mergeProgress').mockResolvedValue({
    lessons: { 1: { read: true, sandboxCode: null, quiz: {}, challenges: {} }, 2: { read: true, sandboxCode: 'x', quiz: {}, challenges: {} } },
    cards: { 'quiz:11': card },
  });

  await startSync(7, { signedOut });

  expect(merge).toHaveBeenCalledWith({ lessons: { 1: expect.objectContaining({ read: true }) }, cards: {} });
  expect(useProgressStore.getState().lessons[2]).toEqual(expect.objectContaining({ read: true, sandboxCode: 'x', theoryPage: 1 }));
  expect(useReviewStore.getState().cards['quiz:11']).toEqual(card);
});

test('afterwards only what changed is pushed, and unpushed changes survive a pull', async () => {
  jest.spyOn(api, 'mergeProgress').mockResolvedValue({ lessons: {}, cards: {} });
  await startSync(7, { signedOut });

  const save = jest.spyOn(api, 'saveProgress').mockResolvedValue(null);
  updateLesson(1, () => ({ read: true }));
  updateLesson(2, () => ({ read: true }));
  await flushSync();
  expect(save).toHaveBeenLastCalledWith({ lessons: { 1: expect.anything(), 2: expect.anything() }, cards: {} });

  updateLesson(2, () => ({ sandboxCode: 'mine' }));
  await flushSync();
  expect(save).toHaveBeenLastCalledWith({ lessons: { 2: expect.objectContaining({ sandboxCode: 'mine' }) }, cards: {} });

  // Next launch, offline at first: lesson 3 changed here, lesson 1 changed on another device.
  stopSync({ clear: false });
  updateLesson(3, () => ({ sandboxCode: 'offline edit' }));
  save.mockRejectedValueOnce(new ApiError('Could not reach the server.', 0));
  jest.spyOn(api, 'progress').mockResolvedValue({
    lessons: {
      1: { read: true, sandboxCode: 'from the web', quiz: {}, challenges: {} },
      3: { read: false, sandboxCode: 'older', quiz: {}, challenges: {} },
    },
    cards: {},
  });
  await startSync(7, { signedOut });

  expect(useProgressStore.getState().lessons[1].sandboxCode).toBe('from the web');
  expect(useProgressStore.getState().lessons[3].sandboxCode).toBe('offline edit');
});

test('signing out clears the account’s progress from the device', async () => {
  jest.spyOn(api, 'mergeProgress').mockResolvedValue({ lessons: {}, cards: {} });
  await startSync(7, { signedOut });
  updateLesson(1, () => ({ read: true }));

  stopSync({ clear: true });

  expect(useProgressStore.getState().lessons).toEqual({});
});

test('an API token that stops working signs the app out', async () => {
  jest.spyOn(api, 'mergeProgress').mockRejectedValue(new ApiError('Unauthenticated.', 401));

  await startSync(7, { signedOut });

  expect(signedOut).toHaveBeenCalled();
});
