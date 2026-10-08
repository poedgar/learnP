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
