import { dayKey, recordVisit, streak, useActivityStore } from '../activity';

const recent = { course: 'sql', courseTitle: 'SQL', lesson: 'your-first-queries', lessonTitle: 'Your first queries' };

afterEach(() => useActivityStore.setState({ recent: null, days: [] }));

test('days in a row count, ending today or yesterday', () => {
  const now = new Date(2026, 9, 21, 12);
  expect(streak(['2026-10-19', '2026-10-20', '2026-10-21'], now)).toBe(3);
  expect(streak(['2026-10-19', '2026-10-20'], now)).toBe(2);
  expect(streak(['2026-10-18', '2026-10-20', '2026-10-21'], now)).toBe(2);
  expect(streak(['2026-10-10'], now)).toBe(0);
  expect(streak([], now)).toBe(0);
});

test('streaks cross month ends', () => {
  expect(streak(['2026-09-30', '2026-10-01'], new Date(2026, 9, 1, 9))).toBe(2);
});

test('a visit is the recent lesson and marks the day once', () => {
  const now = new Date(2026, 9, 21, 9);
  recordVisit(recent, now);
  recordVisit(recent, now);

  expect(useActivityStore.getState()).toMatchObject({ recent, days: [dayKey(now)] });
});
