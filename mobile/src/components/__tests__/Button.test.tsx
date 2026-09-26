import { fireEvent, render, screen } from '@testing-library/react-native';
import { Button } from '../Button';

test('calls onPress when tapped', async () => {
  const onPress = jest.fn();
  await render(<Button title="Run" onPress={onPress} />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(onPress).toHaveBeenCalledTimes(1);
});

test('does not call onPress when disabled', async () => {
  const onPress = jest.fn();
  await render(<Button title="Run" onPress={onPress} disabled />);

  await fireEvent.press(screen.getByRole('button', { name: 'Run' }));

  expect(onPress).not.toHaveBeenCalled();
});
