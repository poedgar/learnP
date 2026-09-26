# learnP Mobile App Implementation Plan (Plan 2 of 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an Expo app for iOS and Android in `mobile/` that offers the learnP web app's courses and lessons, with all four lesson steps (Learn, Sandbox, Quiz, Challenges) for both the SQL and PHP courses, using the existing learnP API.

**Architecture:** An Expo Router app in TypeScript. Screens read the API through a small typed client with TanStack Query, and learner progress is kept on the device in a Zustand store saved to AsyncStorage. Lesson Markdown is tokenised with `marked` and drawn as native components, with code highlighted by Prism. The code editor is CodeMirror 6 running in an **Expo DOM component** (`'use dom'`). Text flows out of it through callbacks, and `insert()`/`focus()` flow in through an imperative handle. A native key bar sits above the keyboard. SQL and PHP both run on the server (Plan 1 added server-side PHP).

**Tech Stack:** Expo SDK 57 (React Native 0.87, React 19), TypeScript, Expo Router, TanStack Query 5, Zustand 5, AsyncStorage, `marked` 18, Prism 1.30, CodeMirror 6, Jest with `jest-expo`, React Native Testing Library.

**Spec:** `docs/superpowers/specs/2026-09-25-expo-mobile-app-design.md` (Part 3, "Errors", "Testing")

## Global Constraints

- Node comes from nvm and isn't on the default PATH. Every shell command in this plan assumes you first run `export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"`.
- The app lives in `mobile/` at the repo root (`/Users/macbook/Desktop/dev/learnP/mobile`). Install Expo-managed packages with `npx expo install` so their versions match SDK 57, and other packages with `npm install`.
- The app must run in **Expo Go**, so it can't use native modules that Expo Go lacks. In particular: no `react-native-keyboard-controller` and no custom native code.
- API base: `EXPO_PUBLIC_API_URL` is the server root (for example `http://192.168.1.20:8000`). The client appends `/api/<path>`.
- Error messages, copied verbatim from `app/resources/js/api.js`:
  - Network failure: `Could not reach the server. Check your connection and try again.`
  - 429: `You are running queries very quickly. Wait a few seconds, then try again.`
  - Otherwise the server's `message`, or `The server responded with an error (<status>).`
- API calls (all JSON, `Accept: application/json`):
  - `GET courses` → `{ data: CourseSummary[] }`
  - `GET courses/{slug}` → `{ data: Course }`
  - `GET courses/{course}/lessons/{lesson}` → `{ data: Lesson, schema, navigation }`
  - `POST lessons/{id}/run`: `{ sql }` → `SqlRun`, or `{ code }` → `PhpRun`
  - `POST quiz-questions/{id}/answer` `{ option }` → `{ correct, correct_option, explanation }`
  - `POST challenges/{id}/check`: `{ sql }` → `SqlCheck`, or `{ code }` → `PhpCheck`
- Progress is saved under the AsyncStorage key `learnp:progress:v2`, in the web app's shape: `{ lessons: { [lessonId]: { read, theoryPage, sandboxCode, quiz: { [questionId]: { option, correct, correct_option, explanation } }, challenges: { [challengeId]: { passed, code } } } } }`. `lessonSummary()` follows `app/resources/js/progress.js` exactly.
- Routes: `/`, `/courses/[course]`, `/courses/[course]/lessons/[lesson]?step=learn|sandbox|quiz|challenges`, with the scheme `learnp`.
- Imports are relative (no `@/` alias), so Jest needs no module mapping.
- Tests: `npm test` (Jest with the `jest-expo` preset) and `npm run typecheck` (`tsc --noEmit`). Both must pass at the end of every task. Test files sit next to the code in `__tests__/` folders.
- Copy that also appears in the web app keeps the web app's wording (step labels, quiz feedback "Correct." / "Not quite.", button labels named in each task).
- Code comments follow the repo's style: a short comment explaining *why* at the top of each module that isn't obvious, and few inline comments.

## Review Focus

These are the inputs and failure cases most likely to hurt real users that the spec doesn't spell out. Each has a test in the task named.

1. **The phone can't reach the API** (wrong LAN IP, server not started). Screens must show the network message and a **Try again** button that refetches, not an endless spinner or a blank screen. Test in Task 5.
2. **The PHP runner is down** (503). The Sandbox and Challenges steps must show the server's message and keep the learner's code. Tests in Tasks 10 and 11.
3. **Double taps** on a quiz answer, Run or Check must send one request, not two. Tests in Tasks 7 and 10.
4. **Progress must survive an app restart:** it is written to AsyncStorage under `learnp:progress:v2`. Test in Task 3.
5. **A lesson with no quiz questions or no challenges** must show a message instead of crashing on `questions[0]`. Tests in Tasks 7 and 11.

**Manual-only risk** (it can't be exercised in Jest, so the Task 13 checklist covers it): the editor runs in a WebView. The native side never pushes text back into it except on an explicit reset, so fast typing can't lose characters.

---

## File Structure

```
mobile/
  app/                                    Expo Router routes (thin wrappers around src/screens)
    _layout.tsx                           QueryClient, Stack, KeyBarHost
    index.tsx                             → HomeScreen
    courses/[course]/index.tsx            → CourseScreen
    courses/[course]/lessons/[lesson].tsx → LessonScreen
  src/
    theme.ts                              colours (light/dark) and monospace font
    lib/text.ts                           plural()
    api/types.ts                          API types
    api/client.ts                         request(), ApiError, api.*
    progress/store.ts                     Zustand + AsyncStorage store, useLessonProgress, updateLesson
    progress/summary.ts                   lessonSummary()
    lesson/sections.ts                    splitSections()
    lesson/php.ts                         withOpeningTag(), phpCheckMessage()
    components/Button.tsx                 shared button
    components/LoadState.tsx              loading / error / not-found wrapper
    components/markdown/Markdown.tsx      marked tokens → native views
    components/markdown/CodeBlock.tsx     Prism-highlighted code
    components/results/ResultTable.tsx    SQL rows table
    components/results/RunResults.tsx     SQL run results
    components/results/PhpOutput.tsx      PHP output
    components/results/SchemaPanel.tsx    SQL dataset tables
    components/results/SqlCheckDetails.tsx
    components/results/PhpCheckDetails.tsx
    components/lesson/StepSwitcher.tsx
    components/lesson/LearnStep.tsx
    components/lesson/QuizStep.tsx
    components/lesson/SandboxStep.tsx
    components/lesson/ChallengesStep.tsx
    editor/CodeMirrorView.tsx             'use dom' CodeMirror component
    editor/__mocks__/CodeMirrorView.tsx   Jest stand-in (a TextInput)
    editor/CodeEditor.tsx                 native wrapper, registers with the key bar
    editor/keys.ts                        key bar keys per language
    editor/keyBarStore.ts                 which editor is focused
    editor/KeyBar.tsx                     KeyBar + KeyBarHost (above the keyboard)
    screens/HomeScreen.tsx
    screens/CourseScreen.tsx
    screens/LessonScreen.tsx
    test/fixtures.ts                      sample API responses for tests
    test/render.tsx                       renderWithClient()
  jest.setup.ts
  README.md                               setup, running on a phone, manual checklist
```

---

### Task 1: Scaffold the Expo app with Expo Router and Jest

**Files:**
- Create: `mobile/` (from the `blank-typescript` template), `mobile/app/_layout.tsx`, `mobile/app/index.tsx`, `mobile/src/theme.ts`, `mobile/src/lib/text.ts`, `mobile/src/components/Button.tsx`, `mobile/src/components/__tests__/Button.test.tsx`, `mobile/src/lib/__tests__/text.test.ts`, `mobile/jest.setup.ts`, `mobile/.env.example`
- Modify: `mobile/package.json`, `mobile/app.json`, `mobile/.gitignore`
- Delete: `mobile/App.tsx`, `mobile/index.ts`

**Interfaces:**
- Produces:
  - `useTheme(): Theme` and `monoFont` from `src/theme.ts`.
  - `plural(count: number, word: string): string` from `src/lib/text.ts`.
  - `Button({ title, onPress, variant?: 'primary' | 'default' | 'quiet', disabled?, accessibilityLabel? })` from `src/components/Button.tsx`.
  - Jest is configured, with `jest.setup.ts` mocking AsyncStorage and `expo-router`.

- [ ] **Step 1: Create the project and install dependencies**

```bash
export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"
cd /Users/macbook/Desktop/dev/learnP
npx create-expo-app@latest mobile --template blank-typescript --yes
cd mobile
npx expo install expo-router expo-linking expo-constants expo-status-bar react-native-safe-area-context react-native-screens react-dom react-native-web @react-native-async-storage/async-storage
npm install @tanstack/react-query zustand marked@^18 prismjs codemirror @codemirror/lang-php @codemirror/lang-sql @codemirror/state @codemirror/view @codemirror/theme-one-dark
npx expo install jest-expo jest @types/jest -- --save-dev
npm install --save-dev @testing-library/react-native @types/prismjs
rm -f App.tsx index.ts
```

Expected: every command succeeds. `npx expo install` may say a package is already installed; that's fine.

- [ ] **Step 2: Configure `package.json`, `app.json`, `.gitignore` and `.env.example`**

In `mobile/package.json`:
- set `"main": "expo-router/entry"`;
- add `"test": "jest"` and `"typecheck": "tsc --noEmit"` to `scripts`;
- add this top-level `jest` block:

```json
"jest": {
  "preset": "jest-expo",
  "setupFilesAfterEnv": ["<rootDir>/jest.setup.ts"],
  "transformIgnorePatterns": [
    "node_modules/(?!((jest-)?react-native|@react-native(-community)?)|expo(nent)?|@expo(nent)?/.*|@expo-google-fonts/.*|react-navigation|@react-navigation/.*|react-native-svg|marked)"
  ]
}
```

In `mobile/app.json`, inside `"expo"`: set `"name": "learnP"`, `"slug": "learnp"`, `"scheme": "learnp"` and `"userInterfaceStyle": "automatic"`, and add `"plugins": ["expo-router"]`. If `plugins` already exists, add `"expo-router"` to it.

Append to `mobile/.gitignore`:

```
.env
```

Create `mobile/.env.example`:

```dotenv
# The learnP API server root, reachable from your phone (not localhost).
# Start the API with: php artisan serve --host=0.0.0.0 --port=8000
EXPO_PUBLIC_API_URL=http://192.168.1.20:8000
```

- [ ] **Step 3: Write the Jest setup**

Create `mobile/jest.setup.ts`:

```ts
// An in-memory AsyncStorage, so tests don't depend on the package's own mock layout.
jest.mock('@react-native-async-storage/async-storage', () => {
  const store = new Map<string, string>();
  return {
    __esModule: true,
    default: {
      getItem: jest.fn(async (key: string) => store.get(key) ?? null),
      setItem: jest.fn(async (key: string, value: string) => {
        store.set(key, value);
      }),
      removeItem: jest.fn(async (key: string) => {
        store.delete(key);
      }),
      clear: jest.fn(async () => store.clear()),
    },
  };
});

// Screens use the imperative router and <Stack.Screen options>; tests assert on the calls.
jest.mock('expo-router', () => ({
  router: { push: jest.fn(), setParams: jest.fn(), back: jest.fn() },
  Stack: { Screen: () => null },
}));

process.env.EXPO_PUBLIC_API_URL = 'http://api.test';
```

- [ ] **Step 4: Write the failing tests**

Create `mobile/src/lib/__tests__/text.test.ts`:

```ts
import { plural } from '../text';

test('plural adds an s except for one', () => {
  expect(plural(0, 'lesson')).toBe('0 lessons');
  expect(plural(1, 'lesson')).toBe('1 lesson');
  expect(plural(8, 'quiz question')).toBe('8 quiz questions');
});
```

Create `mobile/src/components/__tests__/Button.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { Button } from '../Button';

test('calls onPress when tapped', async () => {
  const onPress = jest.fn();
  await render(<Button title="Run" onPress={onPress} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(onPress).toHaveBeenCalledTimes(1);
});

test('does not call onPress when disabled', async () => {
  const onPress = jest.fn();
  await render(<Button title="Run" onPress={onPress} disabled />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(onPress).not.toHaveBeenCalled();
});
```

- [ ] **Step 5: Run the tests to verify they fail**

Run: `cd mobile && npm test`
Expected: FAIL, because `../text` and `../Button` don't exist.

- [ ] **Step 6: Write the theme, text helper, button and the first routes**

Create `mobile/src/theme.ts`:

```ts
import { Platform, useColorScheme } from 'react-native';

/** Colours follow the web app's palette (app/resources/css/app.css), plus a dark variant. */
const light = {
  background: '#f4f6fa',
  surface: '#ffffff',
  ink: '#18233f',
  muted: '#5b6680',
  line: '#d8deea',
  accent: '#18233f',
  accentInk: '#ffffff',
  highlight: '#f5b83d',
  highlightSoft: '#fdf0cf',
  ok: '#2f8f5b',
  okSoft: '#e3f3ea',
  bad: '#c4424e',
  badSoft: '#fbe6e8',
  codeBackground: '#eef1f7',
  syntax: {
    keyword: '#7a3e9d',
    string: '#2f7d4f',
    number: '#b0562a',
    comment: '#7c869c',
    function: '#2c5fb3',
    variable: '#9a4d00',
    type: '#8a5a00',
    plain: '#18233f',
  },
};

export type Theme = typeof light;
export type SyntaxColor = keyof Theme['syntax'];

const dark: Theme = {
  background: '#0f1523',
  surface: '#182033',
  ink: '#e8ecf5',
  muted: '#9aa5bf',
  line: '#2a3550',
  accent: '#f5b83d',
  accentInk: '#18233f',
  highlight: '#f5b83d',
  highlightSoft: '#3a3120',
  ok: '#5cc58c',
  okSoft: '#16301f',
  bad: '#ef7b84',
  badSoft: '#3a1d22',
  codeBackground: '#111829',
  syntax: {
    keyword: '#c792ea',
    string: '#9ece6a',
    number: '#ff9e64',
    comment: '#7a86a3',
    function: '#82aaff',
    variable: '#f7c56a',
    type: '#ffcb6b',
    plain: '#e8ecf5',
  },
};

export function useTheme(): Theme {
  return useColorScheme() === 'dark' ? dark : light;
}

export const monoFont = Platform.select({ ios: 'Menlo', default: 'monospace' });
```

Create `mobile/src/lib/text.ts`:

```ts
export function plural(count: number, word: string): string {
  return `${count} ${word}${count === 1 ? '' : 's'}`;
}
```

Create `mobile/src/components/Button.tsx`:

```tsx
import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme';

type Props = {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'default' | 'quiet';
  disabled?: boolean;
  accessibilityLabel?: string;
};

export function Button({ title, onPress, variant = 'default', disabled = false, accessibilityLabel }: Props) {
  const theme = useTheme();
  const primary = variant === 'primary';
  const quiet = variant === 'quiet';

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? title}
      accessibilityState={{ disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.base,
        {
          backgroundColor: primary ? theme.accent : quiet ? 'transparent' : theme.surface,
          borderColor: primary ? theme.accent : quiet ? 'transparent' : theme.line,
          opacity: disabled ? 0.5 : pressed ? 0.75 : 1,
        },
      ]}
    >
      <Text style={[styles.label, { color: primary ? theme.accentInk : theme.ink }]}>{title}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 44,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: { fontSize: 16, fontWeight: '600' },
});
```

Create `mobile/app/_layout.tsx`:

```tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useState } from 'react';
import { View } from 'react-native';
import { useTheme } from '../src/theme';

export const unstable_settings = { initialRouteName: 'index' };

export default function RootLayout() {
  const theme = useTheme();
  const [client] = useState(
    () => new QueryClient({ defaultOptions: { queries: { retry: 1, staleTime: 5 * 60_000 } } }),
  );

  return (
    <QueryClientProvider client={client}>
      <View style={{ flex: 1, backgroundColor: theme.background }}>
        <Stack
          screenOptions={{
            headerStyle: { backgroundColor: theme.surface },
            headerTintColor: theme.ink,
            contentStyle: { backgroundColor: theme.background },
          }}
        >
          <Stack.Screen name="index" options={{ title: 'learnP' }} />
        </Stack>
        <StatusBar style="auto" />
      </View>
    </QueryClientProvider>
  );
}
```

Create `mobile/app/index.tsx`. It's a placeholder until Task 5:

```tsx
import { Text, View } from 'react-native';

export default function Index() {
  return (
    <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}>
      <Text>learnP</Text>
    </View>
  );
}
```

- [ ] **Step 7: Run tests, typecheck and a bundle check**

```bash
cd /Users/macbook/Desktop/dev/learnP/mobile
npm test
npm run typecheck
npx expo export --platform android --output-dir /tmp/learnp-export && rm -rf /tmp/learnp-export
```

Expected: 3 tests pass, typecheck is clean, and the export finishes. The export proves Metro can bundle the app and router.

If RNTL's `render` or `fireEvent` in this version is synchronous, the `await`s are harmless. Keep them for consistency.

- [ ] **Step 8: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile
git commit -m "Scaffold the learnP Expo app with Expo Router and Jest"
```

Check that `mobile/node_modules` and `mobile/.env` aren't staged. The template's `.gitignore` covers `node_modules`.

---

### Task 2: Typed API client

**Files:**
- Create: `mobile/src/api/types.ts`, `mobile/src/api/client.ts`, `mobile/src/api/__tests__/client.test.ts`

**Interfaces:**
- Produces: the types below, plus `ApiError` (with `status: number`, where 0 means no response) and the `api` object:

```ts
api.courses(): Promise<CourseSummary[]>
api.course(slug: string): Promise<Course>
api.lesson(course: string, lesson: string): Promise<LessonResponse>
api.runSql(lessonId: number, sql: string): Promise<SqlRun>
api.runPhp(lessonId: number, code: string): Promise<PhpRun>
api.answer(questionId: number, option: number): Promise<QuizAnswer>
api.checkSql(challengeId: number, sql: string): Promise<SqlCheck>
api.checkPhp(challengeId: number, code: string): Promise<PhpCheck>
```

- [ ] **Step 1: Write the types**

Create `mobile/src/api/types.ts`:

```ts
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
```

- [ ] **Step 2: Write the failing tests**

Create `mobile/src/api/__tests__/client.test.ts`:

```ts
import { api, ApiError } from '../client';

const fetchMock = jest.fn();

beforeEach(() => {
  fetchMock.mockReset();
  global.fetch = fetchMock as unknown as typeof fetch;
});

function respond(status: number, body: unknown) {
  fetchMock.mockResolvedValueOnce({
    ok: status >= 200 && status < 300,
    status,
    json: async () => {
      if (body === undefined) throw new SyntaxError('no json');
      return body;
    },
  });
}

test('GET courses unwraps data and asks for JSON', async () => {
  respond(200, { data: [{ id: 1, slug: 'sql' }] });

  await expect(api.courses()).resolves.toEqual([{ id: 1, slug: 'sql' }]);

  const [url, init] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/courses');
  expect(init.method).toBe('GET');
  expect(init.headers.Accept).toBe('application/json');
  expect(init.body).toBeUndefined();
});

test('slugs are URL-encoded', async () => {
  respond(200, { data: {} });

  await api.lesson('php oop', 'a/b');

  expect(fetchMock.mock.calls[0][0]).toBe('http://api.test/api/courses/php%20oop/lessons/a%2Fb');
});

test('runPhp posts the code as JSON', async () => {
  respond(200, { output: 'hi', fatal: false, durationMs: 3 });

  await expect(api.runPhp(7, 'echo "hi";')).resolves.toEqual({ output: 'hi', fatal: false, durationMs: 3 });

  const [url, init] = fetchMock.mock.calls[0];
  expect(url).toBe('http://api.test/api/lessons/7/run');
  expect(init.method).toBe('POST');
  expect(init.headers['Content-Type']).toBe('application/json');
  expect(JSON.parse(init.body)).toEqual({ code: 'echo "hi";' });
});

test('runSql, answer, checkSql and checkPhp send the right bodies', async () => {
  respond(200, {});
  respond(200, {});
  respond(200, {});
  respond(200, {});

  await api.runSql(1, 'SELECT 1');
  await api.answer(5, 2);
  await api.checkSql(9, 'SELECT 2');
  await api.checkPhp(10, '<?php');

  const calls = fetchMock.mock.calls.map(([url, init]) => [url, JSON.parse(init.body)]);
  expect(calls).toEqual([
    ['http://api.test/api/lessons/1/run', { sql: 'SELECT 1' }],
    ['http://api.test/api/quiz-questions/5/answer', { option: 2 }],
    ['http://api.test/api/challenges/9/check', { sql: 'SELECT 2' }],
    ['http://api.test/api/challenges/10/check', { code: '<?php' }],
  ]);
});

test('a network failure becomes an ApiError with status 0', async () => {
  fetchMock.mockRejectedValueOnce(new TypeError('Network request failed'));

  const error = await api.courses().catch((e) => e);

  expect(error).toBeInstanceOf(ApiError);
  expect(error.status).toBe(0);
  expect(error.message).toBe('Could not reach the server. Check your connection and try again.');
});

test('429 gets the rate-limit message', async () => {
  respond(429, { message: 'Too Many Attempts.' });

  await expect(api.runSql(1, 'SELECT 1')).rejects.toThrow(
    'You are running queries very quickly. Wait a few seconds, then try again.',
  );
});

test('other errors use the server message', async () => {
  respond(422, { message: 'Write some PHP before running it.' });

  const error = await api.runPhp(1, '').catch((e) => e);

  expect(error.status).toBe(422);
  expect(error.message).toBe('Write some PHP before running it.');
});

test('errors without a JSON body name the status', async () => {
  respond(500, undefined);

  await expect(api.courses()).rejects.toThrow('The server responded with an error (500).');
});
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/api`
Expected: FAIL, because `../client` doesn't exist.

- [ ] **Step 4: Write the client**

Create `mobile/src/api/client.ts`:

```ts
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
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/api
git commit -m "Add typed learnP API client"
```

---

### Task 3: Progress store, lesson summary and lesson helpers

**Files:**
- Create: `mobile/src/progress/store.ts`, `mobile/src/progress/summary.ts`, `mobile/src/lesson/sections.ts`, `mobile/src/lesson/php.ts`, and tests `mobile/src/progress/__tests__/store.test.ts`, `mobile/src/progress/__tests__/summary.test.ts`, `mobile/src/lesson/__tests__/sections.test.ts`, `mobile/src/lesson/__tests__/php.test.ts`
- Modify: `mobile/jest.setup.ts` (reset the store between tests)

**Interfaces:**
- Consumes: `QuizAnswer`, `PhpCheck` from `src/api/types.ts`.
- Produces:
  - Types `LessonProgress`, `QuizRecord`, `ChallengeRecord`.
  - `STORAGE_KEY` and `EMPTY_PROGRESS`.
  - `useProgressStore` (Zustand): `{ lessons; update(lessonId, change) }`.
  - `useLessonProgress(lessonId): LessonProgress`.
  - `updateLesson(lessonId: number, change: (current: LessonProgress) => Partial<LessonProgress>): void`.
  - `lessonSummary(lesson: LessonCounts, progress: LessonProgress | undefined): LessonSummary`.
  - `splitSections(content: string, fallbackTitle: string): Section[]`.
  - `withOpeningTag(code: string): string` and `phpCheckMessage(check: PhpCheck): string`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/progress/__tests__/summary.test.ts`:

```ts
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
```

Create `mobile/src/progress/__tests__/store.test.ts`:

```ts
import AsyncStorage from '@react-native-async-storage/async-storage';
import { act, renderHook } from '@testing-library/react-native';
import { EMPTY_PROGRESS, STORAGE_KEY, updateLesson, useLessonProgress, useProgressStore } from '../store';

test('an unopened lesson reads as empty progress', async () => {
  const { result } = await renderHook(() => useLessonProgress(42));

  expect(result.current).toEqual(EMPTY_PROGRESS);
});

test('updateLesson merges changes into the lesson', async () => {
  const { result } = await renderHook(() => useLessonProgress(3));

  await act(async () => updateLesson(3, () => ({ read: true })));
  await act(async () => updateLesson(3, (current) => ({ quiz: { ...current.quiz, 9: { option: 1, correct: true, correct_option: 1, explanation: 'Yes.' } } })));

  expect(result.current.read).toBe(true);
  expect(result.current.quiz[9].correct).toBe(true);
  expect(result.current.sandboxCode).toBeNull();
});

test('progress is saved to AsyncStorage under learnp:progress:v2', async () => {
  await act(async () => updateLesson(5, () => ({ sandboxCode: 'SELECT 1' })));

  const saved = JSON.parse((await AsyncStorage.getItem(STORAGE_KEY)) ?? 'null');

  expect(STORAGE_KEY).toBe('learnp:progress:v2');
  expect(saved.state.lessons['5'].sandboxCode).toBe('SELECT 1');
  expect(Object.keys(saved.state)).toEqual(['lessons']);
});

test('the store starts from what AsyncStorage holds', async () => {
  await AsyncStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({ state: { lessons: { 8: { ...EMPTY_PROGRESS, read: true } } }, version: 0 }),
  );

  await act(async () => {
    await useProgressStore.persist.rehydrate();
  });

  expect(useProgressStore.getState().lessons[8].read).toBe(true);
});
```

Create `mobile/src/lesson/__tests__/sections.test.ts`:

```ts
import { splitSections } from '../sections';

test('splits on level-2 headings and uses them as titles', () => {
  const sections = splitSections('## One\n\nA\n\n## `Two`\n\nB\n', 'Lesson');

  expect(sections.map((s) => s.title)).toEqual(['One', 'Two']);
  expect(sections[1].source).toContain('B');
});

test('text before the first heading joins the first section', () => {
  const sections = splitSections('Intro.\n\n## One\n\nA\n', 'Lesson');

  expect(sections).toHaveLength(1);
  expect(sections[0].title).toBe('One');
  expect(sections[0].source).toContain('Intro.');
});

test('content without headings is one section titled after the lesson', () => {
  expect(splitSections('Just text.', 'Lesson')).toEqual([{ source: 'Just text.', title: 'Lesson' }]);
});

test('empty content still gives one section', () => {
  expect(splitSections('', 'Lesson')).toEqual([{ source: '', title: 'Lesson' }]);
});
```

Create `mobile/src/lesson/__tests__/php.test.ts`:

```ts
import { phpCheckMessage, withOpeningTag } from '../php';

test('withOpeningTag adds <?php only when missing', () => {
  expect(withOpeningTag('echo 1;')).toBe('<?php\n\necho 1;');
  expect(withOpeningTag('  <?php echo 1;')).toBe('  <?php echo 1;');
});

const checks = [
  { label: 'a', passed: true, message: null },
  { label: 'b', passed: false, message: 'Nope.' },
];

test('phpCheckMessage matches the web app wording', () => {
  expect(phpCheckMessage({ passed: true, loaded: true, error: null, output: '', checks: [checks[0], checks[0]] })).toBe(
    'Correct! All 2 checks pass.',
  );
  expect(phpCheckMessage({ passed: false, loaded: false, error: 'ParseError', output: '', checks })).toBe(
    'Your code has an error, so the checks could not run yet.',
  );
  expect(phpCheckMessage({ passed: false, loaded: true, error: null, output: '', checks })).toBe(
    "1 of 2 checks pass. See what's missing below.",
  );
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/progress src/lesson`
Expected: FAIL, because the modules don't exist.

- [ ] **Step 3: Write the modules**

Create `mobile/src/progress/store.ts`:

```ts
import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createJSONStorage, persist } from 'zustand/middleware';
import type { QuizAnswer } from '../api/types';

/**
 * Learner progress lives on the device; there are no accounts yet. The lesson
 * shape matches the web app's resources/js/progress.js.
 */
export interface QuizRecord extends QuizAnswer {
  option: number;
}

export interface ChallengeRecord {
  passed: boolean;
  code: string | null;
}

export interface LessonProgress {
  read: boolean;
  theoryPage: number;
  sandboxCode: string | null;
  quiz: Record<number, QuizRecord>;
  challenges: Record<number, ChallengeRecord>;
}

export const STORAGE_KEY = 'learnp:progress:v2';

export const EMPTY_PROGRESS: LessonProgress = Object.freeze({
  read: false,
  theoryPage: 1,
  sandboxCode: null,
  quiz: {},
  challenges: {},
});

interface ProgressState {
  lessons: Record<number, LessonProgress>;
  update: (lessonId: number, change: (current: LessonProgress) => Partial<LessonProgress>) => void;
}

export const useProgressStore = create<ProgressState>()(
  persist(
    (set) => ({
      lessons: {},
      update: (lessonId, change) =>
        set((state) => {
          const current = state.lessons[lessonId] ?? EMPTY_PROGRESS;
          return { lessons: { ...state.lessons, [lessonId]: { ...current, ...change(current) } } };
        }),
    }),
    {
      name: STORAGE_KEY,
      storage: createJSONStorage(() => AsyncStorage),
      partialize: (state) => ({ lessons: state.lessons }),
    },
  ),
);

/** A lesson nobody has opened reads as EMPTY_PROGRESS (a stable object, so no re-render loops). */
export function useLessonProgress(lessonId: number): LessonProgress {
  return useProgressStore((state) => state.lessons[lessonId]) ?? EMPTY_PROGRESS;
}

export function updateLesson(
  lessonId: number,
  change: (current: LessonProgress) => Partial<LessonProgress>,
): void {
  useProgressStore.getState().update(lessonId, change);
}
```

Create `mobile/src/progress/summary.ts`:

```ts
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
```

Create `mobile/src/lesson/sections.ts`:

```ts
export interface Section {
  source: string;
  title: string;
}

/**
 * One page per "## " section, as in the web app's TheoryStep. Text before the
 * first heading joins the first page.
 */
export function splitSections(content: string, fallbackTitle: string): Section[] {
  const chunks = content.split(/^(?=## )/m).filter((chunk) => chunk.trim());

  if (chunks.length === 0) {
    return [{ source: content, title: fallbackTitle }];
  }

  if (chunks.length > 1 && !chunks[0].startsWith('## ')) {
    chunks.splice(0, 2, `${chunks[1]}\n\n${chunks[0]}`);
  }

  return chunks.map((source) => ({
    source,
    // Titles appear as plain text in navigation, so drop inline-code backticks.
    title: source.match(/^## (.+)$/m)?.[1].replace(/`/g, '').trim() ?? fallbackTitle,
  }));
}
```

Create `mobile/src/lesson/php.ts`:

```ts
import type { PhpCheck } from '../api/types';

