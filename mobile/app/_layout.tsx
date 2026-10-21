import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useState } from 'react';
import { View } from 'react-native';
import { loadUser } from '../src/auth/store';
import { AccountButton } from '../src/components/AccountButton';
import { KeyBarHost } from '../src/editor/KeyBar';
import { useTheme } from '../src/theme';

export const unstable_settings = { initialRouteName: 'index' };

export default function RootLayout() {
  const theme = useTheme();
  const [client] = useState(
    () => new QueryClient({ defaultOptions: { queries: { retry: 1, staleTime: 5 * 60_000 } } }),
  );

  useEffect(() => {
    loadUser();
  }, []);

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
          <Stack.Screen name="index" options={{ title: 'learnP', headerRight: () => <AccountButton /> }} />
          <Stack.Screen name="review" options={{ title: 'Review' }} />
          <Stack.Screen name="account" options={{ title: 'Account' }} />
          <Stack.Screen name="search" options={{ title: 'Search' }} />
        </Stack>
        <KeyBarHost />
        <StatusBar style="auto" />
      </View>
    </QueryClientProvider>
  );
}
