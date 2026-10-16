import { Directory, File, Paths } from 'expo-file-system';
import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import { Platform } from 'react-native';
import { API_URL } from './api';
import { getToken } from './storage';

/**
 * Downloads a binary endpoint (PDF/Excel export) with the Bearer token
 * attached, then hands it to the system share sheet so the user can save
 * or open it. `path` is relative to the API base URL, e.g.
 * "/bulletins/42/telecharger?id_annee=2".
 */
/** Downloads a binary endpoint with the Bearer token into the cache; returns the local file uri. */
async function downloadToCache(path: string, filename: string): Promise<string> {
  const token = await getToken();
  const destination = new File(new Directory(Paths.cache), filename);
  const file = await File.downloadFileAsync(`${API_URL}${path}`, destination, {
    headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    idempotent: true,
  });

  return file.uri;
}

export async function downloadAndShare(path: string, filename: string): Promise<void> {
  if (Platform.OS === 'web') {
    // A plain window.open can't attach an Authorization header, so this is
    // a best-effort fallback for the web preview only — not the real flow.
    window.open(`${API_URL}${path}`, '_blank');
    return;
  }

  const uri = await downloadToCache(path, filename);
  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(uri);
  }
}

/** Downloads a PDF endpoint and opens the system print dialog (printer or « Save as PDF »). */
export async function downloadAndPrint(path: string, filename: string): Promise<void> {
  if (Platform.OS === 'web') {
    window.open(`${API_URL}${path}`, '_blank');
    return;
  }

  const uri = await downloadToCache(path, filename);
  await Print.printAsync({ uri });
}
