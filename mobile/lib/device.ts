import { Platform } from 'react-native';
import * as SecureStore from 'expo-secure-store';

const DEVICE_ID_KEY = 'kalannet_device_id';

// Nom lisible du téléphone, montré dans l'alerte « Nouvelle connexion détectée » (ex. « Tecno Spark 20 »).
export function deviceName(): string {
  if (Platform.OS === 'android') {
    const { Brand, Model } = Platform.constants as { Brand?: string; Model?: string };
    const marque = (Brand ?? '').trim();
    const modele = (Model ?? '').trim();
    const nom = modele.toLowerCase().startsWith(marque.toLowerCase()) ? modele : `${marque.charAt(0).toUpperCase()}${marque.slice(1)} ${modele}`;
    return nom.trim() || 'Android';
  }
  if (Platform.OS === 'ios') {
    return `${Platform.isPad ? 'iPad' : 'iPhone'} (iOS ${Platform.Version})`;
  }
  return 'Navigateur';
}

// Identifiant tiré une fois par installation : le serveur reconnaît ainsi un téléphone déjà utilisé.
// Ce n'est pas un secret, seulement un repère ; il disparaît si l'application est désinstallée.
export async function deviceId(): Promise<string> {
  if (Platform.OS === 'web') return 'web';
  const existant = await SecureStore.getItemAsync(DEVICE_ID_KEY);
  if (existant) return existant;
  const nouveau = `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}-${Math.random().toString(36).slice(2, 12)}`;
  await SecureStore.setItemAsync(DEVICE_ID_KEY, nouveau);
  return nouveau;
}
