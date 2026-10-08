import { cleanup } from '@testing-library/react-native';
import { useKeyBarStore } from './src/editor/keyBarStore';
import { useProgressStore } from './src/progress/store';

// The WebView editor can't run under Jest; src/editor/__mocks__ has a TextInput stand-in.
jest.mock('./src/editor/CodeMirrorView');

// An in-memory AsyncStorage, so tests don't depend on the package's own mock layout.
jest.mock('@react-native-async-storage/async-storage', () => {
  const store = new Map<string, string>();
  return {
    __esModule: true,
    default: {
      getItem: jest.fn(async (key: string) => store.get(key) ?? null),
      setItem: jest.fn(async (key: string, value: string) => {
        store.set(key, value);
      }),
      removeItem: jest.fn(async (key: string) => {
        store.delete(key);
      }),
      clear: jest.fn(async () => store.clear()),
    },
  };
});

// Screens use the imperative router and <Stack.Screen options>; tests assert on the calls.
jest.mock('expo-router', () => ({
  router: { push: jest.fn(), setParams: jest.fn(), back: jest.fn() },
  Stack: { Screen: () => null },
}));

process.env.EXPO_PUBLIC_API_URL = 'http://api.test';

afterEach(() => {
  cleanup();
  useProgressStore.setState({ lessons: {} });
  useKeyBarStore.setState({ active: null });
});
