import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import { schedule, type Card, type Grade } from './schedule';

/**
 * Review schedules live on the device, next to progress but separate from it:
 * reviewing never changes a lesson's quiz answers or saved code. The shape matches
 * the web app's resources/js/review.js.
 */
export const REVIEW_STORAGE_KEY = 'learnp:review:v1';

interface ReviewState {
  cards: Record<string, Card>;
  grade: (key: string, grade: Grade, now?: number) => void;
}

export const useReviewStore = create<ReviewState>()(
  persist(
    (set) => ({
      cards: {},
      grade: (key, grade, now = Date.now()) =>
        set((state) => ({ cards: { ...state.cards, [key]: schedule(state.cards[key], grade, now) } })),
    }),
    {
      name: REVIEW_STORAGE_KEY,
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ cards: state.cards }),
    },
  ),
);

export function replaceCard(key: string, card: Card): void {
  useReviewStore.setState((state) => ({ cards: { ...state.cards, [key]: card } }));
}

export function clearCards(): void {
  useReviewStore.setState({ cards: {} });
}