/** Snippets from the theory may leave out the opening tag; the sandbox needs it. */
export function withOpeningTag(code: string): string {
  return code.trimStart().startsWith('<?php') ? code : `<?php\n\n${code}`;
}

/** The verdict line for a PHP challenge, worded as in the web app's ChallengesStep. */
export function phpCheckMessage(check: PhpCheck): string {
  const passedCount = check.checks.filter((c) => c.passed).length;

  if (check.passed) {
    return `Correct! All ${check.checks.length} checks pass.`;
  }

  return check.loaded
    ? `${passedCount} of ${check.checks.length} checks pass. See what's missing below.`
    : 'Your code has an error, so the checks could not run yet.';
}
```

In `mobile/jest.setup.ts`, add this at the end so every test starts with empty progress:

```ts
import { useProgressStore } from './src/progress/store';

afterEach(() => {
  useProgressStore.setState({ lessons: {} });
});
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

If `test('progress is saved…')` fails because persist writes asynchronously, wrap the read in `await waitFor(async () => expect(...))` from RNTL. Don't drop the assertion.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile
git commit -m "Add on-device progress store and lesson helpers"
```

---

### Task 4: Markdown renderer with highlighted code

**Files:**
- Create: `mobile/src/components/markdown/Markdown.tsx`, `mobile/src/components/markdown/CodeBlock.tsx`, `mobile/src/components/markdown/__tests__/Markdown.test.tsx`, `mobile/src/components/markdown/__tests__/CodeBlock.test.tsx`

**Interfaces:**
- Consumes: `useTheme`, `monoFont`, `Theme`, `SyntaxColor` (Task 1); `Button` (Task 1); `Language` (Task 2).
- Produces:
  - `Markdown({ source: string; inline?: boolean; runnable?: Language | null; onTry?: (code: string) => void })`.
  - `CodeBlock({ code: string; language: string })`.
  - `highlightTokens(code: string, language: string): { text: string; color: SyntaxColor }[]`.
  - `decodeEntities(text: string): string`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/components/markdown/__tests__/Markdown.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { Markdown } from '../Markdown';

const source = [
  '## Selecting columns',
  '',
  'Use **bold** words, pick `title`, and `<?php` stays literal.',
  '',
  '```sql',
  'SELECT title FROM books;',
  '```',
  '',
  '```php',
  'echo 1;',
  '```',
  '',
  '> Every run starts fresh.',
  '',
  '- first item',
  '- second item',
  '',
  '| column | type |',
  '|---|---|',
  '| title | TEXT |',
].join('\n');

test('renders headings, inline formatting, quotes, lists and tables', async () => {
  await render(<Markdown source={source} />);

  expect(screen.getByText('Selecting columns')).toBeTruthy();
  expect(screen.getByText('bold')).toBeTruthy();
  expect(screen.getByText('<?php')).toBeTruthy();
  expect(screen.getByText('Every run starts fresh.')).toBeTruthy();
  expect(screen.getByText('first item')).toBeTruthy();
  expect(screen.getByText('column')).toBeTruthy();
  expect(screen.getByText('TEXT')).toBeTruthy();
});

test('code blocks in the runnable language get a Try button that sends their code', async () => {
  const onTry = jest.fn();
  await render(<Markdown source={source} runnable="sql" onTry={onTry} />);

  const buttons = screen.getAllByRole('button', { name: 'Try it in the sandbox' });
  expect(buttons).toHaveLength(1);

  await fireEvent.press(buttons[0]);
  expect(onTry).toHaveBeenCalledWith('SELECT title FROM books;');
});

test('no Try buttons without a runnable language', async () => {
  await render(<Markdown source={source} />);

  expect(screen.queryByRole('button', { name: 'Try it in the sandbox' })).toBeNull();
});

test('inline mode renders a single run of text', async () => {
  await render(<Markdown source="Pick `FROM`." inline />);

  expect(screen.getByText('FROM')).toBeTruthy();
});
```

Create `mobile/src/components/markdown/__tests__/CodeBlock.test.tsx`:

```tsx
import { decodeEntities } from '../Markdown';
import { highlightTokens } from '../CodeBlock';

test('highlightTokens keeps every character and colours keywords', () => {
  const tokens = highlightTokens('SELECT title FROM books;', 'sql');

  expect(tokens.map((t) => t.text).join('')).toBe('SELECT title FROM books;');
  expect(tokens.find((t) => t.text === 'SELECT')?.color).toBe('keyword');
});

test('PHP variables and strings get their own colours', () => {
  const tokens = highlightTokens('<?php $name = "Ada";', 'php');

  expect(tokens.map((t) => t.text).join('')).toBe('<?php $name = "Ada";');
  expect(tokens.find((t) => t.text.includes('$name'))?.color).toBe('variable');
  expect(tokens.find((t) => t.text.includes('Ada'))?.color).toBe('string');
});

test('unknown languages come back as plain text', () => {
  expect(highlightTokens('hello', 'brainfuck')).toEqual([{ text: 'hello', color: 'plain' }]);
});

