import AsyncStorage from '@react-native-async-storage/async-storage';
import { act, renderHook } from '@testing-library/react-native';
import { EMPTY_PROGRESS, STORAGE_KEY, updateLesson, useLessonProgress, useProgressStore } from '../store';

test('an unopened lesson reads as empty progress', async () => {
  const { result } = await renderHook(() => useLessonProgress(42));

  expect(result.current).toEqual(EMPTY_PROGRESS);
});

test('updateLesson merges changes into the lesson', async () => {
  const { result } = await renderHook(() => useLessonProgress(3));

  await act(async () => updateLesson(3, () => ({ read: true })));
  await act(async () => updateLesson(3, (current) => ({ quiz: { ...current.quiz, 9: { option: 1, correct: true, correct_option: 1, explanation: 'Yes.' } } })));

  expect(result.current.read).toBe(true);
  expect(result.current.quiz[9].correct).toBe(true);
  expect(result.current.sandboxCode).toBeNull();
});

test('progress is saved to AsyncStorage under learnp:progress:v2', async () => {
  await act(async () => updateLesson(5, () => ({ sandboxCode: 'SELECT 1' })));

  const saved = JSON.parse((await AsyncStorage.getItem(STORAGE_KEY)) ?? 'null');

  expect(STORAGE_KEY).toBe('learnp:progress:v2');
  expect(saved.state.lessons['5'].sandboxCode).toBe('SELECT 1');
  expect(Object.keys(saved.state)).toEqual(['lessons']);
});

test('the store starts from what AsyncStorage holds', async () => {
  await AsyncStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({ state: { lessons: { 8: { ...EMPTY_PROGRESS, read: true } } }, version: 0 }),
  );

  await act(async () => {
    await useProgressStore.persist.rehydrate();
  });

  expect(useProgressStore.getState().lessons[8].read).toBe(true);
});
