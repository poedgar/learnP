import { act, fireEvent, screen } from '@testing-library/react-native';
import { api } from '../../api/client';
import type { ReviewItemsResponse } from '../../api/types';
import { updateLesson, useProgressStore } from '../../progress/store';
import { useReviewStore } from '../../review/store';
import { sqlLesson } from '../../test/fixtures';
import { renderWithClient } from '../../test/render';
import { ReviewScreen } from '../ReviewScreen';

afterEach(() => jest.restoreAllMocks());

const { quiz_questions, challenges } = sqlLesson.data;

const items: ReviewItemsResponse = {
  quiz_questions: [{ ...quiz_questions[0], lesson_id: 1 }],
  challenges: [{ ...challenges[0], lesson_id: 1 }],
  lessons: [
    {
      id: 1,
      slug: 'your-first-queries',
      title: 'Your first queries',
      position: 1,
      course: { slug: 'sql', title: 'SQL', language: 'sql', position: 1 },
    },
  ],
};

async function passBoth() {
  await act(async () =>
    updateLesson(1, () => ({
      quiz: {
        11: { option: 1, correct: true, correct_option: 1, explanation: '' },
        12: { option: 0, correct: false, correct_option: 1, explanation: '' },
      },
      challenges: { 21: { passed: true, code: 'SELECT title, author FROM books;' } },
    })),
  );
}

test('with nothing passed, it explains how items get here', async () => {
  await renderWithClient(<ReviewScreen />);

  expect(screen.getByText('Nothing to review yet')).toBeTruthy();
});

test('lists passed items, leaving out wrong answers, and asks the server only for those', async () => {
  const request = jest.spyOn(api, 'reviewItems').mockResolvedValue(items);
  await passBoth();
  await renderWithClient(<ReviewScreen />);

  expect(await screen.findByText('2 cards to review')).toBeTruthy();
  expect(screen.getByText('Which clause picks the table?')).toBeTruthy();
  expect(screen.getByText('Titles and authors')).toBeTruthy();
  expect(screen.getAllByText('New')).toHaveLength(2);
  expect(request).toHaveBeenCalledWith([11], [21]);
});

test('a review session grades cards and brings missed ones back', async () => {
  jest.spyOn(api, 'reviewItems').mockResolvedValue(items);
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: true, correct_option: 1, explanation: 'FROM names the table.' });
  jest.spyOn(api, 'checkSql').mockResolvedValue({
    passed: true,
    message: 'Correct! Your query returns exactly the expected result.',
    run: { results: [], error: null },
    expected: null,
  });
  await passBoth();
  await renderWithClient(<ReviewScreen />);
  await fireEvent.press(await screen.findByRole('button', { name: 'Start review' }));

  // The quiz card: options are shuffled, so pick by text.
  expect(screen.getByText(/Card 1 of 2/)).toBeTruthy();
  await fireEvent.press(screen.getByText('FROM'));
  expect(await screen.findByText('FROM names the table.')).toBeTruthy();
  expect(api.answer).toHaveBeenCalledWith(11, 1);
  await fireEvent.press(screen.getByRole('button', { name: 'Good, next review in 1 day' }));
  expect(useReviewStore.getState().cards['quiz:11']).toMatchObject({ interval: 1, reps: 1 });

  // The challenge card: forgetting it puts it at the end of the session.
  expect(screen.getByText(/Card 2 of 2/)).toBeTruthy();
  expect(screen.getByLabelText('Code for challenge: Titles and authors').props.value).toBe('SELECT\nFROM books;');
  await fireEvent.press(screen.getByRole('button', { name: "I don't remember" }));
  expect(screen.getByText(/Card 3 of 3/)).toBeTruthy();
  expect(useReviewStore.getState().cards['challenge:21']).toMatchObject({ interval: 0, reps: 0 });

  await fireEvent.press(screen.getByRole('button', { name: 'Check answer' }));
  await fireEvent.press(await screen.findByRole('button', { name: 'Easy, next review in 4 days' }));

  expect(screen.getByText('Review done')).toBeTruthy();
  expect(screen.getByText('You reviewed 3 cards. Nicely done.')).toBeTruthy();
  expect(useReviewStore.getState().cards['challenge:21']).toMatchObject({ interval: 4, reps: 1 });
});

test('reviewing never changes lesson progress', async () => {
  jest.spyOn(api, 'reviewItems').mockResolvedValue(items);
  jest.spyOn(api, 'answer').mockResolvedValue({ correct: false, correct_option: 1, explanation: 'FROM names the table.' });
  await passBoth();
  const before = useProgressStore.getState().lessons;
  await renderWithClient(<ReviewScreen />);
  await fireEvent.press(await screen.findByRole('button', { name: 'Start review' }));

  await fireEvent.press(screen.getByText('SELECT'));
  await fireEvent.press(await screen.findByRole('button', { name: 'Continue (see it again soon)' }));

  expect(useProgressStore.getState().lessons).toBe(before);
  expect(useReviewStore.getState().cards['quiz:11']).toMatchObject({ reps: 0, lapses: 0 });
});
