import * as ImagePicker from 'expo-image-picker';

import { api } from '@/lib/api';

export type PhotoSource = 'camera' | 'library';

/**
 * Prend une photo (appareil photo) ou en choisit une (galerie), recadrée au carré.
 * Renvoie null si l'utilisateur annule, ou 'permission' si l'accès à l'appareil photo est refusé.
 */
export async function choisirPhoto(source: PhotoSource): Promise<ImagePicker.ImagePickerAsset | null | 'permission'> {
  if (source === 'camera') {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) return 'permission';
  }
  const options: ImagePicker.ImagePickerOptions = { mediaTypes: ['images'], allowsEditing: true, aspect: [1, 1], quality: 0.7 };
  const result = source === 'camera' ? await ImagePicker.launchCameraAsync(options) : await ImagePicker.launchImageLibraryAsync(options);
  if (result.canceled || !result.assets?.length) return null;
  return result.assets[0];
}

/** Envoie la photo de l'élève ; le serveur la redresse, la réduit et remplace l'ancienne. */
export async function envoyerPhotoEleve(idEleve: number, asset: ImagePicker.ImagePickerAsset): Promise<string | null> {
  const form = new FormData();
  form.append('photo', {
    uri: asset.uri,
    name: asset.fileName ?? `eleve-${idEleve}.jpg`,
    type: asset.mimeType ?? 'image/jpeg',
  } as unknown as Blob);
  const { data } = await api.post<{ image_url: string | null }>(`/eleves/${idEleve}/photo`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return data.image_url;
}
