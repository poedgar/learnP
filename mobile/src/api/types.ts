/** Shapes of the learnP API (app/app/Http/Resources and the sandbox controllers). */
export type Language = 'sql' | 'php' | 'javascript' | 'typescript' | 'html';

/** Languages whose code runs on the device, in a sandbox frame (see src/js-runtime). */
export type FrameLanguage = 'javascript' | 'typescript' | 'html';

export const LANGUAGE_NAMES: Record<Language, string> = {
  sql: 'SQL',
  php: 'PHP',
  javascript: 'JavaScript',
  typescript: 'TypeScript',
  html: 'HTML & CSS',
};

export function runsInFrame(language: Language): language is FrameLanguage {
  return language === 'javascript' || language === 'typescript' || language === 'html';
}

export interface CourseSummary {
  id: number;
  slug: string;
  title: string;
  description: string;
  language: Language;
  lessons_count?: number;
}

export interface LessonSummary {
  id: number;
  slug: string;
  title: string;
  summary: string;
  position: number;
  quiz_questions_count: number;
  challenges_count: number;
}

export interface Course extends CourseSummary {
  lessons: LessonSummary[];
}

export interface QuizQuestion {
  id: number;
  question: string;
  code: string | null;
  options: string[];
}

export interface Challenge {
  id: number;
  title: string;
  prompt: string;
  starter_code: string | null;
  hint: string | null;
  order_matters: boolean;
  /** PHP and JavaScript: { label, code }. TypeScript may also have { label, compiles } or { label, rejects }. */
  checks: { label: string; code?: string; compiles?: string; rejects?: string; width?: number }[] | null;
}

export interface Lesson {
  id: number;
  slug: string;
  title: string;
  summary: string;
  position: number;
  content: string;
  starter_code: string | null;
  course: CourseSummary;
  quiz_questions: QuizQuestion[];
  challenges: Challenge[];
}

export interface SchemaTable {
  name: string;
  row_count: number;
  columns: { name: string; type: string; primary_key: boolean }[];
}

export interface LessonResponse {
  data: Lesson;
  schema: SchemaTable[];
  navigation: {
    previous: { slug: string; title: string } | null;
    next: { slug: string; title: string } | null;
  };
}

export type Cell = string | number | null;

export type SqlResult =
  | { type: 'rows'; sql: string; columns: string[]; rows: Cell[][]; truncated: boolean; duration_ms: number }
  | { type: 'command'; sql: string; affected_rows: number | null; duration_ms: number };

export interface SqlRun {
  results: SqlResult[];
  error: { message: string; statement: number | null } | null;
}

export interface PhpRun {
  output: string;
  fatal: boolean;
  durationMs: number;
}

/** A line printed by console.log and friends. */
export interface ConsoleEntry {
  level: 'log' | 'warn' | 'error';
  text: string;
}

/** A JavaScript or TypeScript run, from src/js-runtime/engine.js. */
export interface JsRun {
  entries: ConsoleEntry[];
  output: string;
  error: string | null;
  typeErrors: { line: number; column: number; message: string }[];
  stillRunning: boolean;
  durationMs: number;
  hasPage: boolean;
}

/** A JavaScript or TypeScript check report: a PhpCheck plus the console entries. */
export interface JsCheck extends PhpCheck {
  entries: ConsoleEntry[];
}

export interface QuizAnswer {
  correct: boolean;
  correct_option: number;
  explanation: string;
}

export interface SqlCheck {
  passed: boolean;
  message: string;
  run: SqlRun;
  expected: { columns: string[]; rows: Cell[][] } | null;
}

export interface PhpCheck {
  passed: boolean;
  loaded: boolean;
  error: string | null;
  output: string;
  checks: { label: string; passed: boolean; message: string | null }[];
}

/** A lesson as the review endpoint describes it: enough to label and open it. */
export interface ReviewLesson {
  id: number;
  slug: string;
  title: string;
  position: number;
  course: { slug: string; title: string; language: Language; position: number };
}

export interface ReviewItemsResponse {
  quiz_questions: (QuizQuestion & { lesson_id: number })[];
  challenges: (Challenge & { lesson_id: number })[];
  lessons: ReviewLesson[];
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  /** False for accounts made with GitHub, which confirm deletion with their email instead. */
  has_password: boolean;
  github: boolean;
}

/** Signing in from the app returns an API token for this device. */
export interface AuthResponse {
  user: User;
  token: string;
}

/**
 * A signed-in learner's progress as the server keeps it: lessons keyed by lesson id
 * and review cards keyed by item, in the shapes of src/progress and src/review.
 * A lesson saved by the web app has no theoryPage.
 */
export interface ProgressState {
  lessons: Record<string, unknown>;
  cards: Record<string, unknown>;
}
