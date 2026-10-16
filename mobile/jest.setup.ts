import { cleanup } from '@testing-library/react-native';
import { useAuthStore } from './src/auth/store';
import { useKeyBarStore } from './src/editor/keyBarStore';
import { useProgressStore } from './src/progress/store';
import { useReviewStore } from './src/review/store';

// The WebView editor can't run under Jest; src/editor/__mocks__ has a TextInput stand-in.
jest.mock('./src/editor/CodeMirrorView');
// Nor can the WebView JavaScript runner; src/js-runtime/__mocks__ answers with canned results.
jest.mock('./src/js-runtime/JsRunnerView');

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

// The keychain isn't there under Jest; keep tokens in memory.
jest.mock('expo-secure-store', () => {
  const store = new Map<string, string>();
  return {
    getItemAsync: jest.fn(async (key: string) => store.get(key) ?? null),
    setItemAsync: jest.fn(async (key: string, value: string) => {
      store.set(key, value);
    }),
    deleteItemAsync: jest.fn(async (key: string) => {
      store.delete(key);
    }),
  };
});

// Screens use the imperative router and <Stack.Screen options>; tests assert on the calls.
jest.mock('expo-router', () => ({
  router: { push: jest.fn(), setParams: jest.fn(), back: jest.fn(), navigate: jest.fn() },
  Stack: { Screen: () => null },
}));

process.env.EXPO_PUBLIC_API_URL = 'http://api.test';

afterEach(() => {
  cleanup();
  useProgressStore.setState({ lessons: {} });
  useReviewStore.setState({ cards: {} });
  useKeyBarStore.setState({ active: null });
  useAuthStore.setState({ user: null, ready: false });
});