test('decodeEntities turns HTML entities back into characters', () => {
  expect(decodeEntities('&lt;?php &amp;&amp; &quot;x&quot; &#39;y&#39;')).toBe('<?php && "x" \'y\'');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/components/markdown`
Expected: FAIL, because the modules don't exist.

- [ ] **Step 3: Write `CodeBlock`**

Create `mobile/src/components/markdown/CodeBlock.tsx`:

```tsx
import Prism from 'prismjs';
import 'prismjs/components/prism-markup-templating';
import 'prismjs/components/prism-php';
import 'prismjs/components/prism-sql';
import { ScrollView, StyleSheet, Text } from 'react-native';
import { monoFont, useTheme, type SyntaxColor } from '../../theme';

/** Prism token types → theme syntax colours. Anything unlisted inherits its parent's colour. */
const COLORS: Record<string, SyntaxColor> = {
  keyword: 'keyword',
  boolean: 'keyword',
  delimiter: 'keyword',
  important: 'keyword',
  string: 'string',
  char: 'string',
  number: 'number',
  constant: 'number',
  comment: 'comment',
  function: 'function',
  variable: 'variable',
  property: 'variable',
  'class-name': 'type',
};

export function highlightTokens(code: string, language: string): { text: string; color: SyntaxColor }[] {
  const grammar = Prism.languages[language];
  if (!grammar) {
    return [{ text: code, color: 'plain' }];
  }

  const out: { text: string; color: SyntaxColor }[] = [];
  const walk = (tokens: (string | Prism.Token)[], inherited: SyntaxColor) => {
    for (const token of tokens) {
      if (typeof token === 'string') {
        out.push({ text: token, color: inherited });
        continue;
      }
      const color = COLORS[token.type] ?? inherited;
      if (typeof token.content === 'string') {
        out.push({ text: token.content, color });
      } else {
        walk(Array.isArray(token.content) ? token.content : [token.content], color);
      }
    }
  };
  walk(Prism.tokenize(code, grammar), 'plain');

  return out;
}

export function CodeBlock({ code, language }: { code: string; language: string }) {
  const theme = useTheme();

  return (
    <ScrollView
      horizontal
      style={[styles.box, { backgroundColor: theme.codeBackground, borderColor: theme.line }]}
      contentContainerStyle={styles.content}
    >
      <Text style={[styles.code, { color: theme.syntax.plain }]} selectable>
        {highlightTokens(code.replace(/\n$/, ''), language).map((token, i) => (
          <Text key={i} style={{ color: theme.syntax[token.color] }}>
            {token.text}
          </Text>
        ))}
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  box: { borderWidth: 1, borderRadius: 8 },
  content: { padding: 12 },
  code: { fontFamily: monoFont, fontSize: 14, lineHeight: 21 },
});
```

- [ ] **Step 4: Write `Markdown`**

Create `mobile/src/components/markdown/Markdown.tsx`:

```tsx
import { Lexer, type Token, type Tokens } from 'marked';
import { useMemo, type ReactNode } from 'react';
import { Linking, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Language } from '../../api/types';
import { monoFont, useTheme, type Theme } from '../../theme';
import { Button } from '../Button';
import { CodeBlock } from './CodeBlock';

/**
 * Lesson Markdown drawn as native views. marked only tokenises here; the
 * subset the lessons use (headings, paragraphs, lists, tables, quotes, code) is
 * rendered below. Code blocks in the lesson's language get a "Try it" button.
 */
type Props = {
  source: string;
  inline?: boolean;
  runnable?: Language | null;
  onTry?: (code: string) => void;
};

const ENTITIES: Record<string, string> = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#39;': "'" };

export function decodeEntities(text: string): string {
  return text.replace(/&(amp|lt|gt|quot|#39);/g, (entity) => ENTITIES[entity]);
}

export function Markdown({ source, inline = false, runnable = null, onTry }: Props) {
  const theme = useTheme();
  const tokens = useMemo(() => (inline ? Lexer.lexInline(source) : Lexer.lex(source)), [source, inline]);

  if (inline) {
    return <Text style={[styles.body, { color: theme.ink }]}>{renderInline(tokens, theme)}</Text>;
  }

  return (
    <View style={styles.blocks}>
      {tokens.map((token, i) => (
        <Block key={i} token={token} theme={theme} runnable={runnable} onTry={onTry} />
      ))}
    </View>
  );
}

type BlockProps = { token: Token; theme: Theme; runnable: Language | null; onTry?: (code: string) => void };

function Block({ token, theme, runnable, onTry }: BlockProps): ReactNode {
  switch (token.type) {
    case 'space':
      return null;
    case 'heading': {
      const heading = token as Tokens.Heading;
      return (
        <Text accessibilityRole="header" style={[heading.depth <= 2 ? styles.h2 : styles.h3, { color: theme.ink }]}>
          {renderInline(heading.tokens, theme)}
        </Text>
      );
    }
    case 'paragraph':
      return (
        <Text style={[styles.body, { color: theme.ink }]}>{renderInline((token as Tokens.Paragraph).tokens, theme)}</Text>
      );
    case 'text': {
      const text = token as Tokens.Text;
      return (
        <Text style={[styles.body, { color: theme.ink }]}>
          {text.tokens ? renderInline(text.tokens, theme) : decodeEntities(text.text)}
        </Text>
      );
    }
    case 'code': {
      const code = token as Tokens.Code;
      const lang = (code.lang ?? '').trim();
      return (
        <View style={styles.codeGroup}>
          <CodeBlock code={code.text} language={lang} />
          {runnable && lang === runnable && onTry ? (
            <Button title="Try it in the sandbox" onPress={() => onTry(code.text)} />
          ) : null}
        </View>
      );
    }
    case 'blockquote':
      return (
        <View style={[styles.quote, { borderColor: theme.highlight, backgroundColor: theme.highlightSoft }]}>
          {(token as Tokens.Blockquote).tokens.map((child, i) => (
            <Block key={i} token={child} theme={theme} runnable={runnable} onTry={onTry} />
          ))}
        </View>
      );
    case 'list': {
      const list = token as Tokens.List;
      const start = typeof list.start === 'number' ? list.start : 1;
      return (
        <View style={styles.list}>
          {list.items.map((item, i) => (
            <View key={i} style={styles.listItem}>
              <Text style={[styles.body, styles.bullet, { color: theme.muted }]}>
                {list.ordered ? `${start + i}.` : '•'}
              </Text>
              <View style={styles.listBody}>
                {item.tokens.map((child, j) => (
                  <Block key={j} token={child} theme={theme} runnable={runnable} onTry={onTry} />
                ))}
              </View>
            </View>
          ))}
        </View>
      );
    }
    case 'table': {
      const table = token as Tokens.Table;
      return (
        <ScrollView horizontal style={[styles.table, { borderColor: theme.line }]}>
          <View>
            <View style={[styles.tableRow, { backgroundColor: theme.codeBackground }]}>
              {table.header.map((cell, i) => (
                <Text key={i} style={[styles.cell, styles.headerCell, { color: theme.ink, borderColor: theme.line }]}>
                  {renderInline(cell.tokens, theme)}
                </Text>
              ))}
            </View>
            {table.rows.map((row, r) => (
              <View key={r} style={styles.tableRow}>
                {row.map((cell, c) => (
                  <Text key={c} style={[styles.cell, { color: theme.ink, borderColor: theme.line }]}>
                    {renderInline(cell.tokens, theme)}
                  </Text>
                ))}
              </View>
            ))}
          </View>
        </ScrollView>
      );
    }
    case 'hr':
      return <View style={[styles.hr, { backgroundColor: theme.line }]} />;
    default:
      return 'raw' in token && token.raw.trim() ? (
        <Text style={[styles.body, { color: theme.ink }]}>{token.raw}</Text>
      ) : null;
  }
}

function renderInline(tokens: Token[] | undefined, theme: Theme): ReactNode[] {
  return (tokens ?? []).map((token, i) => {
    switch (token.type) {
      case 'strong':
        return (
          <Text key={i} style={styles.strong}>
            {renderInline((token as Tokens.Strong).tokens, theme)}
          </Text>
        );
      case 'em':
        return (
          <Text key={i} style={styles.em}>
            {renderInline((token as Tokens.Em).tokens, theme)}
          </Text>
        );
      case 'del':
        return (
          <Text key={i} style={styles.del}>
            {renderInline((token as Tokens.Del).tokens, theme)}
          </Text>
        );
      case 'codespan':
        return (
          <Text key={i} style={[styles.codespan, { backgroundColor: theme.codeBackground }]}>
            {decodeEntities((token as Tokens.Codespan).text)}
          </Text>
        );
      case 'link': {
        const link = token as Tokens.Link;
        return (
          <Text
            key={i}
            accessibilityRole="link"
            style={[styles.link, { color: theme.ink }]}
            onPress={() => Linking.openURL(link.href)}
          >
            {renderInline(link.tokens, theme)}
          </Text>
        );
      }
      case 'br':
        return '\n';
      case 'text': {
        const text = token as Tokens.Text;
        return text.tokens ? (
          <Text key={i}>{renderInline(text.tokens, theme)}</Text>
        ) : (
          decodeEntities(text.text)
        );
      }
      case 'escape':
        return decodeEntities((token as Tokens.Escape).text);
      default:
        return 'raw' in token ? token.raw : null;
    }
  });
}

const styles = StyleSheet.create({
  blocks: { gap: 14 },
  body: { fontSize: 16, lineHeight: 24 },
  h2: { fontSize: 24, fontWeight: '800', lineHeight: 30, marginTop: 6 },
  h3: { fontSize: 19, fontWeight: '700', lineHeight: 26, marginTop: 4 },
  strong: { fontWeight: '700' },
  em: { fontStyle: 'italic' },
  del: { textDecorationLine: 'line-through' },
  codespan: { fontFamily: monoFont, fontSize: 15 },
  link: { textDecorationLine: 'underline' },
  codeGroup: { gap: 8 },
  quote: { borderLeftWidth: 4, paddingVertical: 8, paddingHorizontal: 12, borderRadius: 6, gap: 8 },
  list: { gap: 6 },
  listItem: { flexDirection: 'row', gap: 8 },
  bullet: { minWidth: 18 },
  listBody: { flex: 1, gap: 6 },
  table: { borderWidth: 1, borderRadius: 6 },
  tableRow: { flexDirection: 'row' },
  cell: { minWidth: 96, paddingVertical: 6, paddingHorizontal: 10, borderRightWidth: 1, borderBottomWidth: 1, fontSize: 15 },
  headerCell: { fontWeight: '700' },
  hr: { height: 1 },
});
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

Two things to check if a test fails:
- If `screen.getByText('<?php')` fails because marked already decodes entities in codespans, `decodeEntities` is harmless; check what the token holds before changing anything.
- If a `Tokens.*` type name differs in marked 18, use the one marked exports (`node_modules/marked/lib/marked.d.ts`) and say so in the report.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/markdown
git commit -m "Render lesson Markdown natively with highlighted code"
```

---

### Task 5: Loading states, course list and course screen

**Files:**
- Create: `mobile/src/components/LoadState.tsx`, `mobile/src/screens/HomeScreen.tsx`, `mobile/src/screens/CourseScreen.tsx`, `mobile/src/test/fixtures.ts`, `mobile/src/test/render.tsx`, `mobile/app/courses/[course]/index.tsx`, tests `mobile/src/screens/__tests__/HomeScreen.test.tsx`, `mobile/src/screens/__tests__/CourseScreen.test.tsx`
- Modify: `mobile/app/index.tsx`

**Interfaces:**
- Consumes: `api`, `ApiError` and the types (Task 2); `useLessonProgress`, `lessonSummary`, `updateLesson` (Task 3); `Button`, `useTheme`, `plural` (Task 1).
- Produces:
  - `LoadState({ loading, error, notFound?, onRetry, children })`.
  - `HomeScreen()` and `CourseScreen({ slug })`.
  - `renderWithClient(ui)` from `src/test/render.tsx`.
  - Fixtures `sqlLesson`, `phpLesson`, `courses`, `sqlCourse` from `src/test/fixtures.ts`.

- [ ] **Step 1: Write the test helpers and fixtures**

Create `mobile/src/test/render.tsx`:

```tsx
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render } from '@testing-library/react-native';
import type { ReactElement, ReactNode } from 'react';

/**
 * Renders with a fresh QueryClient that doesn't retry, so errors show immediately.
 * The client goes in as a wrapper, so rerender() keeps it.
 */
export function renderWithClient(ui: ReactElement) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false, gcTime: Infinity } } });
  const wrapper = ({ children }: { children: ReactNode }) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
  );
  return render(ui, { wrapper });
}
```

Create `mobile/src/test/fixtures.ts`:

```ts
import type { Course, CourseSummary, LessonResponse } from '../api/types';

export const courses: CourseSummary[] = [
  { id: 1, slug: 'sql', title: 'SQL', description: 'Ask questions of your data.', language: 'sql', lessons_count: 1 },
  { id: 2, slug: 'php-oop', title: 'OOP in PHP', description: 'Organise your code around objects.', language: 'php', lessons_count: 1 },
];

export const sqlCourse: Course = {
  ...courses[0],
  lessons: [
    {
      id: 1,
      slug: 'your-first-queries',
      title: 'Your first queries',
      summary: 'Read data from a table with SELECT.',
      position: 1,
      quiz_questions_count: 2,
      challenges_count: 2,
    },
  ],
};

export const sqlLesson: LessonResponse = {
  data: {
    id: 1,
    slug: 'your-first-queries',
    title: 'Your first queries',
    summary: 'Read data from a table with SELECT.',
    position: 1,
    content: 'Intro text.\n\n## Selecting columns\n\nUse `SELECT`.\n\n```sql\nSELECT title FROM books;\n```\n\n## Filtering rows\n\nUse WHERE.\n',
    starter_code: 'SELECT * FROM books;',
    course: courses[0],
    quiz_questions: [
      { id: 11, question: 'Which clause picks the table?', code: null, options: ['`SELECT`', '`FROM`'] },
      { id: 12, question: 'What does this return?', code: 'SELECT 1;', options: ['1', 'Nothing'] },
    ],
    challenges: [
      {
        id: 21,
        title: 'Titles and authors',
        prompt: 'List the `title` and `author` of every book.',
        starter_code: 'SELECT\nFROM books;',
        hint: 'Put the two column names after SELECT.',
        order_matters: false,
        checks: null,
      },
      {
        id: 22,
        title: 'Cheap books',
        prompt: 'Books under 10.',
        starter_code: '',
        hint: null,
        order_matters: true,
        checks: null,
      },
    ],
  },
  schema: [
    {
      name: 'books',
      row_count: 16,
      columns: [
        { name: 'id', type: 'INTEGER', primary_key: true },
        { name: 'title', type: 'TEXT', primary_key: false },
      ],
    },
  ],
  navigation: { previous: null, next: null },
};

export const phpLesson: LessonResponse = {
  data: {
    id: 2,
    slug: 'classes-and-objects',
    title: 'Classes and objects',
    summary: 'Describe things with classes.',
    position: 1,
    content: '## Classes\n\n```php\necho "hi";\n```\n',
    starter_code: '<?php\n\necho "start";',
    course: courses[1],
    quiz_questions: [{ id: 41, question: 'What creates an object?', code: null, options: ['`new`', '`class`'] }],
    challenges: [
      {
        id: 31,
        title: 'Your first class',
        prompt: 'Create a `Book` class.',
        starter_code: '<?php\n',
        hint: null,
        order_matters: false,
        checks: [
          { label: 'Book exists', code: 'return true;' },
          { label: 'Book has a title', code: 'return true;' },
        ],
      },
    ],
  },
  schema: [],
  navigation: { previous: null, next: null },
};
```

- [ ] **Step 2: Write the failing tests**

Create `mobile/src/screens/__tests__/HomeScreen.test.tsx`:

```tsx
import { fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { courses } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { HomeScreen } from '../HomeScreen';

afterEach(() => jest.restoreAllMocks());

test('lists courses and opens one', async () => {
  jest.spyOn(api, 'courses').mockResolvedValue(courses);
  await renderWithClient(<HomeScreen />);

  await fireEvent.press(await screen.findByRole('button', { name: /OOP in PHP/ }));

  expect(screen.getByText('Ask questions of your data.')).toBeTruthy();
  expect(router.push).toHaveBeenCalledWith('/courses/php-oop');
});

test('an unreachable server shows the message and Try again refetches', async () => {
  const courses$ = jest
    .spyOn(api, 'courses')
    .mockRejectedValueOnce(new ApiError('Could not reach the server. Check your connection and try again.', 0))
    .mockResolvedValueOnce(courses);
  await renderWithClient(<HomeScreen />);

  expect(await screen.findByText('Could not reach the server. Check your connection and try again.')).toBeTruthy();
  await fireEvent.press(screen.getByRole('button', { name: 'Try again' }));

  expect(await screen.findByText('SQL')).toBeTruthy();
  expect(courses$).toHaveBeenCalledTimes(2);
});
```

Create `mobile/src/screens/__tests__/CourseScreen.test.tsx`:

```tsx
import { act, fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { updateLesson } from '../../progress/store';
import { sqlCourse } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { CourseScreen } from '../CourseScreen';

afterEach(() => jest.restoreAllMocks());

test('lists lessons with what they contain and opens one', async () => {
  jest.spyOn(api, 'course').mockResolvedValue(sqlCourse);
  await renderWithClient(<CourseScreen slug="sql" />);

  expect(await screen.findByText('Your first queries')).toBeTruthy();
  expect(screen.getByText('Reading, a live sandbox, 2 quiz questions and 2 challenges.')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: /Your first queries/ }));
  expect(router.push).toHaveBeenCalledWith('/courses/sql/lessons/your-first-queries');
});

test('shows progress for started and completed lessons', async () => {
  jest.spyOn(api, 'course').mockResolvedValue(sqlCourse);
  await renderWithClient(<CourseScreen slug="sql" />);
  await screen.findByText('Your first queries');

  await act(async () => updateLesson(1, () => ({ read: true })));
  expect(screen.getByText('Read · quiz 0/2 · challenges 0/2')).toBeTruthy();

  await act(async () =>
    updateLesson(1, () => ({
      quiz: {
        11: { option: 1, correct: true, correct_option: 1, explanation: '' },
        12: { option: 0, correct: true, correct_option: 0, explanation: '' },
      },
      challenges: { 21: { passed: true, code: null }, 22: { passed: true, code: null } },
    })),
  );
  expect(screen.getByText('Completed.')).toBeTruthy();
});

test('an unknown course says so without a retry button', async () => {
  jest.spyOn(api, 'course').mockRejectedValue(new ApiError('Not Found', 404));
  await renderWithClient(<CourseScreen slug="nope" />);

  expect(await screen.findByText("We couldn't find that course.")).toBeTruthy();
  expect(screen.queryByRole('button', { name: 'Try again' })).toBeNull();
});
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/screens`
Expected: FAIL, because the screens don't exist.

- [ ] **Step 4: Write `LoadState` and the screens**

Create `mobile/src/components/LoadState.tsx`:

```tsx
import type { ReactNode } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { ApiError } from '../api/client';
import { useTheme } from '../theme';
import { Button } from './Button';

type Props = {
  loading: boolean;
  error: unknown;
  notFound?: string;
  onRetry: () => void;
  children: ReactNode;
};

/** Loading spinner, error with Try again, or "not found" (which retrying can't fix). */
export function LoadState({ loading, error, notFound = "We couldn't find that page.", onRetry, children }: Props) {
  const theme = useTheme();

  if (error) {
    const status = error instanceof ApiError ? error.status : null;
    const message =
      status === 404 ? notFound : error instanceof Error ? error.message : 'Something went wrong. Try again.';

    return (
      <View style={styles.center}>
        <Text accessibilityRole="alert" style={[styles.message, { color: theme.ink }]}>
          {message}
        </Text>
        {status === 404 ? null : <Button title="Try again" onPress={onRetry} />}
      </View>
    );
  }

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator accessibilityLabel="Loading" color={theme.muted} />
      </View>
    );
  }

  return <>{children}</>;
}

const styles = StyleSheet.create({
  center: { flex: 1, padding: 24, gap: 16, alignItems: 'center', justifyContent: 'center' },
  message: { fontSize: 16, textAlign: 'center', lineHeight: 24 },
});
```

Create `mobile/src/screens/HomeScreen.tsx`:

```tsx
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../api/client';
import { LoadState } from '../components/LoadState';
import { plural } from '../lib/text';
import { useTheme } from '../theme';

