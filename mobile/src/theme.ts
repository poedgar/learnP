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
