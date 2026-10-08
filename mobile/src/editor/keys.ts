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
