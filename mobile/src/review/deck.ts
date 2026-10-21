import { useMemo } from 'react';
import { useNow } from '../lib/useNow';
import { useProgressStore } from '../progress/store';
import { dueItems, passedItems, type ReviewItem } from './schedule';
import { useReviewStore } from './store';

/** Everything the learner has passed, and which of it is due now. */
export function useReviewDeck(givenNow?: number) {
  const ticking = useNow();
  const now = givenNow ?? ticking;
  const lessons = useProgressStore((state) => state.lessons);
  const cards = useReviewStore((state) => state.cards);
  const items = useMemo(() => passedItems(lessons), [lessons]);
  const due = useMemo(() => dueItems(items, cards, now), [items, cards, now]);

  return { items, cards, due };
}

export type { ReviewItem };
