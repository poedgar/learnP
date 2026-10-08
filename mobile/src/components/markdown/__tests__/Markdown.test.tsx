import { fireEvent, render, screen } from '@testing-library/react-native';
import { StyleSheet } from 'react-native';
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

test('each heading level is smaller than the one above it', async () => {
  await render(<Markdown source={'# One\n\n## Two\n\n### Three\n\n#### Four\n\n##### Five\n\n###### Six'} />);

  const sizes = ['One', 'Two', 'Three', 'Four', 'Five', 'Six'].map(
    (text) => StyleSheet.flatten(screen.getByText(text).props.style).fontSize as number,
  );
  for (let i = 1; i < sizes.length; i++) {
    expect(sizes[i]).toBeLessThan(sizes[i - 1]);
  }
});
