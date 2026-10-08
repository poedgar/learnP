import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { api, ApiError } from '../../../api/client';
import type { SqlRun } from '../../../api/types';
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
  let resolve!: (run: SqlRun) => void;
  jest.spyOn(api, 'runSql').mockReturnValue(new Promise((r) => (resolve = r)));
  await render(<SandboxStep lesson={sqlLesson.data} language="sql" schema={sqlLesson.schema} request={null} />);

  // fireEvent resolves with the handler's promise, so the first press settles only once the run finishes.
  const first = fireEvent.press(screen.getByRole('button', { name: 'Run' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Running…' }));

  expect(api.runSql).toHaveBeenCalledTimes(1);
  await act(async () => {
    resolve(sqlRun);
    await first;
  });
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
