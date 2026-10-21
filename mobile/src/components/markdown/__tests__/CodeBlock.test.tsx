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

test('laravel blocks are highlighted as PHP', () => {
  expect(highlightTokens('$app->bind(Mailer::class);', 'laravel')).toEqual(highlightTokens('$app->bind(Mailer::class);', 'php'));
  expect(highlightTokens('$app->bind(Mailer::class);', 'laravel').length).toBeGreaterThan(1);
});

test.each(['bash', 'dockerfile', 'yaml', 'blade'])('%s blocks are highlighted', (language) => {
  const samples: Record<string, string> = {
    bash: 'docker run -d --name web nginx:1.27 # comment',
    dockerfile: 'FROM php:8.4-cli\nRUN apt-get update',
    yaml: 'services:\n  app:\n    build: .',
    blade: '<p>{{ $title }}</p>',
  };
  const tokens = highlightTokens(samples[language], language);
  expect(tokens.map((t) => t.text).join('')).toBe(samples[language]);
  expect(tokens.some((t) => t.color !== 'plain')).toBe(true);
});
