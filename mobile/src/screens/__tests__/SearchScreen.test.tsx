import { fireEvent, screen } from '@testing-library/react-native';
import { router } from 'expo-router';
import { api } from '../../api/client';
import { renderWithClient } from '../../test/render';
import { SearchScreen } from '../SearchScreen';

afterEach(() => jest.restoreAllMocks());

test('searches when submitted and opens a result', async () => {
  const search = jest.spyOn(api, 'search').mockResolvedValue([
    { course: { slug: 'sql', title: 'SQL', position: 1 }, lesson: { slug: 'window-functions', title: 'Window functions', position: 10 }, snippet: 'Rank rows…' },
  ]);
  await renderWithClient(<SearchScreen />);

  await fireEvent.changeText(screen.getByLabelText('Search for'), ' window ');
  await fireEvent(screen.getByLabelText('Search for'), 'submitEditing');

  expect(await screen.findByText('1 lesson found')).toBeTruthy();
  expect(search).toHaveBeenCalledWith('window');
  await fireEvent.press(screen.getByRole('button', { name: 'Window functions, SQL' }));
  expect(router.push).toHaveBeenCalledWith('/courses/sql/lessons/window-functions');
});

test('nothing found says so', async () => {
  jest.spyOn(api, 'search').mockResolvedValue([]);
  await renderWithClient(<SearchScreen />);

  await fireEvent.changeText(screen.getByLabelText('Search for'), 'zzz');
  await fireEvent(screen.getByLabelText('Search for'), 'submitEditing');

  expect(await screen.findByText('Nothing found. Try another word.')).toBeTruthy();
});
