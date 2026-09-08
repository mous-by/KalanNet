import { useState } from 'react';
import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, FAB, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import PaginatedList from '@/components/PaginatedList';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { removeQueueItem } from '@/lib/offlineQueue';
import { hasPermission } from '@/lib/permissions';
import { usePaginatedApi } from '@/lib/useApi';
import { Classe, Enseignant } from '@/types/api';

interface Presence {
  id_presence: number;
  nombre_heure: number;
  date_presence: string;
  valide: boolean | number;
  enseignant?: Enseignant;
  classe?: Classe;
}

export default function PresencesScreen() {
  const { user } = useAuth();
  const { queue } = useOffline();
  const list = usePaginatedApi<Presence>('/presences', {}, 'presences');
  const [actionError, setActionError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const queuedPresences = queue.filter((item) => item.kind === 'presence');

  const canValidate = hasPermission(user, 'presence_modification');
  const canDelete = hasPermission(user, 'presence_supprimer');

  async function handleValidate(id: number) {
    setActionError(null);
    try {
      await api.post(`/presences/${id}/validate`);
      setSuccessMessage('Présence validée avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de valider.'));
    }
  }

  async function handleDelete(id: number) {
    setActionError(null);
    try {
      await api.delete(`/presences/${id}`);
      setSuccessMessage('Présence supprimée avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de supprimer.'));
    }
  }

  return (
    <>
      <OfflineBanner />
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_presence)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error ?? actionError}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucune présence."
        header={
          queuedPresences.length > 0 ? (
            <View style={styles.queuedSection}>
              {queuedPresences.map((item) => (
                <View key={item.id} style={[styles.row, item.status === 'conflict' ? styles.conflictRow : styles.queuedRow]}>
                  <Text style={styles.title}>{item.label}</Text>
                  <Text style={item.status === 'conflict' ? styles.conflictText : styles.queuedText}>
                    {item.status === 'conflict' ? (item.message ?? 'Conflit à vérifier') : 'En attente de synchronisation'}
                  </Text>
                  {item.status === 'conflict' ? (
                    <Button compact textColor="#d33" onPress={() => removeQueueItem(item.id)}>
                      Abandonner
                    </Button>
                  ) : null}
                </View>
              ))}
            </View>
          ) : undefined
        }
        renderItem={(item) => (
          <View style={styles.row}>
            <Text style={styles.title}>{item.classe?.nom_classe ?? '—'}</Text>
            <Text style={styles.meta}>
              {item.date_presence} · {item.nombre_heure}h · {item.enseignant?.nom_prenom_enseignant ?? ''}
            </Text>
            <Text style={item.valide ? styles.validated : styles.pending}>{item.valide ? 'Validée' : 'En attente'}</Text>
            {!item.valide ? (
              <View style={styles.actions}>
                {canValidate ? (
                  <Button compact onPress={() => handleValidate(item.id_presence)}>
                    Valider
                  </Button>
                ) : null}
                {canDelete ? (
                  <Button compact textColor="#d33" onPress={() => handleDelete(item.id_presence)}>
                    Supprimer
                  </Button>
                ) : null}
              </View>
            ) : null}
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/presences/new')} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </>
  );
}

const styles = StyleSheet.create({
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  title: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  validated: { color: '#1f8a4c', marginTop: 6, fontSize: 12 },
  pending: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  actions: { flexDirection: 'row', marginTop: 4 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  queuedSection: { marginBottom: 4 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
