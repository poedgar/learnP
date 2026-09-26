import type { ReactNode } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { ApiError } from '../api/client';
import { useTheme } from '../theme';
import { Button } from './Button';

type Props = {
  loading: boolean;
  error: unknown;
  notFound?: string;
  onRetry: () => void;
  children: ReactNode;
};

/** Loading spinner, error with Try again, or "not found" (which retrying can't fix). */
export function LoadState({ loading, error, notFound = "We couldn't find that page.", onRetry, children }: Props) {
  const theme = useTheme();

  if (error) {
    const status = error instanceof ApiError ? error.status : null;
    const message =
      status === 404 ? notFound : error instanceof Error ? error.message : 'Something went wrong. Try again.';

    return (
      <View style={styles.center}>
        <Text accessibilityRole="alert" style={[styles.message, { color: theme.ink }]}>
          {message}
        </Text>
        {status === 404 ? null : <Button title="Try again" onPress={onRetry} />}
      </View>
    );
  }

  if (loading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator accessibilityLabel="Loading" color={theme.muted} />
      </View>
    );
  }

  return <>{children}</>;
}

const styles = StyleSheet.create({
  center: { flex: 1, padding: 24, gap: 16, alignItems: 'center', justifyContent: 'center' },
  message: { fontSize: 16, textAlign: 'center', lineHeight: 24 },
});
