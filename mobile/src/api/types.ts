/** Shapes of the learnP API (app/app/Http/Resources and the sandbox controllers). */
export type Language = 'sql' | 'php';

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
  checks: { label: string; code: string }[] | null;
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
