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