export function HomeScreen() {
  const theme = useTheme();
  const query = useQuery({ queryKey: ['courses'], queryFn: api.courses });

  return (
    <ScrollView contentContainerStyle={styles.page}>
      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        Learn programming by running real code.
      </Text>
      <Text style={[styles.intro, { color: theme.muted }]}>
        Each lesson pairs a short explanation with a live sandbox, a quiz, and challenges that check your answers by
        running your code.
      </Text>
      <LoadState loading={query.isPending} error={query.error} onRetry={() => query.refetch()}>
        <View style={styles.list}>
          {query.data?.map((course) => (
            <Pressable
              key={course.id}
              accessibilityRole="button"
              accessibilityLabel={`${course.title}, ${plural(course.lessons_count ?? 0, 'lesson')}`}
              onPress={() => router.push(`/courses/${course.slug}`)}
              style={({ pressed }) => [
                styles.card,
                { backgroundColor: theme.surface, borderColor: theme.line, opacity: pressed ? 0.8 : 1 },
              ]}
            >
              <Text style={[styles.cardTitle, { color: theme.ink }]}>{course.title}</Text>
              <Text style={[styles.meta, { color: theme.muted }]}>
                {plural(course.lessons_count ?? 0, 'lesson')} · {course.language === 'sql' ? 'SQL' : 'PHP'}
              </Text>
              <Text style={[styles.description, { color: theme.ink }]}>{course.description}</Text>
            </Pressable>
          ))}
        </View>
      </LoadState>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, flexGrow: 1 },
  title: { fontSize: 28, fontWeight: '800', lineHeight: 34 },
  intro: { fontSize: 16, lineHeight: 24 },
  list: { gap: 12 },
  card: { borderWidth: 1, borderRadius: 14, padding: 16, gap: 6 },
  cardTitle: { fontSize: 20, fontWeight: '700' },
  meta: { fontSize: 14 },
  description: { fontSize: 16, lineHeight: 23 },
});
```

Create `mobile/src/screens/CourseScreen.tsx`:

```tsx
import { useQuery } from '@tanstack/react-query';
import { router, Stack } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../api/client';
import type { LessonSummary as Lesson } from '../api/types';
import { LoadState } from '../components/LoadState';
import { plural } from '../lib/text';
import { useLessonProgress } from '../progress/store';
import { lessonSummary } from '../progress/summary';
import { useTheme } from '../theme';

export function CourseScreen({ slug }: { slug: string }) {
  const theme = useTheme();
  const query = useQuery({ queryKey: ['course', slug], queryFn: () => api.course(slug) });

  return (
    <>
      <Stack.Screen options={{ title: query.data?.title ?? '' }} />
      <LoadState
        loading={query.isPending}
        error={query.error}
        notFound="We couldn't find that course."
        onRetry={() => query.refetch()}
      >
        {query.data ? (
          <ScrollView contentContainerStyle={styles.page}>
            <Text style={[styles.description, { color: theme.muted }]}>{query.data.description}</Text>
            {query.data.lessons.map((lesson) => (
              <LessonRow key={lesson.id} lesson={lesson} courseSlug={slug} />
            ))}
          </ScrollView>
        ) : null}
      </LoadState>
    </>
  );
}

function LessonRow({ lesson, courseSlug }: { lesson: Lesson; courseSlug: string }) {
  const theme = useTheme();
  const summary = lessonSummary(lesson, useLessonProgress(lesson.id));
  const progress = summary.complete
    ? 'Completed.'
    : summary.started
      ? `${summary.read ? 'Read' : 'Not read yet'} · quiz ${summary.quizCorrect}/${summary.quizTotal} · challenges ${summary.challengesPassed}/${summary.challengeTotal}`
      : null;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Lesson ${lesson.position}: ${lesson.title}`}
      onPress={() => router.push(`/courses/${courseSlug}/lessons/${lesson.slug}`)}
      style={({ pressed }) => [
        styles.lesson,
        { backgroundColor: theme.surface, borderColor: theme.line, opacity: pressed ? 0.8 : 1 },
      ]}
    >
      <Text style={[styles.number, { color: theme.muted }]}>{lesson.position}</Text>
      <View style={styles.body}>
        <Text style={[styles.lessonTitle, { color: theme.ink }]}>{lesson.title}</Text>
        <Text style={[styles.text, { color: theme.ink }]}>{lesson.summary}</Text>
        <Text style={[styles.meta, { color: theme.muted }]}>
          {`Reading, a live sandbox, ${plural(lesson.quiz_questions_count, 'quiz question')} and ${plural(lesson.challenges_count, 'challenge')}.`}
        </Text>
        {progress ? (
          <Text style={[styles.meta, { color: summary.complete ? theme.ok : theme.ink }]}>{progress}</Text>
        ) : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 12 },
  description: { fontSize: 16, lineHeight: 24, marginBottom: 4 },
  lesson: { flexDirection: 'row', gap: 12, borderWidth: 1, borderRadius: 14, padding: 16 },
  number: { fontSize: 22, fontWeight: '800', minWidth: 24 },
  body: { flex: 1, gap: 6 },
  lessonTitle: { fontSize: 18, fontWeight: '700' },
  text: { fontSize: 16, lineHeight: 23 },
  meta: { fontSize: 14, lineHeight: 20 },
});
```

Text that tests match exactly is built as a single template string, so it renders as one text node.

Replace `mobile/app/index.tsx`:

```tsx
import { HomeScreen } from '../src/screens/HomeScreen';

export default function Index() {
  return <HomeScreen />;
}
```

Create `mobile/app/courses/[course]/index.tsx`:

```tsx
import { useLocalSearchParams } from 'expo-router';
import { CourseScreen } from '../../../src/screens/CourseScreen';

export default function CourseRoute() {
  const { course } = useLocalSearchParams<{ course: string }>();
  return <CourseScreen slug={course} />;
}
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

`useLocalSearchParams` is only used in route files, which the tests don't import, so the `expo-router` mock doesn't need it.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile
git commit -m "Add course list and course screens"
```

---

### Task 6: Learn step

**Files:**
- Create: `mobile/src/components/lesson/LearnStep.tsx`, `mobile/src/components/lesson/__tests__/LearnStep.test.tsx`

**Interfaces:**
- Consumes: `Markdown` (Task 4); `splitSections` (Task 3); `useLessonProgress`, `updateLesson` (Task 3); `Button` (Task 1); `Lesson`, `Language` (Task 2).
- Produces: `LearnStep({ lesson: Lesson; language: Language; onTry: (code: string) => void; onFinish: () => void; onSkip: () => void })`. The current page is saved to `theoryPage`.

- [ ] **Step 1: Write the failing test**

Create `mobile/src/components/lesson/__tests__/LearnStep.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { useProgressStore } from '../../../progress/store';
import { sqlLesson } from '../../../test/fixtures';
import { LearnStep } from '../LearnStep';

const lesson = sqlLesson.data;

function renderStep(overrides: Partial<Parameters<typeof LearnStep>[0]> = {}) {
  const props = { lesson, language: 'sql' as const, onTry: jest.fn(), onFinish: jest.fn(), onSkip: jest.fn(), ...overrides };
  return render(<LearnStep {...props} />).then(() => props);
}

test('shows the first section and moves to the next', async () => {
  await renderStep();

  expect(screen.getByText('Section 1 of 2')).toBeTruthy();
  expect(screen.getByText('Selecting columns')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: 'Next: Filtering rows' }));

  expect(screen.getByText('Section 2 of 2')).toBeTruthy();
  expect(useProgressStore.getState().lessons[1].theoryPage).toBe(2);
});

test('Try sends the code block to the sandbox', async () => {
  const props = await renderStep();

  await fireEvent.press(screen.getByRole('button', { name: 'Try it in the sandbox' }));

  expect(props.onTry).toHaveBeenCalledWith('SELECT title FROM books;');
});

test('the last section offers the sandbox and the quiz', async () => {
  useProgressStore.setState({ lessons: { 1: { read: false, theoryPage: 2, sandboxCode: null, quiz: {}, challenges: {} } } });
  const props = await renderStep();

  await fireEvent.press(screen.getByRole('button', { name: 'Mark as read and open the sandbox' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Go to the quiz' }));

  expect(props.onFinish).toHaveBeenCalled();
  expect(props.onSkip).toHaveBeenCalled();
});

test('a saved page past the end falls back to the last section', async () => {
  useProgressStore.setState({ lessons: { 1: { read: true, theoryPage: 9, sandboxCode: null, quiz: {}, challenges: {} } } });
  await renderStep();

  expect(screen.getByText('Section 2 of 2')).toBeTruthy();
  expect(screen.getByRole('button', { name: 'Open the sandbox' })).toBeTruthy();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `cd mobile && npx jest src/components/lesson/__tests__/LearnStep.test.tsx`
Expected: FAIL, because the module doesn't exist.

- [ ] **Step 3: Write `LearnStep`**

Create `mobile/src/components/lesson/LearnStep.tsx`:

```tsx
import { useEffect, useMemo, useRef } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Language, Lesson } from '../../api/types';
import { splitSections } from '../../lesson/sections';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { Markdown } from '../markdown/Markdown';

type Props = {
  lesson: Lesson;
  language: Language;
  onTry: (code: string) => void;
  onFinish: () => void;
  onSkip: () => void;
};

/** The lesson theory, one "## " section per page, as in the web app. */
export function LearnStep({ lesson, language, onTry, onFinish, onSkip }: Props) {
  const theme = useTheme();
  const progress = useLessonProgress(lesson.id);
  const sections = useMemo(() => splitSections(lesson.content, lesson.title), [lesson.content, lesson.title]);
  const page = Math.min(Math.max(progress.theoryPage, 1), sections.length);
  const scroll = useRef<ScrollView>(null);

  useEffect(() => {
    scroll.current?.scrollTo({ y: 0, animated: false });
  }, [page]);

  const setPage = (next: number) => updateLesson(lesson.id, () => ({ theoryPage: next }));
  const current = sections[page - 1];
  const previous = sections[page - 2];
  const next = sections[page];

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page}>
      <Text style={[styles.position, { color: theme.muted }]}>{`Section ${page} of ${sections.length}`}</Text>
      <Markdown source={current.source} runnable={language} onTry={onTry} />
      <View style={styles.nav}>
        {previous ? <Button title={`Previous: ${previous.title}`} onPress={() => setPage(page - 1)} /> : null}
        {next ? <Button variant="primary" title={`Next: ${next.title}`} onPress={() => setPage(page + 1)} /> : null}
      </View>
      {next ? null : (
        <View style={[styles.finish, { borderColor: theme.line }]}>
          <Text style={[styles.finishText, { color: theme.ink }]}>
            That's the end of the reading. Try what you've learned in the sandbox, then take the quiz.
          </Text>
          <Button
            variant="primary"
            title={progress.read ? 'Open the sandbox' : 'Mark as read and open the sandbox'}
            onPress={onFinish}
          />
          <Button title="Go to the quiz" onPress={onSkip} />
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 18, paddingBottom: 48 },
  position: { fontSize: 14 },
  nav: { gap: 10 },
  finish: { borderTopWidth: 1, paddingTop: 18, gap: 10 },
  finishText: { fontSize: 16, lineHeight: 24 },
});
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/lesson
git commit -m "Add the Learn step"
```

---

### Task 7: Quiz step

**Files:**
- Create: `mobile/src/components/lesson/QuizStep.tsx`, `mobile/src/components/lesson/__tests__/QuizStep.test.tsx`

**Interfaces:**
- Consumes: `api.answer` (Task 2); `useLessonProgress`, `updateLesson` (Task 3); `Markdown`, `CodeBlock` (Task 4); `Button` (Task 1).
- Produces: `QuizStep({ lesson: Lesson; language: Language; onNext: () => void; onTry: (code: string) => void })`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/components/lesson/__tests__/QuizStep.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import { useProgressStore } from '../../../progress/store';
import { sqlLesson } from '../../../test/fixtures';
import { QuizStep } from '../QuizStep';

const lesson = sqlLesson.data;

afterEach(() => jest.restoreAllMocks());

async function renderQuiz(overrides: Partial<Parameters<typeof QuizStep>[0]> = {}) {
  const props = { lesson, language: 'sql' as const, onNext: jest.fn(), onTry: jest.fn(), ...overrides };
  await render(<QuizStep {...props} />);
  return props;
}

test('a correct answer shows the explanation and is saved', async () => {
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 1, explanation: 'FROM names the table.' });
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer B' }));

  expect(await screen.findByText('Correct.')).toBeTruthy();
  expect(screen.getByText('FROM names the table.')).toBeTruthy();
  expect(api.answer).toHaveBeenCalledWith(11, 1);
  expect(useProgressStore.getState().lessons[1].quiz[11]).toEqual({
    option: 1,
    correct: true,
    correct_option: 1,
    explanation: 'FROM names the table.',
  });
});

test('a wrong answer says Not quite', async () => {
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: false, correct_option: 1, explanation: 'It is FROM.' });
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));

  expect(await screen.findByText('Not quite.')).toBeTruthy();
});

test('tapping twice while the answer is on its way sends one request', async () => {
  jest.spyOn(api, 'answer').mockReturnValue(new Promise(() => {}));
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Answer B' }));

  expect(api.answer).toHaveBeenCalledTimes(1);
});

test('an error is shown and the question stays open', async () => {
  jest.spyOn(api, 'answer').mockRejectedValue(new ApiError('Could not reach the server. Check your connection and try again.', 0));
  await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));

  expect(await screen.findByText('Could not reach the server. Check your connection and try again.')).toBeTruthy();
  expect(useProgressStore.getState().lessons[1]).toBeUndefined();
});

test('resumes at the first unanswered question and offers the challenges when done', async () => {
  useProgressStore.setState({
    lessons: {
      1: {
        read: false,
        theoryPage: 1,
        sandboxCode: null,
        quiz: { 11: { option: 1, correct: true, correct_option: 1, explanation: 'x' } },
        challenges: {},
      },
    },
  });
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 0, explanation: 'One.' });
  const props = await renderQuiz();

  expect(screen.getByText('What does this return?')).toBeTruthy();
  await fireEvent.press(screen.getByRole('button', { name: 'Answer A' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Go to the challenges' }));

  expect(props.onNext).toHaveBeenCalled();
  expect(screen.getByText(/You got 2 of 2 right/)).toBeTruthy();
});

test('after answering, a question with code can be tried in the sandbox', async () => {
  useProgressStore.setState({
    lessons: {
      1: {
        read: false,
        theoryPage: 1,
        sandboxCode: null,
        quiz: {
          11: { option: 1, correct: true, correct_option: 1, explanation: 'x' },
          12: { option: 0, correct: true, correct_option: 0, explanation: 'y' },
        },
        challenges: {},
      },
    },
  });
  const props = await renderQuiz();

  await fireEvent.press(screen.getByRole('button', { name: 'Question 2' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Try it in the sandbox' }));

  expect(props.onTry).toHaveBeenCalledWith('SELECT 1;');
});

test('a lesson without questions says so', async () => {
  await renderQuiz({ lesson: { ...lesson, quiz_questions: [] } });

  expect(screen.getByText('This lesson has no quiz yet.')).toBeTruthy();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/components/lesson/__tests__/QuizStep.test.tsx`
Expected: FAIL, because the module doesn't exist.

- [ ] **Step 3: Write `QuizStep`**

Create `mobile/src/components/lesson/QuizStep.tsx`:

```tsx
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import type { Language, Lesson } from '../../api/types';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { CodeBlock } from '../markdown/CodeBlock';
import { Markdown } from '../markdown/Markdown';

type Props = { lesson: Lesson; language: Language; onNext: () => void; onTry: (code: string) => void };

const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

/** One question per page; answers are checked on the server and kept in progress. */
export function QuizStep({ lesson, language, onNext, onTry }: Props) {
  const theme = useTheme();
  const questions = lesson.quiz_questions;
  const progress = useLessonProgress(lesson.id);
  const [page, setPage] = useState(() => {
    const firstOpen = questions.findIndex((q) => !progress.quiz[q.id]);
    return firstOpen === -1 ? 0 : firstOpen;
  });
  const [pending, setPending] = useState(false);
  const [error, setError] = useState('');

  if (questions.length === 0) {
    return (
      <View style={styles.page}>
        <Text style={[styles.text, { color: theme.ink }]}>This lesson has no quiz yet.</Text>
      </View>
    );
  }

  const question = questions[page];
  const answer = progress.quiz[question.id];
  const total = questions.length;
  const answered = questions.filter((q) => progress.quiz[q.id]).length;
  const correct = questions.filter((q) => progress.quiz[q.id]?.correct).length;
  const isLast = page === total - 1;

  function goTo(next: number) {
    setError('');
    setPage(next);
  }

  async function choose(option: number) {
    if (answer || pending) return;
    setPending(true);
    setError('');
    try {
      const result = await api.answer(question.id, option);
      updateLesson(lesson.id, (current) => ({ quiz: { ...current.quiz, [question.id]: { option, ...result } } }));
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      setPending(false);
    }
  }

  function optionColors(index: number) {
    if (!answer) return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 1 };
    if (index === answer.correct_option) return { borderColor: theme.ok, backgroundColor: theme.okSoft, opacity: 1 };
    if (index === answer.option) return { borderColor: theme.bad, backgroundColor: theme.badSoft, opacity: 1 };
    return { borderColor: theme.line, backgroundColor: theme.surface, opacity: 0.55 };
  }

  return (
    <ScrollView contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.score, { color: theme.ink }]}>
        {`${correct} of ${total} correct${answered < total ? `, ${total - answered} to go` : ''}`}
      </Text>

      <View style={styles.pager}>
        {questions.map((q, i) => {
          const record = progress.quiz[q.id];
          const color = record ? (record.correct ? theme.ok : theme.bad) : theme.line;
          return (
            <Pressable
              key={q.id}
              accessibilityRole="button"
              accessibilityLabel={`Question ${i + 1}`}
              accessibilityState={{ selected: i === page }}
              onPress={() => goTo(i)}
              style={[
                styles.dot,
                { borderColor: i === page ? theme.ink : color, backgroundColor: record ? color : theme.surface },
              ]}
            >
              <Text style={[styles.dotText, { color: record ? theme.surface : theme.ink }]}>{i + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <View style={styles.question}>
        <Text style={[styles.questionNumber, { color: theme.muted }]}>{`${page + 1}.`}</Text>
        <View style={styles.optionText}>
          <Markdown source={question.question} inline />
        </View>
      </View>

      {question.code ? (
        <View style={styles.codeGroup}>
          <CodeBlock code={question.code} language={language} />
          {/* The sandbox link appears once answered, so running the code can't give the answer away. */}
          {answer ? <Button title="Try it in the sandbox" onPress={() => onTry(question.code ?? '')} /> : null}
        </View>
      ) : null}

      <View style={styles.options}>
        {question.options.map((option, i) => (
          <Pressable
            key={i}
            accessibilityRole="button"
            accessibilityLabel={`Answer ${LETTERS[i]}`}
            accessibilityState={{ disabled: Boolean(answer) || pending, selected: answer?.option === i }}
            disabled={Boolean(answer) || pending}
            onPress={() => choose(i)}
            style={[styles.option, optionColors(i)]}
          >
            <Text style={[styles.letter, { color: theme.muted }]}>{LETTERS[i]}</Text>
            <View style={styles.optionText}>
              <Markdown source={option} inline />
            </View>
          </Pressable>
        ))}
      </View>

      {error ? (
        <Text accessibilityRole="alert" style={[styles.text, { color: theme.bad }]}>
          {error}
        </Text>
      ) : null}

      {answer ? (
        <View
          style={[
            styles.feedback,
            { backgroundColor: answer.correct ? theme.okSoft : theme.badSoft, borderColor: answer.correct ? theme.ok : theme.bad },
          ]}
        >
          <Text style={[styles.feedbackTitle, { color: theme.ink }]}>{answer.correct ? 'Correct.' : 'Not quite.'}</Text>
          <Markdown source={answer.explanation} inline />
        </View>
      ) : null}

      <View style={styles.nav}>
        {page > 0 ? <Button title="Previous question" onPress={() => goTo(page - 1)} /> : null}
        {isLast ? null : (
          <Button
            variant={answer ? 'primary' : 'default'}
            title={answer ? 'Next question' : 'Skip for now'}
            onPress={() => goTo(page + 1)}
          />
        )}
      </View>

      {isLast && answered === total ? (
        <View style={styles.done}>
          <Text style={[styles.text, { color: theme.ink }]}>
            {`You got ${correct} of ${total} right. ${correct === total ? 'Nicely done.' : 'Red numbers above mark the ones to review. Try again whenever you like.'}`}
          </Text>
          <Button variant="primary" title="Go to the challenges" onPress={onNext} />
          <Button
            title="Retake the quiz"
            onPress={() => {
              updateLesson(lesson.id, () => ({ quiz: {} }));
              goTo(0);
            }}
          />
        </View>
      ) : null}

      {isLast && answered < total ? (
        <Text style={[styles.text, { color: theme.muted }]}>
          {`${total - answered} ${total - answered === 1 ? 'question is' : 'questions are'} still unanswered. Use the numbers above to go back.`}
        </Text>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 16, paddingBottom: 48 },
  text: { fontSize: 16, lineHeight: 24 },
  score: { fontSize: 16, fontWeight: '600' },
  pager: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  dot: { width: 36, height: 36, borderRadius: 18, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  dotText: { fontSize: 14, fontWeight: '700' },
  codeGroup: { gap: 8 },
  question: { flexDirection: 'row', gap: 8 },
  questionNumber: { fontSize: 16, lineHeight: 24, fontWeight: '700' },
  options: { gap: 8 },
  option: { flexDirection: 'row', alignItems: 'center', gap: 12, borderWidth: 1, borderRadius: 8, padding: 12, minHeight: 48 },
  letter: { fontSize: 14, fontWeight: '700', width: 18 },
  optionText: { flex: 1 },
  feedback: { borderWidth: 1, borderRadius: 8, padding: 12, gap: 6 },
  feedbackTitle: { fontSize: 16, fontWeight: '700' },
  nav: { flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'space-between' },
  done: { gap: 10 },
});
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/lesson
git commit -m "Add the Quiz step"
```

