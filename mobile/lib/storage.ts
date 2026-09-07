import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'kalannet_token';
const ONBOARDING_KEY = 'kalannet_onboarding_seen';

// expo-secure-store has no web implementation; fall back to localStorage
// there (fine for local dev in a browser, not used in the packaged app).
const isWeb = Platform.OS === 'web';

export async function getToken(): Promise<string | null> {
  if (isWeb) return window.localStorage.getItem(TOKEN_KEY);
  return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function setToken(token: string): Promise<void> {
  if (isWeb) {
    window.localStorage.setItem(TOKEN_KEY, token);
    return;
  }
  await SecureStore.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  if (isWeb) {
    window.localStorage.removeItem(TOKEN_KEY);
    return;
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

export async function hasSeenOnboarding(): Promise<boolean> {
  const value = isWeb ? window.localStorage.getItem(ONBOARDING_KEY) : await SecureStore.getItemAsync(ONBOARDING_KEY);
  return value === '1';
}

export async function setOnboardingSeen(): Promise<void> {
  if (isWeb) {
    window.localStorage.setItem(ONBOARDING_KEY, '1');
    return;
  }
  await SecureStore.setItemAsync(ONBOARDING_KEY, '1');
}
