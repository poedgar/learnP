import { fireEvent, render, screen } from '@testing-library/react-native';
import { CodeEditor } from '../CodeEditor';
import { useKeyBarStore } from '../keyBarStore';

test('reports edits and only takes a new value when resetKey changes', async () => {
  const onChange = jest.fn();
  const { rerender } = await render(
    <CodeEditor value="SELECT 1" resetKey={0} language="sql" onChange={onChange} label="Sandbox SQL editor" />,
  );
  const input = screen.getByLabelText('Sandbox SQL editor');

  await fireEvent.changeText(input, 'SELECT 2');
  expect(onChange).toHaveBeenCalledWith('SELECT 2');

  await rerender(<CodeEditor value="SELECT 1" resetKey={0} language="sql" onChange={onChange} label="Sandbox SQL editor" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT 2');

  await rerender(<CodeEditor value="SELECT 3" resetKey={1} language="sql" onChange={onChange} label="Sandbox SQL editor" />);
  expect(screen.getByLabelText('Sandbox SQL editor').props.value).toBe('SELECT 3');
});

test('an editor that goes away while focused releases the key bar', async () => {
  const { unmount } = await render(
    <CodeEditor value="" resetKey={0} language="php" onChange={jest.fn()} label="Sandbox PHP editor" />,
  );
  await fireEvent(screen.getByLabelText('Sandbox PHP editor'), 'focus');
  expect(useKeyBarStore.getState().active?.language).toBe('php');

  await unmount();

  expect(useKeyBarStore.getState().active).toBeNull();
});
