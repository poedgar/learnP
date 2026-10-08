import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useState } from 'react';
import { View } from 'react-native';
import { KeyBarHost } from '../src/editor/KeyBar';
import { useTheme } from '../src/theme';

export const unstable_settings = { initialRouteName: 'index' };

export default function RootLayout() {
  const theme = useTheme();
  const [client] = useState(
    () => new QueryClient({ defaultOptions: { queries: { retry: 1, staleTime: 5 * 60_000 } } }),
  );

  return (
    <QueryClientProvider client={client}>
      <View style={{ flex: 1, backgroundColor: theme.background }}>
        <Stack
          screenOptions={{
            headerStyle: { backgroundColor: theme.surface },
            headerTintColor: theme.ink,
            contentStyle: { backgroundColor: theme.background },
          }}
        >
          <Stack.Screen name="index" options={{ title: 'learnP' }} />
        </Stack>
        <KeyBarHost />
        <StatusBar style="auto" />
      </View>
    </QueryClientProvider>
  );
}
