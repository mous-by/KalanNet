import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';

import { useOffline } from '@/context/OfflineContext';
import { removeQueueItem } from '@/lib/offlineQueue';

const KIND_LABELS: Record<string, string> = {
  presence: 'Présence',
  emargement: 'Émargement',
  paiement_classe: 'Paiement de classe',
};

export default function SynchronisationScreen() {
  const { isOnline, queue, isSyncing, syncNow } = useOffline();

  const pending = queue.filter((item) => item.status === 'pending');
  const conflicts = queue.filter((item) => item.status === 'conflict');

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <View style={styles.statusRow}>
        <MaterialCommunityIcons name={isOnline ? 'wifi' : 'wifi-off'} size={20} color={isOnline ? '#1f8a4c' : '#b8860b'} />
        <Text style={styles.statusText}>{isOnline ? 'Connecté' : 'Hors ligne'}</Text>
      </View>

      <Button mode="contained" onPress={syncNow} loading={isSyncing} disabled={!isOnline || isSyncing} style={styles.syncButton}>
        Synchroniser maintenant
      </Button>

      {queue.length === 0 ? (
        <Text style={styles.empty}>Rien en attente. Tout est synchronisé.</Text>
      ) : (
        <>
          {pending.length > 0 ? (
            <>
              <Text style={styles.sectionTitle}>En attente ({pending.length})</Text>
              {pending.map((item) => (
                <View key={item.id} style={styles.card}>
                  <Text style={styles.cardKind}>{KIND_LABELS[item.kind] ?? item.kind}</Text>
                  <Text style={styles.cardLabel}>{item.label}</Text>
                  <Text style={styles.cardMeta}>Créé le {new Date(item.createdAt).toLocaleString('fr-FR')}</Text>
                </View>
              ))}
            </>
          ) : null}

          {conflicts.length > 0 ? (
            <>
              <Text style={styles.sectionTitle}>Conflits à vérifier ({conflicts.length})</Text>
              {conflicts.map((item) => (
                <View key={item.id} style={[styles.card, styles.conflictCard]}>
                  <Text style={styles.cardKind}>{KIND_LABELS[item.kind] ?? item.kind}</Text>
                  <Text style={styles.cardLabel}>{item.label}</Text>
                  <Text style={styles.conflictMessage}>{item.message}</Text>
                  <Button compact textColor="#d33" onPress={() => removeQueueItem(item.id)} style={styles.discardButton}>
                    Abandonner cet enregistrement
                  </Button>
                </View>
              ))}
            </>
          ) : null}
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  statusRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 16 },
  statusText: { fontWeight: '600' },
  syncButton: { marginBottom: 20 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  sectionTitle: { fontWeight: '700', marginTop: 12, marginBottom: 8 },
  card: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  conflictCard: {
    borderColor: '#d33',
    backgroundColor: 'rgba(211,51,51,0.06)',
  },
  cardKind: { fontSize: 12, opacity: 0.6, textTransform: 'uppercase' },
  cardLabel: { fontWeight: '600', marginTop: 2 },
  cardMeta: { fontSize: 12, opacity: 0.6, marginTop: 4 },
  conflictMessage: { color: '#d33', marginTop: 6, fontSize: 13 },
  discardButton: { alignSelf: 'flex-start', marginTop: 6 },
});