---

### Task 8: Code editor (DOM component) and key bar

**Files:**
- Create: `mobile/src/editor/CodeMirrorView.tsx`, `mobile/src/editor/__mocks__/CodeMirrorView.tsx`, `mobile/src/editor/CodeEditor.tsx`, `mobile/src/editor/keys.ts`, `mobile/src/editor/keyBarStore.ts`, `mobile/src/editor/KeyBar.tsx`, tests `mobile/src/editor/__tests__/keys.test.ts`, `mobile/src/editor/__tests__/KeyBar.test.tsx`, `mobile/src/editor/__tests__/CodeEditor.test.tsx`
- Modify: `mobile/jest.setup.ts` (mock `CodeMirrorView`, reset the key bar store), `mobile/app/_layout.tsx` (render `KeyBarHost`)

**Interfaces:**
- Consumes: `Language` (Task 2); `useTheme` (Task 1).
- Produces:
  - `CodeEditor({ value: string; resetKey: number; language: Language; onChange: (value: string) => void; label: string; height?: number; onFocus?: () => void })`. `value` is applied **only when `resetKey` changes**; the editor otherwise keeps its own text and reports changes through `onChange`.
  - `EditorHandle` with `insert(text, cursorOffset)` and `focus()`.
  - `KEYS: Record<Language, EditorKey[]>`.
  - `useKeyBarStore`.
  - `KeyBar` and `KeyBarHost`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/editor/__tests__/keys.test.ts`:

```ts
import { KEYS } from '../keys';

test('PHP keys include the symbols phone keyboards hide', () => {
  expect(KEYS.php.map((k) => k.label)).toEqual(['Tab', '$', '->', ';', '{ }', '( )', '[ ]', "' '", '=>']);
});

test('SQL keys', () => {
  expect(KEYS.sql.map((k) => k.label)).toEqual(['Tab', '*', ';', '( )', "' '", ',', '=']);
});

test('paired keys put the cursor between the pair', () => {
  for (const key of [...KEYS.php, ...KEYS.sql]) {
    if (['{}', '()', '[]', "''"].includes(key.text)) {
      expect(key.cursorOffset).toBe(1);
    } else {
      expect(key.cursorOffset).toBe(key.text.length);
    }
  }
});

test('Tab inserts four spaces', () => {
  expect(KEYS.php[0]).toMatchObject({ text: '    ', cursorOffset: 4 });
});
```

Create `mobile/src/editor/__tests__/KeyBar.test.tsx`:

```tsx
import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { Keyboard } from 'react-native';
import { KeyBar, KeyBarHost } from '../KeyBar';
import { useKeyBarStore } from '../keyBarStore';

afterEach(() => jest.restoreAllMocks());

