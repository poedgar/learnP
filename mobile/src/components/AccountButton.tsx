import { router } from 'expo-router';
import { Pressable, StyleSheet, Text } from 'react-native';
import { useAuthStore } from '../auth/store';
import { useTheme } from '../theme';

/** The header's way to the account screen: "Sign in", or "Account" once signed in. */
export function AccountButton() {
  const theme = useTheme();
  const { user, ready } = useAuthStore();

  if (!ready) return null;

  return (
    <Pressable
      accessibilityRole="button"
      onPress={() => router.push('/account')}
      hitSlop={8}
      style={({ pressed }) => [styles.button, { opacity: pressed ? 0.6 : 1 }]}
    >
      <Text style={[styles.label, { color: theme.ink }]}>{user ? 'Account' : 'Sign in'}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: { paddingHorizontal: 4, minHeight: 44, justifyContent: 'center' },
  label: { fontSize: 16, fontWeight: '600' },
});
