import { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Dialog, Divider, Portal, Text } from 'react-native-paper';

import { api } from '@/lib/api';

interface Annonce {
  id_annonce: number;
  titre: string;
  contenu: string;
  public_cible: string;
  date_publication: string | null;
  auteur: string | null;
}

function formatDate(value: string | null): string {
  if (!value) return '';
  const date = new Date(value.replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Mirrors resources/views/annonces/_unread-modal.blade.php — every
// authenticated user (not just those with the annonces_apercu management
// permission) sees announcements targeted at their role as soon as they open
// the app, exactly like the auto-popup on web login.
export default function UnreadAnnouncementsModal() {
  const [annonces, setAnnonces] = useState<Annonce[]>([]);
  const [visible, setVisible] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    let cancelled = false;
    api
      .get<{ annonces: Annonce[] }>('/annonces/visibles')
      .then(({ data }) => {
        if (!cancelled && data.annonces.length > 0) {
          setAnnonces(data.annonces);
          setVisible(true);
        }
      })
      .catch(() => {});
    return () => {
      cancelled = true;
    };
  }, []);

  async function handleDismiss() {
    setIsSaving(true);
    try {
      await api.post('/annonces/marquer-lues');
    } catch {
      // if this fails the announcements simply reappear next launch
    } finally {
      setIsSaving(false);
      setVisible(false);
    }
  }

  if (annonces.length === 0) return null;

  return (
    <Portal>
      <Dialog visible={visible} onDismiss={handleDismiss} style={styles.dialog} dismissable={false}>
        <Dialog.Title>Nouvelles annonces</Dialog.Title>
        <Dialog.ScrollArea style={styles.scrollArea}>
          <ScrollView contentContainerStyle={styles.scrollContent}>
            {annonces.map((annonce, index) => (
              <View key={annonce.id_annonce}>
                {index > 0 ? <Divider style={styles.divider} /> : null}
                <View style={styles.item}>
                  <View style={styles.itemHeader}>
                    <Text style={styles.title}>{annonce.titre}</Text>
                    <View style={styles.badge}>
                      <Text style={styles.badgeText}>{annonce.public_cible}</Text>
                    </View>
                  </View>
                  <Text style={styles.meta}>
                    {formatDate(annonce.date_publication)}
                    {annonce.auteur ? ` · ${annonce.auteur}` : ''}
                  </Text>
                  <Text style={styles.content}>{annonce.contenu}</Text>
                </View>
              </View>
            ))}
          </ScrollView>
        </Dialog.ScrollArea>
        <Dialog.Actions>
          <Button loading={isSaving} onPress={handleDismiss}>
            J&apos;ai lu
          </Button>
        </Dialog.Actions>
      </Dialog>
    </Portal>
  );
}

const styles = StyleSheet.create({
  dialog: { maxHeight: '80%' },
  scrollArea: { paddingHorizontal: 0, maxHeight: 400 },
  scrollContent: { paddingHorizontal: 24 },
  divider: { marginVertical: 12 },
  item: { paddingVertical: 4 },
  itemHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: 8 },
  title: { fontWeight: '700', fontSize: 15, flex: 1 },
  badge: { backgroundColor: '#dbeafe', borderRadius: 20, paddingHorizontal: 10, paddingVertical: 3 },
  badgeText: { fontSize: 11, fontWeight: '700', color: '#1e40af', textTransform: 'capitalize' },
  meta: { fontSize: 11, opacity: 0.6, marginTop: 4, marginBottom: 8 },
  content: { fontSize: 13, lineHeight: 18 },
});
