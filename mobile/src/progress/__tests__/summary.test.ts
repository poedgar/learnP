import { EMPTY_PROGRESS, type LessonProgress } from '../store';
import { lessonSummary } from '../summary';

const lesson = { id: 1, quiz_questions_count: 2, challenges_count: 2 };

function progress(overrides: Partial<LessonProgress>): LessonProgress {
  return { ...EMPTY_PROGRESS, ...overrides };
}

test('a lesson nobody has opened is not started', () => {
  expect(lessonSummary(lesson, undefined)).toEqual({
    read: false,
    quizAnswered: 0,
    quizCorrect: 0,
    quizTotal: 2,
    challengesPassed: 0,
    challengeTotal: 2,
    complete: false,
    started: false,
  });
});

test('reading starts a lesson', () => {
  expect(lessonSummary(lesson, progress({ read: true })).started).toBe(true);
});

test('counts answered and correct quiz questions and passed challenges', () => {
  const summary = lessonSummary(
    lesson,
    progress({
      quiz: {
        1: { option: 0, correct: true, correct_option: 0, explanation: '' },
        2: { option: 1, correct: false, correct_option: 0, explanation: '' },
      },
      challenges: { 5: { passed: true, code: 'x' }, 6: { passed: false, code: 'y' } },
    }),
  );

  expect(summary.quizAnswered).toBe(2);
  expect(summary.quizCorrect).toBe(1);
  expect(summary.challengesPassed).toBe(1);
  expect(summary.started).toBe(true);
  expect(summary.complete).toBe(false);
});

test('complete needs reading, every question answered and every challenge passed', () => {
  const summary = lessonSummary(
    lesson,
    progress({
      read: true,
      quiz: {
        1: { option: 0, correct: true, correct_option: 0, explanation: '' },
        2: { option: 1, correct: false, correct_option: 0, explanation: '' },
      },
      challenges: { 5: { passed: true, code: null }, 6: { passed: true, code: null } },
    }),
  );

  expect(summary.complete).toBe(true);
});

test('uses list lengths when counts are missing', () => {
  const full = { id: 1, quiz_questions: [{}, {}, {}], challenges: [{}] };

  expect(lessonSummary(full, undefined)).toMatchObject({ quizTotal: 3, challengeTotal: 1 });
});
