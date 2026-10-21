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

const javascript = [
  tab,
  key('{ }', '{}', 'Insert braces', 1),
  parens,
  key('[ ]', '[]', 'Insert brackets', 1),
  quotes,
  key('` `', '``', 'Insert backticks', 1),
  key('=>', '=>', 'Insert arrow'),
  semicolon,
  key('${ }', '${}', 'Insert template placeholder', 2),
];

export const KEYS: Record<Language, EditorKey[]> = {
  html: [
    tab,
    key('< >', '<>', 'Insert angle brackets', 1),
    key('</', '</', 'Insert closing tag start'),
    key('=" "', '=""', 'Insert attribute value', 2),
    key('{ }', '{}', 'Insert braces', 1),
    key(':', ':', 'Insert colon'),
    semicolon,
    key('#', '#', 'Insert hash'),
    key('.', '.', 'Insert full stop'),
  ],
  javascript,
  typescript: [...javascript.slice(0, 7), key(':', ':', 'Insert colon'), key('< >', '<>', 'Insert angle brackets', 1), ...javascript.slice(7)],
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
  // Git lessons are shell scripts of git commands.
  git: [
    key('git', 'git ', 'Insert git'),
    key('" "', '""', 'Insert double quotes', 1),
    key('-', '-', 'Insert hyphen'),
    key('--', '--', 'Insert double hyphen'),
    key('>', '>', 'Insert redirect'),
    key('&&', ' && ', 'Insert and'),
    key('/', '/', 'Insert slash'),
    key('.', '.', 'Insert full stop'),
    key('~', '~', 'Insert tilde'),
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
