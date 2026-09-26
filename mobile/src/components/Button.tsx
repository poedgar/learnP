import { Pressable, StyleSheet, Text } from 'react-native';
import { useTheme } from '../theme';

type Props = {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'default' | 'quiet';
  disabled?: boolean;
  accessibilityLabel?: string;
};

export function Button({ title, onPress, variant = 'default', disabled = false, accessibilityLabel }: Props) {
  const theme = useTheme();
  const primary = variant === 'primary';
  const quiet = variant === 'quiet';

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={accessibilityLabel ?? title}
      accessibilityState={{ disabled }}
      disabled={disabled}
      onPress={onPress}
      style={({ pressed }) => [
        styles.base,
        {
          backgroundColor: primary ? theme.accent : quiet ? 'transparent' : theme.surface,
          borderColor: primary ? theme.accent : quiet ? 'transparent' : theme.line,
          opacity: disabled ? 0.5 : pressed ? 0.75 : 1,
        },
      ]}
    >
      <Text style={[styles.label, { color: primary ? theme.accentInk : theme.ink }]}>{title}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 44,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: { fontSize: 16, fontWeight: '600' },
});
