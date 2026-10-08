import { act, fireEvent, render, screen } from '@testing-library/react-native';
import { Keyboard } from 'react-native';
import { KeyBar, KeyBarHost } from '../KeyBar';
import { useKeyBarStore } from '../keyBarStore';

afterEach(() => jest.restoreAllMocks());

test('pressing a key sends its text and cursor offset', async () => {
  const onKey = jest.fn();
  await render(<KeyBar language="php" onKey={onKey} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Insert braces' }));

  expect(onKey).toHaveBeenCalledWith(expect.objectContaining({ text: '{}', cursorOffset: 1 }));
});

test('the host shows the bar only while an editor is focused and the keyboard is up', async () => {
  const listeners: Record<string, (e: { endCoordinates: { height: number } }) => void> = {};
  jest.spyOn(Keyboard, 'addListener').mockImplementation(((event: string, handler: never) => {
    listeners[event] = handler;
    return { remove: jest.fn() };
  }) as never);
  const insert = jest.fn();
  await render(<KeyBarHost />);

  expect(screen.queryByRole('button', { name: 'Insert tab' })).toBeNull();

  await act(async () => useKeyBarStore.getState().focus({ id: 'a', language: 'sql', insert }));
  await act(async () => Object.entries(listeners).find(([name]) => /Show/.test(name))?.[1]({ endCoordinates: { height: 300 } }));
  await fireEvent.press(screen.getByRole('button', { name: 'Insert tab' }));

  expect(insert).toHaveBeenCalledWith('    ', 4);

  await act(async () => useKeyBarStore.getState().blur('a'));
  expect(screen.queryByRole('button', { name: 'Insert tab' })).toBeNull();
});

test('blurring another editor does not clear the focused one', async () => {
  const insert = jest.fn();
  useKeyBarStore.getState().focus({ id: 'b', language: 'php', insert });
  useKeyBarStore.getState().blur('a');

  expect(useKeyBarStore.getState().active?.id).toBe('b');
});