test('pressing a key sends its text and cursor offset', async () => {
  const onKey = jest.fn();
  await render(<KeyBar language="php" onKey={onKey} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Insert braces' }));

  expect(onKey).toHaveBeenCalledWith(expect.objectContaining({ text: '{}', cursorOffset: 1 }));
});

test('the host shows the bar only while an editor is focused and the keyboard is up', async () => {
  const listeners: Record<string, (e: { endCoordinates: { height: number } }) => void> = {};
  jest.spyOn(Keyboard, 'addListener').mockImplementation(((event: string, handler: never) => {
    listeners[event] = handler;
    return { remove: jest.fn() };
  }) as never);
  const insert = jest.fn();
  await render(<KeyBarHost />);

  expect(screen.queryByRole('button', { name: 'Insert tab' })).toBeNull();

  await act(async () => useKeyBarStore.getState().focus({ id: 'a', language: 'sql', insert }));
  await act(async () => Object.entries(listeners).find(([name]) => /Show/.test(name))?.[1]({ endCoordinates: { height: 300 } }));
  await fireEvent.press(screen.getByRole('button', { name: 'Insert tab' }));

  expect(insert).toHaveBeenCalledWith('    ', 4);

  await act(async () => useKeyBarStore.getState().blur('a'));
  expect(screen.queryByRole('button', { name: 'Insert tab' })).toBeNull();
});

test('blurring another editor does not clear the focused one', async () => {
  const insert = jest.fn();
  useKeyBarStore.getState().focus({ id: 'b', language: 'php', insert });
  useKeyBarStore.getState().blur('a');

  expect(useKeyBarStore.getState().active?.id).toBe('b');
});
```

Create `mobile/src/editor/__tests__/CodeEditor.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { CodeEditor } from '../CodeEditor';

test('reports edits and only takes a new value when resetKey changes', async () => {
  const onChange = jest.fn();
  const { rerender } = await render(
    <CodeEditor value="SELECT 1" resetKey={0} language="sql" onChange={onChange} label="Sandbox SQL editor" />,
  );
  const input = screen.getByLabelText('Sandbox SQL editor');

  await fireEvent.changeText(input, 'SELECT 2');
  expect(onChange).toHaveBeenCalledWith('SELECT 2');

  await rerender(<CodeEditor value="SELECT 1" resetKey={0} language="sql" onChange={onChange} label="Sandbox SQL editor" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT 2');

  await rerender(<CodeEditor value="SELECT 3" resetKey={1} language="sql" onChange={onChange} label="Sandbox SQL editor" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT 3');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/editor`
Expected: FAIL, because the modules don't exist.

- [ ] **Step 3: Write the keys, the store and the key bar**

Create `mobile/src/editor/keys.ts`:

```ts
import type { Language } from '../api/types';

/** Symbols that phone keyboards bury. cursorOffset is where the cursor lands, from the start of the inserted text. */
export interface EditorKey {
  label: string;
  text: string;
  cursorOffset: number;
  accessibilityLabel: string;
}

const key = (label: string, text: string, accessibilityLabel: string, cursorOffset = text.length): EditorKey => ({
  label,
  text,
  cursorOffset,
  accessibilityLabel,
});

const tab = key('Tab', '    ', 'Insert tab');
const parens = key('( )', '()', 'Insert parentheses', 1);
const quotes = key("' '", "''", 'Insert quotes', 1);
const semicolon = key(';', ';', 'Insert semicolon');

export const KEYS: Record<Language, EditorKey[]> = {
  php: [
    tab,
    key('$', '$', 'Insert dollar sign'),
    key('->', '->', 'Insert arrow'),
    semicolon,
    key('{ }', '{}', 'Insert braces', 1),
    parens,
    key('[ ]', '[]', 'Insert brackets', 1),
    quotes,
    key('=>', '=>', 'Insert double arrow'),
  ],
  sql: [
    tab,
    key('*', '*', 'Insert asterisk'),
    semicolon,
    parens,
    quotes,
    key(',', ',', 'Insert comma'),
    key('=', '=', 'Insert equals sign'),
  ],
};
```

Create `mobile/src/editor/keyBarStore.ts`:

```ts
import { create } from 'zustand';
import type { Language } from '../api/types';

/** The editor that has focus, so the one key bar above the keyboard can type into it. */
export interface ActiveEditor {
  id: string;
  language: Language;
  insert: (text: string, cursorOffset: number) => void;
}

interface KeyBarState {
  active: ActiveEditor | null;
  focus: (editor: ActiveEditor) => void;
  blur: (id: string) => void;
}

export const useKeyBarStore = create<KeyBarState>((set) => ({
  active: null,
  focus: (editor) => set({ active: editor }),
  blur: (id) => set((state) => (state.active?.id === id ? { active: null } : state)),
}));
```

Create `mobile/src/editor/KeyBar.tsx`:

```tsx
import { useEffect, useState } from 'react';
import { Keyboard, Platform, Pressable, ScrollView, StyleSheet, Text, View, type ViewStyle } from 'react-native';
import type { Language } from '../api/types';
import { monoFont, useTheme } from '../theme';
import { KEYS, type EditorKey } from './keys';
import { useKeyBarStore } from './keyBarStore';

export function KeyBar({ language, onKey, style }: { language: Language; onKey: (key: EditorKey) => void; style?: ViewStyle }) {
  const theme = useTheme();

  return (
    <View style={[styles.bar, { backgroundColor: theme.surface, borderColor: theme.line }, style]}>
      <ScrollView horizontal keyboardShouldPersistTaps="always" contentContainerStyle={styles.keys}>
        {KEYS[language].map((key) => (
          <Pressable
            key={key.label}
            accessibilityRole="button"
            accessibilityLabel={key.accessibilityLabel}
            onPress={() => onKey(key)}
            style={({ pressed }) => [styles.key, { backgroundColor: pressed ? theme.line : theme.codeBackground }]}
          >
            <Text style={[styles.label, { color: theme.ink }]}>{key.label}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

/** Rendered once at the root; floats the key bar above the keyboard while an editor has focus. */
export function KeyBarHost() {
  const active = useKeyBarStore((state) => state.active);
  const keyboardHeight = useKeyboardHeight();

  if (!active || keyboardHeight === 0) {
    return null;
  }

  return (
    <KeyBar
      language={active.language}
      onKey={(key) => active.insert(key.text, key.cursorOffset)}
      style={{ position: 'absolute', left: 0, right: 0, bottom: keyboardHeight }}
    />
  );
}

function useKeyboardHeight(): number {
  const [height, setHeight] = useState(0);

  useEffect(() => {
    const ios = Platform.OS === 'ios';
    const show = Keyboard.addListener(ios ? 'keyboardWillShow' : 'keyboardDidShow', (event) =>
      setHeight(event.endCoordinates.height),
    );
    const hide = Keyboard.addListener(ios ? 'keyboardWillHide' : 'keyboardDidHide', () => setHeight(0));
    return () => {
      show.remove();
      hide.remove();
    };
  }, []);

  return height;
}

const styles = StyleSheet.create({
  bar: { borderTopWidth: 1 },
  keys: { gap: 6, padding: 6 },
  key: { minWidth: 44, height: 40, paddingHorizontal: 10, borderRadius: 6, alignItems: 'center', justifyContent: 'center' },
  label: { fontFamily: monoFont, fontSize: 16, fontWeight: '600' },
});
```

- [ ] **Step 4: Write the DOM component, its Jest stand-in and the native wrapper**

Create `mobile/src/editor/CodeMirrorView.tsx`:

```tsx
'use dom';

import { php } from '@codemirror/lang-php';
import { sql } from '@codemirror/lang-sql';
import { EditorState } from '@codemirror/state';
import { oneDark } from '@codemirror/theme-one-dark';
import { EditorView, basicSetup } from 'codemirror';
import { useDOMImperativeHandle, type DOMImperativeFactory, type DOMProps } from 'expo/dom';
import { useEffect, useRef, type Ref } from 'react';

/**
 * CodeMirror 6 running in a WebView (an Expo DOM component). Text only flows
 * out, through onChange; the native side replaces the text by changing
 * resetKey. That way fast typing can never be overwritten by a stale value
 * coming back across the bridge.
 */
export interface EditorHandle extends DOMImperativeFactory {
  insert: (text: string, cursorOffset: number) => void;
  focus: () => void;
}

type Props = {
  initialValue: string;
  resetKey: number;
  language: 'sql' | 'php';
  dark: boolean;
  onChange: (value: string) => Promise<void>;
  onFocusChange: (focused: boolean) => Promise<void>;
  /** Names the editor for screen readers (and for the Jest stand-in). */
  accessibilityLabel?: string;
  ref?: Ref<EditorHandle>;
  dom?: DOMProps;
};

export default function CodeMirrorView({
  initialValue,
  resetKey,
  language,
  dark,
  onChange,
  onFocusChange,
  accessibilityLabel,
  ref,
}: Props) {
  const host = useRef<HTMLDivElement>(null);
  const view = useRef<EditorView | null>(null);
  const latest = useRef(initialValue);
  const appliedReset = useRef<number | null>(null);
  const callbacks = useRef({ onChange, onFocusChange });
  callbacks.current = { onChange, onFocusChange };

  useEffect(() => {
    document.body.style.margin = '0';
    document.body.style.background = dark ? '#282c34' : '#ffffff';

    // A new resetKey means new text from native; a theme or language change keeps what's typed.
    const doc = appliedReset.current === resetKey ? latest.current : initialValue;
    appliedReset.current = resetKey;
    latest.current = doc;

    const editor = new EditorView({
      parent: host.current!,
      state: EditorState.create({
        doc,
        extensions: [
          basicSetup,
          EditorView.lineWrapping,
          language === 'php' ? php() : sql(),
          dark ? oneDark : [],
          EditorView.theme({
            '&': { height: '100vh', fontSize: '15px' },
            '.cm-scroller': { fontFamily: 'Menlo, Consolas, monospace' },
          }),
          EditorView.updateListener.of((update) => {
            if (update.docChanged) {
              latest.current = update.state.doc.toString();
              callbacks.current.onChange(latest.current);
            }
            if (update.focusChanged) {
              callbacks.current.onFocusChange(update.view.hasFocus);
            }
          }),
        ],
      }),
    });
    view.current = editor;

    return () => {
      editor.destroy();
      view.current = null;
    };
    // initialValue is read only when resetKey changes, on purpose (see the comment above).
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resetKey, language, dark]);

  useDOMImperativeHandle(
    ref,
    () => ({
      insert(text: string, cursorOffset: number) {
        const editor = view.current;
        if (!editor) return;
        const { from, to } = editor.state.selection.main;
        editor.dispatch({ changes: { from, to, insert: text }, selection: { anchor: from + cursorOffset } });
        editor.focus();
      },
      focus() {
        view.current?.focus();
      },
    }),
    [],
  );

  return <div ref={host} aria-label={accessibilityLabel} style={{ height: '100vh' }} />;
}
```

Create `mobile/src/editor/__mocks__/CodeMirrorView.tsx`:

```tsx
import { useEffect, useImperativeHandle, useState, type Ref } from 'react';
import { TextInput } from 'react-native';
import type { EditorHandle } from '../CodeMirrorView';

/** Jest stand-in for the WebView editor: a TextInput with the same props and handle. */
export default function CodeMirrorView({
  initialValue,
  resetKey,
  onChange,
  onFocusChange,
  accessibilityLabel,
  ref,
}: {
  initialValue: string;
  resetKey: number;
  onChange: (value: string) => Promise<void> | void;
  onFocusChange: (focused: boolean) => Promise<void> | void;
  accessibilityLabel?: string;
  ref?: Ref<EditorHandle>;
}) {
  const [value, setValue] = useState(initialValue);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => setValue(initialValue), [resetKey]);

  useImperativeHandle(ref, () => ({
    insert: (text: string) => {
      setValue((current) => {
        const next = current + text;
        onChange(next);
        return next;
      });
    },
    focus: () => {},
  }));

  return (
    <TextInput
      testID="code-editor"
      accessibilityLabel={accessibilityLabel}
      value={value}
      onChangeText={(text) => {
        setValue(text);
        onChange(text);
      }}
      onFocus={() => onFocusChange(true)}
      onBlur={() => onFocusChange(false)}
    />
  );
}
```

Create `mobile/src/editor/CodeEditor.tsx`:

```tsx
import { useId, useRef } from 'react';
import { useColorScheme, View } from 'react-native';
import type { Language } from '../api/types';
import { useTheme } from '../theme';
import CodeMirrorView, { type EditorHandle } from './CodeMirrorView';
import { useKeyBarStore } from './keyBarStore';

type Props = {
  value: string;
  resetKey: number;
  language: Language;
  onChange: (value: string) => void;
  label: string;
  height?: number;
  onFocus?: () => void;
};

/** The code editor as a native view. value is applied only when resetKey changes. */
export function CodeEditor({ value, resetKey, language, onChange, label, height = 240, onFocus }: Props) {
  const theme = useTheme();
  const dark = useColorScheme() === 'dark';
  const id = useId();
  const handle = useRef<EditorHandle>(null);
  const { focus, blur } = useKeyBarStore.getState();

  return (
    <View
      accessible={false}
      style={{ height, borderWidth: 1, borderColor: theme.line, borderRadius: 8, overflow: 'hidden' }}
    >
      <CodeMirrorView
        ref={handle}
        initialValue={value}
        resetKey={resetKey}
        language={language}
        dark={dark}
        onChange={async (next) => onChange(next)}
        onFocusChange={async (focused) => {
          if (focused) {
            focus({ id, language, insert: (text, offset) => handle.current?.insert(text, offset) });
            onFocus?.();
          } else {
            blur(id);
          }
        }}
        accessibilityLabel={label}
        dom={{ style: { flex: 1 } }}
      />
    </View>
  );
}
```

In `mobile/jest.setup.ts`, add:

```ts
jest.mock('./src/editor/CodeMirrorView');

import { useKeyBarStore } from './src/editor/keyBarStore';

afterEach(() => {
  useKeyBarStore.setState({ active: null });
});
```

Put the `jest.mock` line with the other `jest.mock` calls at the top. Jest hoists them anyway.

In `mobile/app/_layout.tsx`, import `KeyBarHost` from `'../src/editor/KeyBar'` and render `<KeyBarHost />` right after `</Stack>`, inside the root `View`.

- [ ] **Step 5: Run the tests, typecheck and the bundle check**

```bash
cd /Users/macbook/Desktop/dev/learnP/mobile
npm test
npm run typecheck
npx expo export --platform android --output-dir /tmp/learnp-export && rm -rf /tmp/learnp-export
```

Expected: all pass. The export must succeed, because it proves Metro bundles the `'use dom'` component with CodeMirror.

If `expo/dom` exports `useDOMImperativeHandle`, `DOMImperativeFactory` or `DOMProps` under other names in SDK 57, import what it exports (`node_modules/expo/dom/index.d.ts`) and say so in the report.

- [ ] **Step 6: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile
git commit -m "Add CodeMirror editor as a DOM component with a native key bar"
```

---

### Task 9: Result views

**Files:**
- Create: `mobile/src/components/results/ResultTable.tsx`, `RunResults.tsx`, `PhpOutput.tsx`, `SchemaPanel.tsx`, `SqlCheckDetails.tsx`, `PhpCheckDetails.tsx`, and `mobile/src/components/results/__tests__/results.test.tsx`

**Interfaces:**
- Consumes: the API types (Task 2); `useTheme`, `monoFont`, `plural`, `Button` (Task 1).
- Produces:
  - `ResultTable({ columns, rows })`
  - `RunResults({ run: SqlRun })`
  - `PhpOutput({ output, fatal, durationMs? })`
  - `SchemaPanel({ tables: SchemaTable[]; onPreview: (table: string) => void })`
  - `SqlCheckDetails({ check: SqlCheck })`
  - `PhpCheckDetails({ check: PhpCheck })`

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/components/results/__tests__/results.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { sqlLesson } from '../../../test/fixtures';
import { PhpCheckDetails } from '../PhpCheckDetails';
import { PhpOutput } from '../PhpOutput';
import { ResultTable } from '../ResultTable';
import { RunResults } from '../RunResults';
import { SchemaPanel } from '../SchemaPanel';
import { SqlCheckDetails } from '../SqlCheckDetails';

test('ResultTable shows headers, values and NULL', async () => {
  await render(<ResultTable columns={['title', 'price']} rows={[['Dune', 9.5], ['Emma', null]]} />);

  expect(screen.getByText('title')).toBeTruthy();
  expect(screen.getByText('9.5')).toBeTruthy();
  expect(screen.getByText('NULL')).toBeTruthy();
});

test('RunResults describes rows, commands, truncation and errors', async () => {
  await render(
    <RunResults
      run={{
        results: [
          { type: 'command', sql: 'UPDATE books SET price = 1', affected_rows: 3, duration_ms: 1 },
          { type: 'rows', sql: 'SELECT x FROM n', columns: ['x'], rows: [[1]], truncated: true, duration_ms: 4 },
        ],
        error: { message: 'No such column: nope', statement: 2 },
      }}
    />,
  );

  expect(screen.getByText('UPDATE changed 3 rows.')).toBeTruthy();
  expect(screen.getByText('First 1 rows in 4 ms. Add a LIMIT to see specific rows.')).toBeTruthy();
  expect(screen.getByText('Statement 3: No such column: nope')).toBeTruthy();
});

test('RunResults with nothing to show says so', async () => {
  await render(<RunResults run={{ results: [], error: null }} />);

  expect(screen.getByText('Your SQL ran but returned nothing.')).toBeTruthy();
});

test('PhpOutput shows output, silence and fatal errors', async () => {
  const { rerender } = await render(<PhpOutput output={'hello\nWarning: nope'} fatal={false} durationMs={12} />);
  expect(screen.getByText('Ran in 12 ms.')).toBeTruthy();

  await rerender(<PhpOutput output="" fatal={false} durationMs={3} />);
  expect(screen.getByText("Your code ran but didn't print anything. Use echo to see values.")).toBeTruthy();

  await rerender(<PhpOutput output="Fatal error: boom" fatal />);
  expect(screen.getByText('PHP stopped with an error.')).toBeTruthy();
});

test('SchemaPanel lists tables and previews one', async () => {
  const onPreview = jest.fn();
  await render(<SchemaPanel tables={sqlLesson.schema} onPreview={onPreview} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Show tables' }));
  expect(screen.getByText('books')).toBeTruthy();
  expect(screen.getByText('id INTEGER, primary key')).toBeTruthy();

  await fireEvent.press(screen.getByRole('button', { name: 'Preview books' }));
  expect(onPreview).toHaveBeenCalledWith('books');
});

test('SqlCheckDetails compares your result with the expected one', async () => {
  await render(
    <SqlCheckDetails
      check={{
        passed: false,
        message: 'The result should have 2 columns, yours has 1.',
        run: { results: [{ type: 'rows', sql: 'SELECT title FROM books', columns: ['title'], rows: [['Dune']], truncated: false, duration_ms: 1 }], error: null },
        expected: { columns: ['title', 'author'], rows: [['Dune', 'Herbert']] },
      }}
    />,
  );

  expect(screen.getByText('Your result')).toBeTruthy();
  expect(screen.getByText('Expected result')).toBeTruthy();
  expect(screen.getByText('Herbert')).toBeTruthy();
});

test('PhpCheckDetails lists checks with their messages and the output', async () => {
  await render(
    <PhpCheckDetails
      check={{
        passed: false,
        loaded: true,
        error: null,
        output: 'hello',
        checks: [
          { label: 'Book exists', passed: true, message: null },
          { label: 'Book has a title', passed: false, message: "Expected 'Dune', got null." },
        ],
      }}
    />,
  );

  expect(screen.getByLabelText('Book exists (passed)')).toBeTruthy();
  expect(screen.getByLabelText('Book has a title (failed)')).toBeTruthy();
  expect(screen.getByText("Expected 'Dune', got null.")).toBeTruthy();
  expect(screen.getByText('What your code printed')).toBeTruthy();
});

test('PhpCheckDetails marks checks as not run when the code did not load', async () => {
  await render(
    <PhpCheckDetails
      check={{ passed: false, loaded: false, error: 'ParseError: syntax error on line 1', output: '', checks: [{ label: 'a', passed: false, message: 'Fix the error in your code first.' }] }}
    />,
  );

  expect(screen.getByText('ParseError: syntax error on line 1')).toBeTruthy();
  expect(screen.getByLabelText('a (not run)')).toBeTruthy();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/components/results`
Expected: FAIL, because the modules don't exist.

- [ ] **Step 3: Write the components**

Create `mobile/src/components/results/ResultTable.tsx`:

```tsx
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { Cell } from '../../api/types';
import { monoFont, useTheme } from '../../theme';

export function ResultTable({ columns, rows }: { columns: string[]; rows: Cell[][] }) {
  const theme = useTheme();

  return (
    <ScrollView horizontal style={[styles.box, { borderColor: theme.line, backgroundColor: theme.surface }]}>
      <View>
        <View style={[styles.row, { backgroundColor: theme.codeBackground }]}>
          {columns.map((column, i) => (
            <Text key={i} style={[styles.cell, styles.head, { color: theme.ink, borderColor: theme.line }]}>
              {column}
            </Text>
          ))}
        </View>
        {rows.map((row, r) => (
          <View key={r} style={styles.row}>
            {row.map((value, c) => (
              <Text
                key={c}
                style={[
                  styles.cell,
                  { color: value === null ? theme.muted : theme.ink, borderColor: theme.line },
                  value === null && styles.null,
                ]}
              >
                {value === null ? 'NULL' : String(value)}
              </Text>
            ))}
          </View>
        ))}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  box: { borderWidth: 1, borderRadius: 8 },
  row: { flexDirection: 'row' },
  cell: { minWidth: 88, maxWidth: 260, paddingVertical: 6, paddingHorizontal: 10, borderRightWidth: 1, borderBottomWidth: 1, fontFamily: monoFont, fontSize: 14 },
  head: { fontWeight: '700' },
  null: { fontStyle: 'italic' },
});
```

Create `mobile/src/components/results/RunResults.tsx`:

```tsx
import { StyleSheet, Text, View } from 'react-native';
import type { SqlResult, SqlRun } from '../../api/types';
import { plural } from '../../lib/text';
import { monoFont, useTheme } from '../../theme';
import { ResultTable } from './ResultTable';

/** Wording follows the web app's RunResults.vue. */
function rowsCaption(result: Extract<SqlResult, { type: 'rows' }>): string {
  const count = result.truncated ? `First ${result.rows.length} rows` : plural(result.rows.length, 'row');
  return `${count} in ${result.duration_ms} ms`;
}

function commandCaption(result: Extract<SqlResult, { type: 'command' }>): string {
  const verb = result.sql.trim().split(/\s+/)[0].toUpperCase();
  return result.affected_rows === null
    ? `${verb} ran successfully.`
    : `${verb} changed ${plural(result.affected_rows, 'row')}.`;
}

function preview(sql: string): string {
  const line = sql.replace(/\s+/g, ' ').trim();
  return line.length > 70 ? `${line.slice(0, 70)}…` : line;
}

export function RunResults({ run }: { run: SqlRun }) {
  const theme = useTheme();
  const multiple = run.results.length + (run.error ? 1 : 0) > 1;

  return (
    <View style={styles.list} accessibilityLiveRegion="polite">
      {run.results.map((result, index) => (
        <View key={index} style={styles.result}>
          {multiple ? (
            <Text style={[styles.statement, { color: theme.muted }]}>{`${index + 1}. ${preview(result.sql)}`}</Text>
          ) : null}
          {result.type === 'rows' ? (
            <>
              <ResultTable columns={result.columns} rows={result.rows} />
              <Text style={[styles.meta, { color: theme.muted }]}>
                {`${rowsCaption(result)}${result.truncated ? '. Add a LIMIT to see specific rows.' : '.'}`}
              </Text>
            </>
          ) : (
            <Text style={[styles.meta, { color: theme.ink }]}>{commandCaption(result)}</Text>
          )}
        </View>
      ))}
      {run.error ? (
        <Text accessibilityRole="alert" style={[styles.error, { color: theme.bad, backgroundColor: theme.badSoft }]}>
          {run.error.statement === null ? run.error.message : `Statement ${run.error.statement + 1}: ${run.error.message}`}
        </Text>
      ) : null}
      {run.results.length === 0 && !run.error ? (
        <Text style={[styles.meta, { color: theme.muted }]}>Your SQL ran but returned nothing.</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  list: { gap: 14 },
  result: { gap: 6 },
  statement: { fontFamily: monoFont, fontSize: 13 },
  meta: { fontSize: 14 },
  error: { padding: 10, borderRadius: 8, fontSize: 15, lineHeight: 22 },
});
```

Create `mobile/src/components/results/PhpOutput.tsx`:

```tsx
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { monoFont, useTheme } from '../../theme';

type Props = { output: string; fatal: boolean; durationMs?: number | null };

/** PHP's own error and warning lines are coloured, as in the web app's PhpOutput.vue. */
export function PhpOutput({ output, fatal, durationMs = null }: Props) {
  const theme = useTheme();
  const lines = output.split('\n');

  return (
    <View style={styles.wrap} accessibilityLiveRegion="polite">
      <ScrollView
        horizontal
        style={[styles.screen, { backgroundColor: theme.codeBackground, borderColor: fatal ? theme.bad : theme.line }]}
        contentContainerStyle={styles.content}
      >
        {output.trim() ? (
          <Text selectable style={[styles.text, { color: theme.ink }]}>
            {lines.map((line, i) => {
              const color = /^(PHP )?(Fatal error|Parse error)/.test(line)
                ? theme.bad
                : /^(PHP )?(Warning|Notice|Deprecated)/.test(line)
                  ? theme.syntax.number
                  : theme.ink;
              return (
                <Text key={i} style={{ color }}>
                  {line}
                  {i < lines.length - 1 ? '\n' : ''}
                </Text>
              );
            })}
          </Text>
        ) : (
          <Text style={[styles.silent, { color: theme.muted }]}>
            Your code ran but didn't print anything. Use echo to see values.
          </Text>
        )}
      </ScrollView>
      <Text style={[styles.meta, { color: fatal ? theme.bad : theme.muted }]}>
        {fatal ? 'PHP stopped with an error.' : durationMs !== null ? `Ran in ${durationMs} ms.` : ''}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  screen: { borderWidth: 1, borderRadius: 8, maxHeight: 320 },
  content: { padding: 12 },
  text: { fontFamily: monoFont, fontSize: 14, lineHeight: 20 },
  silent: { fontSize: 15 },
  meta: { fontSize: 14 },
});
```

Create `mobile/src/components/results/SchemaPanel.tsx`:

```tsx
import { useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import type { SchemaTable } from '../../api/types';
import { plural } from '../../lib/text';
import { monoFont, useTheme } from '../../theme';
import { Button } from '../Button';

/** The lesson dataset's tables, collapsed by default to leave room for the editor. */
export function SchemaPanel({ tables, onPreview }: { tables: SchemaTable[]; onPreview: (table: string) => void }) {
  const theme = useTheme();
  const [open, setOpen] = useState(false);

  if (tables.length === 0) return null;

  return (
    <View style={[styles.panel, { borderColor: theme.line, backgroundColor: theme.surface }]}>
      <Button variant="quiet" title={open ? 'Hide tables' : 'Show tables'} onPress={() => setOpen(!open)} />
      {open
        ? tables.map((table) => (
            <View key={table.name} style={styles.table}>
              <Text style={[styles.name, { color: theme.ink }]}>{table.name}</Text>
              <Text style={[styles.meta, { color: theme.muted }]}>{plural(table.row_count, 'row')}</Text>
              {table.columns.map((column) => (
                <Text key={column.name} style={[styles.column, { color: theme.ink }]}>
                  {`${column.name} ${column.type}${column.primary_key ? ', primary key' : ''}`}
                </Text>
              ))}
              <Button title={`Preview ${table.name}`} onPress={() => onPreview(table.name)} />
            </View>
          ))
        : null}
    </View>
  );
}

const styles = StyleSheet.create({
  panel: { borderWidth: 1, borderRadius: 8, padding: 8, gap: 8 },
  table: { gap: 4, paddingHorizontal: 8, paddingBottom: 8 },
  name: { fontFamily: monoFont, fontSize: 16, fontWeight: '700' },
  meta: { fontSize: 13 },
  column: { fontFamily: monoFont, fontSize: 14 },
});
```

Create `mobile/src/components/results/SqlCheckDetails.tsx`:

```tsx
import { StyleSheet, Text, View } from 'react-native';
import type { SqlCheck, SqlResult } from '../../api/types';
import { useTheme } from '../../theme';
import { ResultTable } from './ResultTable';
import { RunResults } from './RunResults';

export function SqlCheckDetails({ check }: { check: SqlCheck }) {
  const theme = useTheme();
  const rowSets = check.run.results.filter((r): r is Extract<SqlResult, { type: 'rows' }> => r.type === 'rows');
  const learnerRows = rowSets[rowSets.length - 1] ?? null;
  const heading = [styles.heading, { color: theme.ink }];

  if (check.passed) {
    return learnerRows ? <ResultTable columns={learnerRows.columns} rows={learnerRows.rows} /> : null;
  }

  return (
    <View style={styles.compare}>
      <Text style={heading}>Your result</Text>
      {learnerRows ? <ResultTable columns={learnerRows.columns} rows={learnerRows.rows} /> : <RunResults run={check.run} />}
      {check.expected ? (
        <>
          <Text style={heading}>Expected result</Text>
          <ResultTable columns={check.expected.columns} rows={check.expected.rows} />
        </>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  compare: { gap: 8 },
  heading: { fontSize: 15, fontWeight: '700', marginTop: 4 },
});
```

Create `mobile/src/components/results/PhpCheckDetails.tsx`:

```tsx
import { StyleSheet, Text, View } from 'react-native';
import type { PhpCheck } from '../../api/types';
import { useTheme } from '../../theme';
import { PhpOutput } from './PhpOutput';

export function PhpCheckDetails({ check }: { check: PhpCheck }) {
  const theme = useTheme();

  return (
    <View style={styles.details}>
      {check.error ? (
        <Text accessibilityRole="alert" style={[styles.error, { color: theme.bad, backgroundColor: theme.badSoft }]}>
          {check.error}
        </Text>
      ) : null}
      {check.checks.map((item, i) => {
        const state = !check.loaded ? 'not run' : item.passed ? 'passed' : 'failed';
        return (
          <View key={i} accessible accessibilityLabel={`${item.label} (${state})`} style={styles.check}>
            <Text style={[styles.mark, { color: state === 'passed' ? theme.ok : state === 'failed' ? theme.bad : theme.muted }]}>
              {state === 'passed' ? '✓' : state === 'failed' ? '✗' : '•'}
            </Text>
            <View style={styles.checkBody}>
              <Text style={[styles.label, { color: theme.ink }]}>{item.label}</Text>
              {check.loaded && !item.passed && item.message ? (
                <Text style={[styles.message, { color: theme.muted }]}>{item.message}</Text>
              ) : null}
            </View>
          </View>
        );
      })}
      {check.output.trim() ? (
        <View style={styles.output}>
          <Text style={[styles.heading, { color: theme.ink }]}>What your code printed</Text>
          <PhpOutput output={check.output} fatal={!check.loaded} />
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  details: { gap: 10 },
  error: { padding: 10, borderRadius: 8, fontSize: 15, lineHeight: 22 },
  check: { flexDirection: 'row', gap: 10 },
  mark: { fontSize: 18, fontWeight: '700', width: 18 },
  checkBody: { flex: 1, gap: 2 },
  label: { fontSize: 16 },
  message: { fontSize: 14, lineHeight: 20 },
  output: { gap: 6 },
  heading: { fontSize: 15, fontWeight: '700' },
});
```

The checks' `accessible` View merges its children for screen readers. RNTL's `getByText` can still find text inside it, and `getByLabelText` matches the label.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

If `screen.getByText('Herbert')` finds more than one match (the value may show in two tables), use `getAllByText(...).length > 0`. Don't change the component.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/results
git commit -m "Add SQL and PHP result views"
```

---

### Task 10: Sandbox step

**Files:**
- Create: `mobile/src/components/lesson/SandboxStep.tsx`, `mobile/src/components/lesson/__tests__/SandboxStep.test.tsx`

**Interfaces:**
- Consumes:
  - `api.runSql` and `api.runPhp` (Task 2);
  - `useLessonProgress`, `updateLesson` and `withOpeningTag` (Task 3);
  - `CodeEditor` (Task 8);
  - `RunResults`, `PhpOutput` and `SchemaPanel` (Task 9);
  - `Button` (Task 1).
- Produces: `SandboxStep({ lesson: Lesson; language: Language; schema: SchemaTable[]; request: { code: string; key: number } | null })`. A new `request.key` loads that code and runs it.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/components/lesson/__tests__/SandboxStep.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import { useProgressStore } from '../../../progress/store';
import { phpLesson, sqlLesson } from '../../../test/fixtures';
import { SandboxStep } from '../SandboxStep';

afterEach(() => jest.restoreAllMocks());

const sqlRun = {
  results: [{ type: 'rows' as const, sql: 'SELECT * FROM books;', columns: ['title'], rows: [['Dune']], truncated: false, duration_ms: 2 }],
  error: null,
};

test('runs SQL from the starter code and shows the rows', async () => {
  jest.spyOn(api, 'runSql').mockResolvedValue(sqlRun);
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(api.runSql).toHaveBeenCalledWith(1, 'SELECT * FROM books;');
  expect(await screen.findByText('Dune')).toBeTruthy();
});

test('edits are saved and restored', async () => {
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  await fireEvent.changeText(screen.getByLabelText('Sandbox SQL editor'), 'SELECT 42;');

  expect(useProgressStore.getState().lessons[1].sandboxCode).toBe('SELECT 42;');
});

test('a Try request loads the code and runs it; PHP snippets get an opening tag', async () => {
  jest.spyOn(api, 'runPhp').mockResolvedValue({ output: 'hi', fatal: false, durationMs: 5 });
  await render(<SandboxStep lesson={phpLesson.data} language="php" schema={[]} request={{ code: 'echo "hi";', key: 1 }} />);

  expect(await screen.findByText('hi')).toBeTruthy();
  expect(api.runPhp).toHaveBeenCalledWith(2, '<?php\n\necho "hi";');
  expect(screen.getByLabelText('Sandbox PHP editor').props.value).toBe('<?php\n\necho "hi";');
});

test('double-tapping Run sends one request', async () => {
  jest.spyOn(api, 'runSql').mockReturnValue(new Promise(() => {}));
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Running…' }));

  expect(api.runSql).toHaveBeenCalledTimes(1);
});

test('when the PHP sandbox is down the message shows and the code stays', async () => {
  jest.spyOn(api, 'runPhp').mockRejectedValue(new ApiError('The PHP sandbox is unavailable. Try again shortly.', 503));
  await render(<SandboxStep lesson={phpLesson.data} language="php" schema={[]} request={null} />);

  await fireEvent.changeText(screen.getByLabelText('Sandbox PHP editor'), '<?php echo 1;');
  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(await screen.findByText('The PHP sandbox is unavailable. Try again shortly.')).toBeTruthy();
  expect(screen.getByLabelText('Sandbox PHP editor').props.value).toBe('<?php echo 1;');
});

test('Restore example brings back the starter code', async () => {
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  await fireEvent.changeText(screen.getByLabelText('Sandbox SQL editor'), 'SELECT 42;');
  await fireEvent.press(screen.getByRole('button', { name: 'Restore example' }));

  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT * FROM books;');
});

test('previewing a table runs a SELECT on it', async () => {
  jest.spyOn(api, 'runSql').mockResolvedValue(sqlRun);
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Show tables' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Preview books' }));

  expect(api.runSql).toHaveBeenCalledWith(1, 'SELECT *\nFROM books\nLIMIT 10;');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/components/lesson/__tests__/SandboxStep.test.tsx`
Expected: FAIL, because the module doesn't exist.

- [ ] **Step 3: Write `SandboxStep`**

Create `mobile/src/components/lesson/SandboxStep.tsx`:

```tsx
import { useEffect, useRef, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import type { Language, Lesson, PhpRun, SchemaTable, SqlRun } from '../../api/types';
import { CodeEditor } from '../../editor/CodeEditor';
import { withOpeningTag } from '../../lesson/php';
import { updateLesson, useLessonProgress } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { PhpOutput } from '../results/PhpOutput';
import { RunResults } from '../results/RunResults';
import { SchemaPanel } from '../results/SchemaPanel';

type Props = {
  lesson: Lesson;
  language: Language;
  schema: SchemaTable[];
  /** Code sent over from a "Try it" button. A new key replaces the editor's code and runs it. */
  request: { code: string; key: number } | null;
};

type Result = { kind: 'sql'; run: SqlRun } | { kind: 'php'; run: PhpRun };

export function SandboxStep({ lesson, language, schema, request }: Props) {
  const theme = useTheme();
  const progress = useLessonProgress(lesson.id);
  const [editor, setEditor] = useState(() => ({ value: progress.sandboxCode ?? lesson.starter_code ?? '', key: 0 }));
  const code = useRef(editor.value);
  const running = useRef(false);
  const [busy, setBusy] = useState(false);
  const [result, setResult] = useState<Result | null>(null);
  const [failure, setFailure] = useState('');
  const scroll = useRef<ScrollView>(null);
  const editorY = useRef(0);

  function save(value: string) {
    code.current = value;
    updateLesson(lesson.id, () => ({ sandboxCode: value }));
  }

  function load(value: string) {
    save(value);
    setEditor((current) => ({ value, key: current.key + 1 }));
  }

  async function run() {
    if (running.current) return;
    running.current = true;
    setBusy(true);
    setFailure('');
    try {
      setResult(
        language === 'sql'
          ? { kind: 'sql', run: await api.runSql(lesson.id, code.current) }
          : { kind: 'php', run: await api.runPhp(lesson.id, code.current) },
      );
    } catch (e) {
      setResult(null);
      setFailure(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      running.current = false;
      setBusy(false);
    }
  }

  useEffect(() => {
    if (request) {
      load(language === 'php' ? withOpeningTag(request.code) : request.code);
      run();
    }
    // Only a new request should load code; language and lesson are fixed for this step.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [request?.key]);

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.intro, { color: theme.muted }]}>
        {language === 'sql'
          ? "Write any SQL you like: SELECT, INSERT, UPDATE, even DROP TABLE. Every run starts from a fresh copy of the data, so you can't break anything."
          : 'Write any PHP you like and press Run. It runs in a sandbox on the server. Use echo to print values.'}
      </Text>
      {language === 'sql' ? (
        <SchemaPanel
          tables={schema}
          onPreview={(table) => {
            load(`SELECT *\nFROM ${table}\nLIMIT 10;`);
            run();
          }}
        />
      ) : null}
      <View onLayout={(event) => (editorY.current = event.nativeEvent.layout.y)}>
        <CodeEditor
          value={editor.value}
          resetKey={editor.key}
          language={language}
          onChange={save}
          label={language === 'sql' ? 'Sandbox SQL editor' : 'Sandbox PHP editor'}
          onFocus={() => scroll.current?.scrollTo({ y: Math.max(editorY.current - 8, 0), animated: true })}
        />
      </View>
      <View style={styles.actions}>
        <Button variant="primary" title={busy ? 'Running…' : 'Run'} onPress={run} />
        <Button
          title="Restore example"
          onPress={() => {
            load(lesson.starter_code ?? '');
            setResult(null);
            setFailure('');
          }}
        />
      </View>
      {failure ? (
        <Text accessibilityRole="alert" style={[styles.failure, { color: theme.bad }]}>
          {failure}
        </Text>
      ) : null}
      {result?.kind === 'sql' ? <RunResults run={result.run} /> : null}
      {result?.kind === 'php' ? <PhpOutput {...result.run} /> : null}
      {!result && !failure ? (
        <Text style={[styles.intro, { color: theme.muted }]}>
          {language === 'sql' ? 'Press Run to see the result of your query here.' : 'Press Run to see what your code prints.'}
        </Text>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 14, paddingBottom: 120 },
  intro: { fontSize: 15, lineHeight: 22 },
  actions: { flexDirection: 'row', gap: 10 },
  failure: { fontSize: 15, lineHeight: 22 },
});
```

A few notes on this component:
- `running` is a ref, so a second tap during the same render cycle is refused before React re-renders the button. That is what the double-tap test proves.
- `bottom: 120` padding leaves room for the key bar.
- The preview's `load()` then `run()` works because `load` updates `code.current` synchronously before `run` reads it.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/lesson
git commit -m "Add the Sandbox step"
```

---

### Task 11: Challenges step

**Files:**
- Create: `mobile/src/components/lesson/ChallengesStep.tsx`, `mobile/src/components/lesson/__tests__/ChallengesStep.test.tsx`

**Interfaces:**
- Consumes:
  - `api.runSql`, `api.runPhp`, `api.checkSql` and `api.checkPhp` (Task 2);
  - `useLessonProgress`, `updateLesson`, `useProgressStore` and `phpCheckMessage` (Task 3);
  - `Markdown` (Task 4);
  - `CodeEditor` (Task 8);
  - `RunResults`, `PhpOutput`, `SqlCheckDetails` and `PhpCheckDetails` (Task 9);
  - `Button` (Task 1).
- Produces: `ChallengesStep({ lesson: Lesson; language: Language })`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/components/lesson/__tests__/ChallengesStep.test.tsx`:

```tsx
import { fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import { useProgressStore } from '../../../progress/store';
import { phpLesson, sqlLesson } from '../../../test/fixtures';
import { ChallengesStep } from '../ChallengesStep';

afterEach(() => jest.restoreAllMocks());

const passingSql = {
  passed: true,
  message: 'Correct! Your query returns exactly the expected result.',
  run: { results: [{ type: 'rows' as const, sql: 'x', columns: ['title', 'author'], rows: [['Dune', 'Herbert']], truncated: false, duration_ms: 1 }], error: null },
  expected: { columns: ['title', 'author'], rows: [['Dune', 'Herbert']] },
};

test('starts at the first unsolved challenge with its starter code', async () => {
  useProgressStore.setState({ lessons: { 1: { read: false, theoryPage: 1, sandboxCode: null, quiz: {}, challenges: { 21: { passed: true, code: 'done' } } } } });
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  expect(screen.getByText('Cheap books')).toBeTruthy();
  expect(screen.getByText('1 of 2 solved')).toBeTruthy();
  expect(screen.getByText('Row order is checked for this challenge.')).toBeTruthy();
});

test('a passing SQL answer is saved and offers the next challenge', async () => {
  jest.spyOn(api, 'checkSql').mockResolvedValue(passingSql);
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'SELECT title, author FROM books');
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText('Correct! Your query returns exactly the expected result.')).toBeTruthy();
  expect(api.checkSql).toHaveBeenCalledWith(21, 'SELECT title, author FROM books');
  expect(useProgressStore.getState().lessons[1].challenges[21]).toEqual({ passed: true, code: 'SELECT title, author FROM books' });

  await fireEvent.press(screen.getByRole('button', { name: 'Next challenge' }));
  expect(screen.getByText('Books under 10.')).toBeTruthy();
});

test('a failing PHP answer lists the checks', async () => {
  jest.spyOn(api, 'checkPhp').mockResolvedValue({
    passed: false,
    loaded: true,
    error: null,
    output: '',
    checks: [
      { label: 'Book exists', passed: true, message: null },
      { label: 'Book has a title', passed: false, message: 'Expected a title.' },
    ],
  });
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText("1 of 2 checks pass. See what's missing below.")).toBeTruthy();
  expect(screen.getByText('Expected a title.')).toBeTruthy();
  expect(useProgressStore.getState().lessons[2]?.challenges[31]?.passed ?? false).toBe(false);
});

test('when the PHP sandbox is down the message shows and the code stays', async () => {
  jest.spyOn(api, 'checkPhp').mockRejectedValue(new ApiError('The PHP sandbox is unavailable. Try again shortly.', 503));
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Your first class'), '<?php class Book {}');
  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));

  expect(await screen.findByText('The PHP sandbox is unavailable. Try again shortly.')).toBeTruthy();
  expect(screen.getByLabelText('Code for challenge: Your first class').props.value).toBe('<?php class Book {}');
});

test('the hint toggles and Start over restores the starter code', async () => {
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Show a hint' }));
  expect(screen.getByText('Put the two column names after SELECT.')).toBeTruthy();

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'nope');
  await fireEvent.press(screen.getByRole('button', { name: 'Start over' }));
  expect(screen.getByLabelText('Code for challenge: Titles and authors').props.value).toBe('SELECT\nFROM books;');
});

test('switching challenges keeps each one’s code', async () => {
  await render(<ChallengesStep lesson={sqlLesson.data} language="sql" />);

  await fireEvent.changeText(screen.getByLabelText('Code for challenge: Titles and authors'), 'SELECT 1');
  await fireEvent.press(screen.getByRole('button', { name: 'Challenge 2: Cheap books' }));
  await fireEvent.press(screen.getByRole('button', { name: 'Challenge 1: Titles and authors' }));

  expect(screen.getByLabelText('Code for challenge: Titles and authors').props.value).toBe('SELECT 1');
});

test('Run shows the result without checking', async () => {
  jest.spyOn(api, 'runPhp').mockResolvedValue({ output: 'hello', fatal: false, durationMs: 4 });
  await render(<ChallengesStep lesson={phpLesson.data} language="php" />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(await screen.findByText('hello')).toBeTruthy();
});

test('a lesson without challenges says so', async () => {
  await render(<ChallengesStep lesson={{ ...sqlLesson.data, challenges: [] }} language="sql" />);

  expect(screen.getByText('This lesson has no challenges yet.')).toBeTruthy();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/components/lesson/__tests__/ChallengesStep.test.tsx`
Expected: FAIL, because the module doesn't exist.

- [ ] **Step 3: Write `ChallengesStep`**

Create `mobile/src/components/lesson/ChallengesStep.tsx`:

```tsx
import { useRef, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { api } from '../../api/client';
import type { Challenge, Language, Lesson, PhpCheck, PhpRun, SqlCheck, SqlRun } from '../../api/types';
import { CodeEditor } from '../../editor/CodeEditor';
import { phpCheckMessage } from '../../lesson/php';
import { updateLesson, useLessonProgress, useProgressStore } from '../../progress/store';
import { useTheme } from '../../theme';
import { Button } from '../Button';
import { Markdown } from '../markdown/Markdown';
import { PhpCheckDetails } from '../results/PhpCheckDetails';
import { PhpOutput } from '../results/PhpOutput';
import { RunResults } from '../results/RunResults';
import { SqlCheckDetails } from '../results/SqlCheckDetails';

type Props = { lesson: Lesson; language: Language };

type RunView = { kind: 'sql'; run: SqlRun } | { kind: 'php'; run: PhpRun };
type CheckView = { kind: 'sql'; check: SqlCheck; message: string } | { kind: 'php'; check: PhpCheck; message: string };

function savedCode(lessonId: number, challenge: Challenge): string {
  return useProgressStore.getState().lessons[lessonId]?.challenges[challenge.id]?.code ?? challenge.starter_code ?? '';
}

/** Tasks checked on the server: SQL against a reference result, PHP against the challenge's checks. */
export function ChallengesStep({ lesson, language }: Props) {
  const theme = useTheme();
  const challenges = lesson.challenges;
  const progress = useLessonProgress(lesson.id);
  const [index, setIndex] = useState(() => {
    const first = challenges.findIndex((c) => !progress.challenges[c.id]?.passed);
    return first === -1 ? 0 : first;
  });
  const [editor, setEditor] = useState(() => ({
    value: challenges.length ? savedCode(lesson.id, challenges[index]) : '',
    key: 0,
  }));
  const code = useRef(editor.value);
  const working = useRef(false);
  const [busy, setBusy] = useState<'run' | 'check' | null>(null);
  const [run, setRun] = useState<RunView | null>(null);
  const [check, setCheck] = useState<CheckView | null>(null);
  const [failure, setFailure] = useState('');
  const [showHint, setShowHint] = useState(false);
  const scroll = useRef<ScrollView>(null);
  const editorY = useRef(0);

  if (challenges.length === 0) {
    return (
      <View style={styles.page}>
        <Text style={[styles.text, { color: theme.ink }]}>This lesson has no challenges yet.</Text>
      </View>
    );
  }

  const active = challenges[index];
  const solved = challenges.filter((c) => progress.challenges[c.id]?.passed).length;
  const order = [...challenges.slice(index + 1), ...challenges.slice(0, index)];
  const nextUnsolved = order.find((c) => !progress.challenges[c.id]?.passed) ?? null;

  function save(value: string, passed?: boolean) {
    code.current = value;
    updateLesson(lesson.id, (current) => ({
      challenges: {
        ...current.challenges,
        [active.id]: { passed: passed ?? current.challenges[active.id]?.passed ?? false, code: value },
      },
    }));
  }

  function load(value: string) {
    code.current = value;
    setEditor((current) => ({ value, key: current.key + 1 }));
  }

  function open(challenge: Challenge) {
    setIndex(challenges.indexOf(challenge));
    load(savedCode(lesson.id, challenge));
    setRun(null);
    setCheck(null);
    setFailure('');
    setShowHint(false);
  }

  async function work(kind: 'run' | 'check') {
    if (working.current) return;
    working.current = true;
    setBusy(kind);
    setFailure('');
    setRun(null);
    setCheck(null);
    try {
      if (kind === 'run') {
        setRun(
          language === 'sql'
            ? { kind: 'sql', run: await api.runSql(lesson.id, code.current) }
            : { kind: 'php', run: await api.runPhp(lesson.id, code.current) },
        );
      } else if (language === 'sql') {
        const result = await api.checkSql(active.id, code.current);
        setCheck({ kind: 'sql', check: result, message: result.message });
        if (result.passed) save(code.current, true);
      } else {
        const result = await api.checkPhp(active.id, code.current);
        setCheck({ kind: 'php', check: result, message: phpCheckMessage(result) });
        if (result.passed) save(code.current, true);
      }
    } catch (e) {
      setFailure(e instanceof Error ? e.message : 'Something went wrong. Try again.');
    } finally {
      working.current = false;
      setBusy(null);
    }
  }

  return (
    <ScrollView ref={scroll} contentContainerStyle={styles.page} keyboardShouldPersistTaps="handled">
      <Text style={[styles.solved, { color: theme.muted }]}>{`${solved} of ${challenges.length} solved`}</Text>
      <View style={styles.chips}>
        {challenges.map((challenge, i) => {
          const passed = progress.challenges[challenge.id]?.passed ?? false;
          return (
            <Pressable
              key={challenge.id}
              accessibilityRole="button"
              accessibilityLabel={`Challenge ${i + 1}: ${challenge.title}`}
              accessibilityState={{ selected: i === index }}
              onPress={() => open(challenge)}
              style={[
                styles.chip,
                {
                  borderColor: i === index ? theme.ink : passed ? theme.ok : theme.line,
                  backgroundColor: passed ? theme.okSoft : theme.surface,
                },
              ]}
            >
              <Text style={[styles.chipText, { color: passed ? theme.ok : theme.ink }]}>{passed ? '✓' : i + 1}</Text>
            </Pressable>
          );
        })}
      </View>

      <Text accessibilityRole="header" style={[styles.title, { color: theme.ink }]}>
        {active.title}
      </Text>
      <Markdown source={active.prompt} />
      {active.order_matters ? (
        <Text style={[styles.note, { color: theme.muted }]}>Row order is checked for this challenge.</Text>
      ) : null}
      {active.hint ? (
        <View style={styles.hint}>
          <Button variant="quiet" title={showHint ? 'Hide hint' : 'Show a hint'} onPress={() => setShowHint(!showHint)} />
          {showHint ? <Text style={[styles.text, { color: theme.ink }]}>{active.hint}</Text> : null}
        </View>
      ) : null}

      <View onLayout={(event) => (editorY.current = event.nativeEvent.layout.y)}>
        <CodeEditor
          value={editor.value}
          resetKey={editor.key}
          language={language}
          onChange={(value) => save(value)}
          label={`Code for challenge: ${active.title}`}
          onFocus={() => scroll.current?.scrollTo({ y: Math.max(editorY.current - 8, 0), animated: true })}
        />
      </View>
      <View style={styles.actions}>
        <Button title={busy === 'run' ? 'Running…' : 'Run'} onPress={() => work('run')} disabled={busy !== null} />
        <Button
          variant="primary"
          title={busy === 'check' ? 'Checking…' : 'Check answer'}
          onPress={() => work('check')}
          disabled={busy !== null}
        />
        <Button
          variant="quiet"
          title="Start over"
          onPress={() => {
            save(active.starter_code ?? '');
            load(active.starter_code ?? '');
            setRun(null);
            setCheck(null);
          }}
        />
      </View>

      {failure ? (
        <Text accessibilityRole="alert" style={[styles.text, { color: theme.bad }]}>
          {failure}
        </Text>
      ) : null}

      {check ? (
        <View style={styles.result}>
          <View
            style={[
              styles.verdict,
              {
                backgroundColor: check.check.passed ? theme.okSoft : theme.badSoft,
                borderColor: check.check.passed ? theme.ok : theme.bad,
              },
            ]}
          >
            <Text style={[styles.verdictText, { color: theme.ink }]}>{check.message}</Text>
            {check.check.passed && nextUnsolved ? (
              <Button variant="primary" title="Next challenge" onPress={() => open(nextUnsolved)} />
            ) : null}
            {check.check.passed && !nextUnsolved ? (
              <Text style={[styles.text, { color: theme.ink }]}>You've solved every challenge in this lesson.</Text>
            ) : null}
          </View>
          {check.kind === 'sql' ? <SqlCheckDetails check={check.check} /> : <PhpCheckDetails check={check.check} />}
        </View>
      ) : null}

      {run?.kind === 'sql' ? <RunResults run={run.run} /> : null}
      {run?.kind === 'php' ? <PhpOutput {...run.run} /> : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { padding: 20, gap: 14, paddingBottom: 120 },
  text: { fontSize: 16, lineHeight: 24 },
  solved: { fontSize: 14 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { width: 40, height: 40, borderRadius: 20, borderWidth: 2, alignItems: 'center', justifyContent: 'center' },
  chipText: { fontSize: 15, fontWeight: '700' },
  title: { fontSize: 22, fontWeight: '800' },
  note: { fontSize: 14 },
  hint: { gap: 6, alignItems: 'flex-start' },
  actions: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  result: { gap: 12 },
  verdict: { borderWidth: 1, borderRadius: 8, padding: 12, gap: 10 },
  verdictText: { fontSize: 16, fontWeight: '700', lineHeight: 23 },
});
```

Hooks rule: every hook (`useState`, `useRef`, `useLessonProgress`, `useTheme`) runs before the early `return` for "no challenges". Keep it that way.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/src/components/lesson
git commit -m "Add the Challenges step"
```

---

### Task 12: Lesson screen and step switcher

**Files:**
- Create: `mobile/src/components/lesson/StepSwitcher.tsx`, `mobile/src/screens/LessonScreen.tsx`, `mobile/app/courses/[course]/lessons/[lesson].tsx`, `mobile/src/screens/__tests__/LessonScreen.test.tsx`

**Interfaces:**
- Consumes:
  - `api.lesson` (Task 2);
  - `useLessonProgress`, `updateLesson` and `lessonSummary` (Task 3);
  - `LoadState` (Task 5);
  - `LearnStep` (Task 6), `QuizStep` (Task 7), `SandboxStep` (Task 10) and `ChallengesStep` (Task 11).
- Produces:
  - `LessonScreen({ courseSlug: string; lessonSlug: string; step?: string })`.
  - `StepSwitcher({ steps: { key: Step; label: string; status: string }[]; current: Step; onChange: (step: Step) => void })`.
  - `Step = 'learn' | 'sandbox' | 'quiz' | 'challenges'`.

- [ ] **Step 1: Write the failing tests**

Create `mobile/src/screens/__tests__/LessonScreen.test.tsx`:

```tsx
import { act, fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api, ApiError } from '../../api/client';
import { updateLesson } from '../../progress/store';
import { sqlLesson } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { LessonScreen } from '../LessonScreen';

afterEach(() => jest.restoreAllMocks());

test('opens on Learn and switches steps through the URL', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);

  expect(await screen.findByText('Section 1 of 2')).toBeTruthy();
  await fireEvent.press(screen.getByRole('tab', { name: /Quiz/ }));

  expect(router.setParams).toHaveBeenCalledWith({ step: 'quiz' });
});

test('the step param picks the step, and unknown values fall back to Learn', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  const { rerender } = await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="quiz" />);

  expect(await screen.findByText('Which clause picks the table?')).toBeTruthy();

  await rerender(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="bogus" />);
  expect(await screen.findByText('Section 1 of 2')).toBeTruthy();
});

test('tabs show progress', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);
  await screen.findByText('Section 1 of 2');

  await act(async () =>
    updateLesson(1, () => ({
      read: true,
      quiz: { 11: { option: 1, correct: true, correct_option: 1, explanation: '' } },
      challenges: { 21: { passed: true, code: null } },
    })),
  );

  expect(screen.getByRole('tab', { name: 'Learn, Read' })).toBeTruthy();
  expect(screen.getByRole('tab', { name: 'Quiz, 1/2' })).toBeTruthy();
  expect(screen.getByRole('tab', { name: 'Challenges, 1/2' })).toBeTruthy();
});

