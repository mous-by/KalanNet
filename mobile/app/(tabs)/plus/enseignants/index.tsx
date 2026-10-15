import { router } from 'expo-router';
import { useState } from 'react';
import { Alert, Pressable, StyleSheet, View } from 'react-native';
import { Button, FAB, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { apiErrorMessage } from '@/lib/api';
import { downloadAndShare } from '@/lib/downloadFile';
import { removeQueueItem } from '@/lib/offlineQueue';
import { hasPermission } from '@/lib/permissions';
import { usePaginatedApi } from '@/lib/useApi';
import { Enseignant } from '@/types/api';

export default function EnseignantsScreen() {
  const { user } = useAuth();
  const { queue } = useOffline();
  const list = usePaginatedApi<Enseignant>('/enseignants');
  const canManage = hasPermission(user, 'enseignants_creation');
  const queuedEnseignants = queue.filter((item) => item.kind === 'enseignant');
  // Export Excel : administrateur / gestionnaire avec la permission (comme sur le web).
  const canExport = user?.droit === 'SupAdmin' || (['Admin', 'Gestionnaire'].includes(user?.droit ?? '') && hasPermission(user, 'enseignants_exporter'));
  const [isExporting, setIsExporting] = useState(false);

  async function handleExport() {
    setIsExporting(true);
    try {
      await downloadAndShare('/enseignants/exporter', 'Enseignants.xlsx');
    } catch (err) {
      Alert.alert('Export', apiErrorMessage(err, "Impossible d'exporter la liste des enseignants."));
    } finally {
      setIsExporting(false);
    }
  }

  return (
    <>
      <OfflineBanner />
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_enseignant)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        search={list.search}
        onSearchChange={list.setSearch}
        searchPlaceholder="Nom, email, téléphone…"
        emptyLabel="Aucun enseignant."
        header={
          canExport || queuedEnseignants.length > 0 ? (
            <View style={styles.queuedSection}>
              {canExport ? (
                <Button mode="outlined" icon="microsoft-excel" loading={isExporting} onPress={handleExport} style={styles.exportButton}>
                  Exporter en Excel
                </Button>
              ) : null}
              {queuedEnseignants.map((item) => (
                <View key={item.id} style={[styles.row, item.status === 'conflict' ? styles.conflictRow : styles.queuedRow]}>
                  <Text style={styles.name}>{item.label}</Text>
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
          <Pressable style={styles.row} onPress={() => router.push(`/plus/enseignants/${item.id_enseignant}`)}>
            <Text style={styles.name}>{item.nom_prenom_enseignant}</Text>
            <Text style={styles.meta}>{item.email_enseignant ?? item.telephone_enseignant ?? '—'}</Text>
          </Pressable>
        )}
      />
      {canManage ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/enseignants/new')} /> : null}
    </>
  );
}

const styles = StyleSheet.create({
  exportButton: { marginBottom: 8 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  name: {
    fontSize: 16,
    fontWeight: '600',
  },
  meta: {
    opacity: 0.6,
    marginTop: 4,
  },
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
  },
  queuedSection: { marginBottom: 4 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
