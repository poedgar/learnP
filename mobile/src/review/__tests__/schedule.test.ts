import type { LessonProgress } from '../../progress/store';
import { describeDue, describeWait, dueItems, nextDue, passedItems, previewWait, schedule, STARTING_EASE, type Card } from '../schedule';

// Midday, so "local midnight N days later" is unambiguous in any time zone.
const now = new Date(2026, 3, 8, 12, 0, 0).getTime();
const midnightAfter = (days: number) => new Date(2026, 3, 8 + days).getTime();

const card = (overrides: Partial<Card> = {}): Card => ({
  due: now,
  interval: 10,
  ease: STARTING_EASE,
  reps: 3,
  lapses: 0,
  ...overrides,
});

test('a new card answered good comes back tomorrow, easy in four days', () => {
  expect(schedule(undefined, 'good', now)).toEqual({ due: midnightAfter(1), interval: 1, ease: 2.5, reps: 1, lapses: 0 });
  expect(schedule(undefined, 'easy', now)).toMatchObject({ due: midnightAfter(4), interval: 4, ease: 2.65 });
  expect(schedule(undefined, 'hard', now)).toMatchObject({ interval: 1, ease: 2.35 });
});

test('a reviewed card grows by its ease', () => {
  expect(schedule(card(), 'good', now)).toMatchObject({ interval: 25, ease: 2.5, reps: 4, due: midnightAfter(25) });
  expect(schedule(card(), 'hard', now)).toMatchObject({ interval: 12, ease: 2.35 });
  expect(schedule(card(), 'easy', now)).toMatchObject({ interval: 33, ease: 2.65 });
});

test('intervals always grow by at least a day', () => {
  expect(schedule(card({ interval: 1, ease: 1.3 }), 'hard', now).interval).toBe(2);
  expect(schedule(card({ interval: 1, ease: 1.3 }), 'good', now).interval).toBe(2);
});

test('again brings a card back in ten minutes and counts a lapse', () => {
  expect(schedule(card(), 'again', now)).toEqual({ due: now + 10 * 60_000, interval: 0, ease: 2.3, reps: 0, lapses: 1 });
  // Missing a card that was never learned isn't a lapse.
  expect(schedule(undefined, 'again', now)).toMatchObject({ ease: 2.5, lapses: 0, reps: 0 });
});

test('after a lapse the card starts again from a one-day interval', () => {
  const relearning = schedule(card(), 'again', now);
  expect(schedule(relearning, 'good', now)).toMatchObject({ interval: 1, reps: 1, lapses: 1, ease: 2.3 });
});

test('ease never drops below 1.3', () => {
  expect(schedule(card({ ease: 1.35 }), 'again', now).ease).toBe(1.3);
  expect(schedule(card({ ease: 1.3 }), 'hard', now).ease).toBe(1.3);
});

test('waits are described in words', () => {
  expect(describeWait(now, now + 10 * 60_000)).toBe('10 min');
  expect(describeWait(now, now + 5 * 3_600_000)).toBe('5 h');
  expect(describeWait(now, now + 86_400_000)).toBe('1 day');
  expect(describeWait(now, now + 12 * 86_400_000)).toBe('12 days');
  expect(describeWait(now, now + 90 * 86_400_000)).toBe('3 mo');
  expect(previewWait(undefined, 'again', now)).toBe('10 min');
  expect(previewWait(undefined, 'good', now)).toBe('1 day');
  expect(previewWait(card(), 'easy', now)).toBe('1 mo');
});

test('due dates are described by calendar day', () => {
  expect(describeDue(now, now + 10 * 60_000)).toBe('in 10 min');
  expect(describeDue(now, midnightAfter(1))).toBe('tomorrow');
  expect(describeDue(now, midnightAfter(4))).toBe('in 4 days');
});

test('passed items are correct quiz answers and passed challenges', () => {
  const lessons: Record<number, LessonProgress> = {
    3: {
      read: true,
      theoryPage: 1,
      sandboxCode: null,
      quiz: {
        11: { option: 1, correct: true, correct_option: 1, explanation: '' },
        12: { option: 0, correct: false, correct_option: 1, explanation: '' },
      },
      challenges: { 21: { passed: true, code: 'SELECT 1' }, 22: { passed: false, code: 'SELECT' } },
    },
  };

  expect(passedItems(lessons)).toEqual([
    { key: 'quiz:11', kind: 'quiz', id: 11, lessonId: 3 },
    { key: 'challenge:21', kind: 'challenge', id: 21, lessonId: 3 },
  ]);
});

test('due items are overdue reviews, most overdue first, then new items', () => {
  const items = ['quiz:1', 'quiz:2', 'quiz:3', 'quiz:4'].map((key) => ({ key }));
  const cards = {
    'quiz:1': card({ due: now - 1000 }),
    'quiz:2': card({ due: now + 86_400_000 }),
    'quiz:3': card({ due: now - 5000 }),
  };

  expect(dueItems(items, cards, now).map((item) => item.key)).toEqual(['quiz:3', 'quiz:1', 'quiz:4']);
  expect(nextDue(items, cards, now)).toBe(now + 86_400_000);
  expect(nextDue(items, {}, now)).toBeNull();
});
