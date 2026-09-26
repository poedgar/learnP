import { plural } from '../text';

test('plural adds an s except for one', () => {
  expect(plural(0, 'lesson')).toBe('0 lessons');
  expect(plural(1, 'lesson')).toBe('1 lesson');
  expect(plural(8, 'quiz question')).toBe('8 quiz questions');
});
