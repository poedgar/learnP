import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import type { QuizAnswer } from '../api/types';

/**
 * Learner progress lives on the device. Signed-in learners also keep a copy on the
 * server (see src/sync), but the app always reads and writes this one. The lesson
 * shape matches the web app's resources/js/progress.js.
 */
export interface QuizRecord extends QuizAnswer {
  option: number;
}

export interface ChallengeRecord {
  passed: boolean;
  code: string | null;
}

export interface LessonProgress {
  read: boolean;
  theoryPage: number;
  sandboxCode: string | null;
  quiz: Record<number, QuizRecord>;
  challenges: Record<number, ChallengeRecord>;
}

export const STORAGE_KEY = 'learnp:progress:v2';

export const EMPTY_PROGRESS: LessonProgress = Object.freeze({
  read: false,
  theoryPage: 1,
  sandboxCode: null,
  quiz: {},
  challenges: {},
});

interface ProgressState {
  lessons: Record<number, LessonProgress>;
  update: (lessonId: number, change: (current: LessonProgress) => Partial<LessonProgress>) => void;
}

export const useProgressStore = create<ProgressState>()(
  persist(
    (set) => ({
      lessons: {},
      update: (lessonId, change) =>
        set((state) => {
          const current = state.lessons[lessonId] ?? EMPTY_PROGRESS;
          return { lessons: { ...state.lessons, [lessonId]: { ...current, ...change(current) } } };
        }),
    }),
    {
      name: STORAGE_KEY,
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ lessons: state.lessons }),
    },
  ),
);

/** A lesson nobody has opened reads as EMPTY_PROGRESS (a stable object, so no re-render loops). */
export function useLessonProgress(lessonId: number): LessonProgress {
  return useProgressStore((state) => state.lessons[lessonId]) ?? EMPTY_PROGRESS;
}

export function updateLesson(
  lessonId: number,
  change: (current: LessonProgress) => Partial<LessonProgress>,
): void {
  useProgressStore.getState().update(lessonId, change);
}

/** Takes in a lesson's progress saved elsewhere (the server); missing fields get their defaults. */
export function replaceLesson(lessonId: number, data: Partial<LessonProgress>): void {
  useProgressStore.setState((state) => ({ lessons: { ...state.lessons, [lessonId]: { ...EMPTY_PROGRESS, ...data } } }));
}

export function clearProgress(): void {
  useProgressStore.setState({ lessons: {} });
}
