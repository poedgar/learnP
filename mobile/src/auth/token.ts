import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

/**
 * The API token for the signed-in account, kept in the device's keychain. SecureStore
 * has no web version, so the web build (development only) falls back to AsyncStorage.
 * A copy stays in memory so every request doesn't have to read the keychain.
 */
const TOKEN_KEY = 'learnp-api-token';

const storage =
  Platform.OS === 'web'
    ? {
        get: () => AsyncStorage.getItem(TOKEN_KEY),
        set: (token: string) => AsyncStorage.setItem(TOKEN_KEY, token),
        remove: () => AsyncStorage.removeItem(TOKEN_KEY),
      }
    : {
        get: () => SecureStore.getItemAsync(TOKEN_KEY),
        set: (token: string) => SecureStore.setItemAsync(TOKEN_KEY, token),
        remove: () => SecureStore.deleteItemAsync(TOKEN_KEY),
      };

let current: string | null = null;

export function currentToken(): string | null {
  return current;
}

export async function loadToken(): Promise<string | null> {
  current = await storage.get();
  return current;
}

export async function saveToken(token: string): Promise<void> {
  current = token;
  await storage.set(token);
}

export async function clearToken(): Promise<void> {
  current = null;
  await storage.remove();
}
