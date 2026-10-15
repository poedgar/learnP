import type { LessonProgress } from '../progress/store';

/**
 * Spaced repetition with SM-2, the algorithm behind Anki. The same rules are in the
 * web app's resources/js/review.js; keep the two in step.
 */
export type Grade = 'again' | 'hard' | 'good' | 'easy';

export const GRADES: Grade[] = ['again', 'hard', 'good', 'easy'];

export interface Card {
  /** When the card is next due, in milliseconds since 1970. */
  due: number;
  /** Days until the next review; 0 while relearning after "again". */
  interval: number;
  ease: number;
  /** Successful reviews in a row. */
  reps: number;
  lapses: number;
}

export type ItemKind = 'quiz' | 'challenge';

export interface ReviewItem {
  key: string;
  kind: ItemKind;
  id: number;
  lessonId: number;
}

export const STARTING_EASE = 2.5;
const MINIMUM_EASE = 1.3;
const AGAIN_DELAY_MS = 10 * 60_000;

export const itemKey = (kind: ItemKind, id: number) => `${kind}:${id}`;

/** Local midnight, `days` days after `now`, so a card is due all day rather than from a set minute. */
function startOfDayAfter(now: number, days: number): number {
  const date = new Date(now);
  date.setHours(0, 0, 0, 0);
  date.setDate(date.getDate() + days);
  return date.getTime();
}

export function schedule(card: Card | undefined, grade: Grade, now: number): Card {
  const current = card ?? { due: now, interval: 0, ease: STARTING_EASE, reps: 0, lapses: 0 };
  const ease = (change: number) => Math.max(MINIMUM_EASE, current.ease + change);

  if (grade === 'again') {
    return {
      due: now + AGAIN_DELAY_MS,
      interval: 0,
      ease: current.reps > 0 ? ease(-0.2) : current.ease,
      reps: 0,
      lapses: current.reps > 0 ? current.lapses + 1 : current.lapses,
    };
  }

  // New and relearning cards step up to a first interval; others grow from the last one.
  let interval: number;
  if (current.reps === 0) {
    interval = grade === 'easy' ? 4 : 1;
  } else if (grade === 'hard') {
    interval = Math.max(current.interval + 1, Math.round(current.interval * 1.2));
  } else if (grade === 'good') {
    interval = Math.max(current.interval + 1, Math.round(current.interval * current.ease));
  } else {
    interval = Math.max(current.interval + 1, Math.round(current.interval * current.ease * 1.3));
  }

  return {
    due: startOfDayAfter(now, interval),
    interval,
    ease: grade === 'hard' ? ease(-0.15) : grade === 'easy' ? ease(0.15) : current.ease,
    reps: current.reps + 1,
    lapses: current.lapses,
  };
}

/** How long until a time, in words: "10 min", "1 day", "3 mo". */
export function describeWait(from: number, to: number): string {
  const minutes = Math.max(1, Math.round((to - from) / 60_000));
  if (minutes < 60) return `${minutes} min`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} h`;
  const days = Math.round(hours / 24);
  if (days < 30) return `${days} ${days === 1 ? 'day' : 'days'}`;
  if (days < 365) return `${Math.round(days / 30)} mo`;
  return `${Math.round((days / 365) * 10) / 10} yr`;
}

/** When a due date falls, in words: "in 10 min" (later today), "tomorrow", "in 4 days". */
export function describeDue(now: number, due: number): string {
  const days = Math.round((startOfDayAfter(due, 0) - startOfDayAfter(now, 0)) / 86_400_000);
  if (days <= 0) return `in ${describeWait(now, due)}`;
  if (days === 1) return 'tomorrow';
  return `in ${describeWait(0, days * 86_400_000)}`;
}

/** What a grade would mean for this card, for the label under each grade button: "10 min", "4 days". */
export function previewWait(card: Card | undefined, grade: Grade, now: number): string {
  const next = schedule(card, grade, now);
  // Due dates fall on midnight, so count whole days rather than hours until then.
  return next.interval > 0 ? describeWait(0, next.interval * 86_400_000) : describeWait(now, next.due);
}

/** Every quiz question answered correctly and every challenge passed, in lesson order. */
export function passedItems(lessons: Record<number, LessonProgress>): ReviewItem[] {
  return Object.entries(lessons).flatMap(([lessonKey, progress]) => {
    const lessonId = Number(lessonKey);
    const quiz = Object.entries(progress.quiz ?? {})
      .filter(([, answer]) => answer.correct)
      .map(([id]) => ({ key: itemKey('quiz', Number(id)), kind: 'quiz' as const, id: Number(id), lessonId }));
    const challenges = Object.entries(progress.challenges ?? {})
      .filter(([, challenge]) => challenge.passed)
      .map(([id]) => ({ key: itemKey('challenge', Number(id)), kind: 'challenge' as const, id: Number(id), lessonId }));
    return [...quiz, ...challenges];
  });
}

/** Items due now: overdue reviews first (most overdue first), then items never reviewed. */
export function dueItems<T extends { key: string }>(items: T[], cards: Record<string, Card>, now: number): T[] {
  const reviews = items
    .filter((item) => cards[item.key] && cards[item.key].due <= now)
    .sort((a, b) => cards[a.key].due - cards[b.key].due);
  const fresh = items.filter((item) => !cards[item.key]);
  return [...reviews, ...fresh];
}

/** When the next item that isn't due yet becomes due, or null. */
export function nextDue(items: { key: string }[], cards: Record<string, Card>, now: number): number | null {
  const upcoming = items.map((item) => cards[item.key]?.due).filter((due): due is number => due !== undefined && due > now);
  return upcoming.length ? Math.min(...upcoming) : null;
}