test('Try from the theory opens the sandbox with that code', async () => {
  jest.spyOn(api, 'lesson').mockResolvedValue(sqlLesson);
  jest.spyOn(api, 'runSql').mockResolvedValue({ results: [], error: null });
  const { rerender } = await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" />);

  await fireEvent.press(await screen.findByRole('button', { name: 'Try it in the sandbox' }));
  expect(router.setParams).toHaveBeenCalledWith({ step: 'sandbox' });

  await rerender(<LessonScreen courseSlug="sql" lessonSlug="your-first-queries" step="sandbox" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT title FROM books;');
  expect(api.runSql).toHaveBeenCalledWith(1, 'SELECT title FROM books;');
});

test('an unknown lesson says so', async () => {
  jest.spyOn(api, 'lesson').mockRejectedValue(new ApiError('Not Found', 404));
  await renderWithClient(<LessonScreen courseSlug="sql" lessonSlug="nope" />);

  expect(await screen.findByText("We couldn't find that lesson.")).toBeTruthy();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `cd mobile && npx jest src/screens/__tests__/LessonScreen.test.tsx`
Expected: FAIL, because the module doesn't exist.

- [ ] **Step 3: Write `StepSwitcher`, `LessonScreen` and the route**

Create `mobile/src/components/lesson/StepSwitcher.tsx`:

```tsx
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../../theme';

export type Step = 'learn' | 'sandbox' | 'quiz' | 'challenges';

type Props = {
  steps: { key: Step; label: string; status: string }[];
  current: Step;
  onChange: (step: Step) => void;
};

/** Tapped, never swiped, so it can't fight with scrolling inside the editor. */
export function StepSwitcher({ steps, current, onChange }: Props) {
  const theme = useTheme();

  return (
    <View accessibilityRole="tablist" style={[styles.bar, { backgroundColor: theme.surface, borderColor: theme.line }]}>
      {steps.map((step) => {
        const selected = step.key === current;
        return (
          <Pressable
            key={step.key}
            accessibilityRole="tab"
            accessibilityLabel={step.status ? `${step.label}, ${step.status}` : step.label}
            accessibilityState={{ selected }}
            onPress={() => onChange(step.key)}
            style={[styles.tab, selected && { borderBottomColor: theme.highlight }]}
          >
            <Text style={[styles.label, { color: selected ? theme.ink : theme.muted }]}>{step.label}</Text>
            {step.status ? <Text style={[styles.status, { color: theme.muted }]}>{step.status}</Text> : null}
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  bar: { flexDirection: 'row', borderBottomWidth: 1 },
  tab: { flex: 1, minHeight: 52, alignItems: 'center', justifyContent: 'center', borderBottomWidth: 3, borderBottomColor: 'transparent', paddingVertical: 6 },
  label: { fontSize: 15, fontWeight: '700' },
  status: { fontSize: 12 },
});
```

Create `mobile/src/screens/LessonScreen.tsx`:

```tsx
import { useQuery } from '@tanstack/react-query';
import { router, Stack } from 'expo-router';
import { useEffect, useState, type ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';
import { api } from '../api/client';
import { LoadState } from '../components/LoadState';
import { ChallengesStep } from '../components/lesson/ChallengesStep';
import { LearnStep } from '../components/lesson/LearnStep';
import { QuizStep } from '../components/lesson/QuizStep';
import { SandboxStep } from '../components/lesson/SandboxStep';
import { StepSwitcher, type Step } from '../components/lesson/StepSwitcher';
import { updateLesson, useLessonProgress } from '../progress/store';
import { lessonSummary } from '../progress/summary';

const STEPS: Step[] = ['learn', 'sandbox', 'quiz', 'challenges'];

type Props = { courseSlug: string; lessonSlug: string; step?: string };

/**
 * A lesson's four steps. The current step lives in the URL (?step=), and steps
 * stay mounted once opened, so editor contents survive switching tabs.
 */
export function LessonScreen({ courseSlug, lessonSlug, step: stepParam }: Props) {
  const query = useQuery({
    queryKey: ['lesson', courseSlug, lessonSlug],
    queryFn: () => api.lesson(courseSlug, lessonSlug),
  });
  const step: Step = STEPS.includes(stepParam as Step) ? (stepParam as Step) : 'learn';
  const [visited, setVisited] = useState<Set<Step>>(() => new Set([step]));
  const [sandboxRequest, setSandboxRequest] = useState<{ code: string; key: number } | null>(null);
  const lesson = query.data?.data;
  const progress = useLessonProgress(lesson?.id ?? -1);

  useEffect(() => {
    setVisited((current) => (current.has(step) ? current : new Set(current).add(step)));
  }, [step]);

  const goTo = (next: Step) => router.setParams({ step: next });
  const tryInSandbox = (code: string) => {
    setSandboxRequest({ code, key: Date.now() });
    goTo('sandbox');
  };

  let content: ReactNode = null;
  if (lesson && query.data) {
    const language = lesson.course.language;
    const schema = query.data.schema;
    const summary = lessonSummary(lesson, progress);
    const views: Record<Step, () => ReactNode> = {
      learn: () => (
        <LearnStep
          lesson={lesson}
          language={language}
          onTry={tryInSandbox}
          onFinish={() => {
            updateLesson(lesson.id, () => ({ read: true }));
            goTo('sandbox');
          }}
          onSkip={() => goTo('quiz')}
        />
      ),
      sandbox: () => (
        <SandboxStep lesson={lesson} language={language} schema={schema} request={sandboxRequest} />
      ),
      quiz: () => <QuizStep lesson={lesson} language={language} onNext={() => goTo('challenges')} onTry={tryInSandbox} />,
      challenges: () => <ChallengesStep lesson={lesson} language={language} />,
    };

    content = (
      <View style={styles.flex}>
        <StepSwitcher
          current={step}
          onChange={goTo}
          steps={[
            { key: 'learn', label: 'Learn', status: summary.read ? 'Read' : '' },
            { key: 'sandbox', label: 'Sandbox', status: '' },
            { key: 'quiz', label: 'Quiz', status: summary.quizAnswered ? `${summary.quizCorrect}/${summary.quizTotal}` : '' },
            {
              key: 'challenges',
              label: 'Challenges',
              status: summary.challengesPassed ? `${summary.challengesPassed}/${summary.challengeTotal}` : '',
            },
          ]}
        />
        {STEPS.filter((s) => visited.has(s)).map((s) => (
          <View key={s} style={[styles.flex, s !== step && styles.hidden]}>
            {views[s]()}
          </View>
        ))}
      </View>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: lesson?.title ?? '' }} />
      <LoadState
        loading={query.isPending}
        error={query.error}
        notFound="We couldn't find that lesson."
        onRetry={() => query.refetch()}
      >
        {content}
      </LoadState>
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  hidden: { display: 'none' },
});
```

Create `mobile/app/courses/[course]/lessons/[lesson].tsx`:

```tsx
import { useLocalSearchParams } from 'expo-router';
import { LessonScreen } from '../../../../src/screens/LessonScreen';

export default function LessonRoute() {
  const { course, lesson, step } = useLocalSearchParams<{ course: string; lesson: string; step?: string }>();
  // A new lesson starts with a fresh screen (visited steps, sandbox request).
  return <LessonScreen key={`${course}/${lesson}`} courseSlug={course} lessonSlug={lesson} step={step} />;
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `cd mobile && npm test && npm run typecheck`
Expected: all pass.

About `test('Try from the theory…')`: the sandbox mounts after the rerender with the request already set, so its effect loads and runs the code on mount. If the first `findByRole('button', { name: 'Try it in the sandbox' })` matches more than one button (hidden steps stay mounted), use `findAllByRole(...)[0]`. The Learn step is the only mounted step at that point.

Hidden steps stay mounted with `display: 'none'`. RNTL may still find text inside hidden views, so tests query only text that belongs to the current step or use `findBy` after switching. Don't remove the keep-mounted behaviour.

- [ ] **Step 5: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile
git commit -m "Add the lesson screen with step switching"
```

---

### Task 13: README, manual device checklist and final checks

**Files:**
- Create: `mobile/README.md`
- Modify: `app/README.md` (a short "Mobile app" pointer)

**Interfaces:**
- Consumes: everything above.
- Produces: documentation, and green `npm test`, `npm run typecheck`, `npx expo-doctor` and export results.

- [ ] **Step 1: Write `mobile/README.md`**

```markdown
# learnP mobile

The learnP courses on iOS and Android, built with Expo (SDK 57) and Expo Router. It uses the same
API as the web app (`../app`). SQL and PHP both run on the server; PHP runs on the separate runner
(`../runner`).

## Running it on your phone

1. Install **Expo Go** on your phone. Your phone and computer must be on the same Wi-Fi.
2. Start the API so the phone can reach it:
   ```sh
   cd ../app && php artisan serve --host=0.0.0.0 --port=8000
   ```
   For PHP lessons, also start the runner (see `../runner/README.md`) and set `RUNNER_URL` and
   `RUNNER_SECRET` in `../app/.env`.
3. Point the app at your computer's LAN address (find it with `ipconfig getifaddr en0` on macOS):
   ```sh
   cp .env.example .env   # then edit EXPO_PUBLIC_API_URL, e.g. http://192.168.1.20:8000
   ```
4. Start Expo and scan the QR code with the camera (iOS) or Expo Go (Android):
   ```sh
   export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"
   npm install
   npx expo start
   ```

Changing `.env` needs a restart of `npx expo start`.

## How it's built

- `app/`: routes (`/`, `/courses/[course]`, `/courses/[course]/lessons/[lesson]?step=…`). Deep links use the scheme `learnp://`.
- `src/api`: typed client. Its error messages match the web app's `api.js`.
- `src/progress`: progress stored on the device (AsyncStorage key `learnp:progress:v2`). There are no accounts.
- `src/components/markdown`: lesson Markdown tokenised with `marked` and drawn as native views, with code highlighted by Prism.
- `src/editor`: CodeMirror 6 in an Expo DOM component (`'use dom'`), with a native key bar above the keyboard.
  - The editor only reports text; the app replaces it by bumping `resetKey`, so typing is never overwritten.

## Tests

```sh
npm test
npm run typecheck
```

Jest replaces the WebView editor with a `TextInput` (`src/editor/__mocks__`).

## Manual checklist (Expo Go, on a real phone)

Run through this on one iPhone and one Android phone before a release.

- [ ] The course list loads. With the API stopped, you see "Could not reach the server…", and **Try again** works once the API is back.
- [ ] Opening a course shows its lessons; opening a lesson lands on **Learn**.
- [ ] Learn:
  - [ ] Next and Previous change sections.
  - [ ] Tables scroll sideways.
  - [ ] **Try it in the sandbox** opens the Sandbox with that code and runs it.
- [ ] Sandbox (SQL):
  - [ ] **Show tables** and **Preview** work.
  - [ ] Running shows rows.
  - [ ] An error shows the statement number.
  - [ ] **Restore example** works.
- [ ] Sandbox (PHP): echo output shows. With the runner stopped you see "The PHP sandbox is unavailable. Try again shortly." and your code stays.
- [ ] Editor:
  - [ ] Typing fast (hold a key, paste a long snippet) loses no characters.
  - [ ] The key bar appears above the keyboard, inserts `$`, `->` and `{}`, and paired keys put the cursor inside.
  - [ ] The keyboard stays open while you use the key bar.
  - [ ] The editor stays visible while typing.
- [ ] Switching Learn → Sandbox → Quiz → Sandbox keeps the Sandbox code.
- [ ] Quiz:
  - [ ] Answers show Correct/Not quite and the explanation.
  - [ ] The numbers turn green and red.
  - [ ] **Retake the quiz** clears answers.
- [ ] Challenges:
  - [ ] **Check answer** shows the verdict and details.
  - [ ] **Next challenge** works.
  - [ ] Solved challenges show ✓.
  - [ ] **Start over** works.
- [ ] Progress survives closing and reopening the app. The course screen shows "Completed." for a finished lesson.
- [ ] Dark mode (switch the phone's appearance) keeps everything readable, including the editor.
- [ ] `npx uri-scheme open "learnp://courses/sql/lessons/your-first-queries?step=quiz" --ios` (or tapping such a link) opens the quiz directly.
```

- [ ] **Step 2: Point to it from `app/README.md`**

After the courses table near the top of `app/README.md`, add:

```markdown
There is also a mobile app for iOS and Android in `../mobile` (Expo). See `../mobile/README.md`.
```

- [ ] **Step 3: Run the final checks**

```bash
export PATH="$HOME/.nvm/versions/node/v24.13.0/bin:$PATH"
cd /Users/macbook/Desktop/dev/learnP/mobile
npm test
npm run typecheck
npx expo-doctor
npx expo export --platform ios --output-dir /tmp/learnp-export-ios && rm -rf /tmp/learnp-export-ios
npx expo export --platform android --output-dir /tmp/learnp-export-android && rm -rf /tmp/learnp-export-android
```

Expected: tests and typecheck are green, and both exports succeed.

`expo-doctor` must report no dependency-version problems. If it flags a package installed with plain `npm install` that Expo manages (it shouldn't for the ones in this plan), reinstall it with `npx expo install <package>` and re-run the checks.

- [ ] **Step 4: Commit**

```bash
cd /Users/macbook/Desktop/dev/learnP
git add mobile/README.md app/README.md
git commit -m "Document the mobile app and its manual device checklist"
```
