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
import { Classe, Enseignant, Matiere } from '@/types/api';

interface Emargement {
  id_emargement: number;
  chapitre: string | null;
  nombre_heure: number;
  date_emargement: string;
  valide: boolean | number;
  enseignant?: Enseignant;
  classe?: Classe;
  matiere?: Matiere;
}

export default function EmargementsScreen() {
  const { user } = useAuth();
  const { queue } = useOffline();
  const list = usePaginatedApi<Emargement>('/emargements', {}, 'emargements');
  const [actionError, setActionError] = useState<string | null>(null);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const queuedEmargements = queue.filter((item) => item.kind === 'emargement');

  const canValidate = hasPermission(user, 'emargement_validation_admin');
  const canDelete = hasPermission(user, 'emargement_supprimer');

  async function handleValidate(id: number) {
    setActionError(null);
    try {
      await api.post(`/emargements/${id}/validate`);
      setSuccessMessage('Émargement validé avec succès.');
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de valider.'));
    }
  }

  async function handleDelete(id: number) {
    setActionError(null);
    try {
      await api.delete(`/emargements/${id}`);
      setSuccessMessage('Émargement supprimé avec succès.');
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
        keyExtractor={(item) => String(item.id_emargement)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error ?? actionError}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucun émargement."
        header={
          queuedEmargements.length > 0 ? (
            <View style={styles.queuedSection}>
              {queuedEmargements.map((item) => (
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
            <Text style={styles.title}>
              {item.classe?.nom_classe ?? '—'} · {item.matiere?.nom_matiere ?? '—'}
            </Text>
            <Text style={styles.meta}>
              {item.date_emargement} · {item.nombre_heure}h · {item.enseignant?.nom_prenom_enseignant ?? ''}
            </Text>
            {item.chapitre ? <Text style={styles.meta}>{item.chapitre}</Text> : null}
            <Text style={item.valide ? styles.validated : styles.pending}>{item.valide ? 'Validé' : 'En attente'}</Text>
            {!item.valide ? (
              <View style={styles.actions}>
                {canValidate ? (
                  <Button compact onPress={() => handleValidate(item.id_emargement)}>
                    Valider
                  </Button>
                ) : null}
                {canDelete ? (
                  <Button compact textColor="#d33" onPress={() => handleDelete(item.id_emargement)}>
                    Supprimer
                  </Button>
                ) : null}
              </View>
            ) : null}
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/emargements/new')} />

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
