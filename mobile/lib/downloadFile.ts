import { Directory, File, Paths } from 'expo-file-system';
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
export async function downloadAndShare(path: string, filename: string): Promise<void> {
  const url = `${API_URL}${path}`;

  if (Platform.OS === 'web') {
    // A plain window.open can't attach an Authorization header, so this is
    // a best-effort fallback for the web preview only — not the real flow.
    window.open(url, '_blank');
    return;
  }

  const token = await getToken();
  const destination = new File(new Directory(Paths.cache), filename);

  const file = await File.downloadFileAsync(url, destination, {
    headers: token ? { Authorization: `Bearer ${token}` } : undefined,
    idempotent: true,
  });

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(file.uri);
  }
}
