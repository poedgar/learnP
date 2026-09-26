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
