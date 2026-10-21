import type { LessonProgress } from './store';

/** The same rules as lessonSummary() in the web app's resources/js/progress.js. */
export interface LessonCounts {
  id: number;
  quiz_questions_count?: number;
  challenges_count?: number;
  quiz_questions?: unknown[];
  challenges?: unknown[];
}

export interface LessonSummary {
  read: boolean;
  quizAnswered: number;
  quizCorrect: number;
  quizTotal: number;
  challengesPassed: number;
  challengeTotal: number;
  complete: boolean;
  started: boolean;
}

/** How much of a course is done, from its lessons' ids and counts. Same rules as courseProgress() on the web. */
export function courseProgress(
  lessons: LessonCounts[] | undefined,
  progress: Record<number, LessonProgress>,
): { completed: number; total: number; started: boolean; complete: boolean } {
  const list = lessons ?? [];
  const summaries = list.map((lesson) => lessonSummary(lesson, progress[lesson.id]));
  const completed = summaries.filter((s) => s.complete).length;

  return {
    completed,
    total: list.length,
    started: summaries.some((s) => s.started),
    complete: list.length > 0 && completed === list.length,
  };
}

export function lessonSummary(lesson: LessonCounts, progress: LessonProgress | undefined): LessonSummary {
  const quiz = Object.values(progress?.quiz ?? {});
  const quizAnswered = quiz.length;
  const quizCorrect = quiz.filter((answer) => answer.correct).length;
  const challengesPassed = Object.values(progress?.challenges ?? {}).filter((c) => c.passed).length;
  const quizTotal = lesson.quiz_questions_count ?? lesson.quiz_questions?.length ?? 0;
  const challengeTotal = lesson.challenges_count ?? lesson.challenges?.length ?? 0;
  const read = progress?.read ?? false;

  return {
    read,
    quizAnswered,
    quizCorrect,
    quizTotal,
    challengesPassed,
    challengeTotal,
    complete: read && quizAnswered === quizTotal && challengesPassed === challengeTotal,
    started: Boolean(progress && (read || quizAnswered || challengesPassed)),
  };
}
