import type {
  Course,
  CourseSummary,
  LessonResponse,
  PhpCheck,
  PhpRun,
  QuizAnswer,
  SqlCheck,
  SqlRun,
} from './types';

/** Error messages match the web app's resources/js/api.js. */
export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

function baseUrl(): string {
  return (process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000').replace(/\/+$/, '');
}

async function request<T>(method: 'GET' | 'POST', path: string, body?: unknown): Promise<T> {
  let response: Response;

  try {
    response = await fetch(`${baseUrl()}/api/${path}`, {
      method,
      headers: {
        Accept: 'application/json',
        ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      },
      body: body === undefined ? undefined : JSON.stringify(body),
    });
  } catch {
    throw new ApiError('Could not reach the server. Check your connection and try again.', 0);
  }

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    const message =
      response.status === 429
        ? 'You are running queries very quickly. Wait a few seconds, then try again.'
        : (data?.message ?? `The server responded with an error (${response.status}).`);
    throw new ApiError(message, response.status);
  }

  return data as T;
}

const segment = encodeURIComponent;

export const api = {
  courses: () => request<{ data: CourseSummary[] }>('GET', 'courses').then((r) => r.data),
  course: (slug: string) => request<{ data: Course }>('GET', `courses/${segment(slug)}`).then((r) => r.data),
  lesson: (course: string, lesson: string) =>
    request<LessonResponse>('GET', `courses/${segment(course)}/lessons/${segment(lesson)}`),
  runSql: (lessonId: number, sql: string) => request<SqlRun>('POST', `lessons/${lessonId}/run`, { sql }),
  runPhp: (lessonId: number, code: string) => request<PhpRun>('POST', `lessons/${lessonId}/run`, { code }),
  answer: (questionId: number, option: number) =>
    request<QuizAnswer>('POST', `quiz-questions/${questionId}/answer`, { option }),
  checkSql: (challengeId: number, sql: string) =>
    request<SqlCheck>('POST', `challenges/${challengeId}/check`, { sql }),
  checkPhp: (challengeId: number, code: string) =>
    request<PhpCheck>('POST', `challenges/${challengeId}/check`, { code }),
};
