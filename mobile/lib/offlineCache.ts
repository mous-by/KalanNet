import AsyncStorage from '@react-native-async-storage/async-storage';

const PREFIX = 'kalannet_cache_';

// Last-known-good snapshot of a GET response, keyed by caller-chosen name.
// Used so a "new entry" screen (présences, émargements, paiement par
// classe) can still render its dropdowns when opened offline.
export async function getCached<T>(key: string): Promise<T | null> {
  try {
    const raw = await AsyncStorage.getItem(PREFIX + key);
    return raw ? (JSON.parse(raw) as T) : null;
  } catch {
    return null;
  }
}

export async function setCached<T>(key: string, value: T): Promise<void> {
  try {
    await AsyncStorage.setItem(PREFIX + key, JSON.stringify(value));
  } catch {
    // best-effort cache; ignore storage failures (e.g. quota)
  }
}
