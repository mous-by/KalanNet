import { ReactNode, useState } from 'react';
import { Image, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, IconButton, Text } from 'react-native-paper';

import { useLocale } from '@/context/LocaleContext';
import { useOffline } from '@/context/OfflineContext';
import { apiErrorMessage } from '@/lib/api';
import { choisirPhoto, envoyerPhotoEleve, PhotoSource } from '@/lib/photoEleve';

type Props = {
  idEleve: number;
  imageUrl: string | null;
  onChange?: (imageUrl: string | null) => void;
  compact?: boolean;
  /** Mode compact (liste « Photos de la classe ») : contenu entre la photo et les boutons (nom de l'élève…). */
  children?: ReactNode;
};

/** Photo d'un élève avec « Prendre » (appareil photo) et « Galerie » : envoyée aussitôt au serveur. */
export default function PhotoEleve({ idEleve, imageUrl, onChange, compact = false, children }: Props) {
  const { t } = useLocale();
  const { isOnline } = useOffline();
  const [url, setUrl] = useState<string | null>(imageUrl);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function changer(source: PhotoSource) {
    setError(null);
    if (!isOnline) {
      setError(t('eleves.photo_hors_ligne'));
      return;
    }
    const asset = await choisirPhoto(source);
    if (asset === 'permission') {
      setError(t('eleves.photo_permission'));
      return;
    }
    if (!asset) return;
    setBusy(true);
    try {
      const nouvelle = await envoyerPhotoEleve(idEleve, asset);
      setUrl(nouvelle);
      onChange?.(nouvelle);
    } catch (err) {
      setError(apiErrorMessage(err, t('eleves.photo_erreur')));
    } finally {
      setBusy(false);
    }
  }

  const size = compact ? 52 : 96;

  return (
    <View style={compact ? styles.rowCompact : styles.row}>
      <View style={[styles.avatar, { width: size, height: size, borderRadius: size / 2 }]}>
        {url ? <Image source={{ uri: url }} style={styles.image} /> : <Text style={styles.placeholder}>{t('eleves.photo_manquante')}</Text>}
        {busy ? <ActivityIndicator style={StyleSheet.absoluteFill} /> : null}
      </View>
      {compact ? (
        <>
          <View style={styles.middle}>
            {children}
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </View>
          <IconButton icon="camera" mode="contained" disabled={busy} onPress={() => changer('camera')} accessibilityLabel={t('eleves.photo_prendre')} />
          <IconButton icon="image" mode="outlined" disabled={busy} onPress={() => changer('library')} accessibilityLabel={t('eleves.photo_galerie')} />
        </>
      ) : (
        <View style={styles.actions}>
          <View style={styles.buttons}>
            <Button mode="contained" icon="camera" compact disabled={busy} onPress={() => changer('camera')}>
              {t('eleves.photo_prendre')}
            </Button>
            <Button mode="outlined" icon="image" compact disabled={busy} onPress={() => changer('library')}>
              {t('eleves.photo_galerie')}
            </Button>
          </View>
          {error ? <Text style={styles.error}>{error}</Text> : null}
        </View>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: 16, marginBottom: 16 },
  rowCompact: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  avatar: { backgroundColor: '#eceff1', alignItems: 'center', justifyContent: 'center', overflow: 'hidden' },
  image: { width: '100%', height: '100%' },
  placeholder: { fontSize: 11, color: '#78909c', textAlign: 'center' },
  actions: { flexShrink: 1 },
  middle: { flex: 1, minWidth: 0 },
  buttons: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  error: { color: '#d33', marginTop: 4, fontSize: 12 },
});
