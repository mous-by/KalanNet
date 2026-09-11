import { useCallback } from 'react';
import { router, useFocusEffect } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { Button, FAB, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import PaginatedList from '@/components/PaginatedList';
import { useOffline } from '@/context/OfflineContext';
import { removeQueueItem } from '@/lib/offlineQueue';
import { usePaginatedApi } from '@/lib/useApi';
import { Classe, Matiere, Trimestre } from '@/types/api';

interface EvaluationRow {
  id_evaluation: number;
  id_classe: number;
  id_matiere: number;
  mois: number | null;
  validation_status: string;
  evaluation?: { libeller: string; date_evaluation: string };
  classe?: Classe;
  matiere?: Matiere;
  trimestre?: Trimestre;
}

export default function EvaluationsScreen() {
  const { queue } = useOffline();
  const list = usePaginatedApi<EvaluationRow>('/evaluations', {}, 'evaluations');
  const queuedEvaluations = queue.filter((item) => item.kind === 'evaluation');

  // Refetch whenever this screen regains focus — e.g. returning here after
  // deleting or creating an evaluation on another screen, which otherwise
  // left the stale list showing until the user pulled to refresh manually.
  useFocusEffect(
    useCallback(() => {
      list.refresh();
    }, [list.refresh])
  );

  return (
    <>
      <OfflineBanner />
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => `${item.id_evaluation}-${item.id_classe}-${item.id_matiere}`}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        emptyLabel="Aucune évaluation."
        header={
          queuedEvaluations.length > 0 ? (
            <View style={styles.queuedSection}>
              {queuedEvaluations.map((item) => (
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
          <Pressable style={styles.row} onPress={() => router.push(`/plus/evaluations/${item.id_evaluation}`)}>
            <Text style={styles.title}>{item.evaluation?.libeller ?? 'Évaluation'}</Text>
            <Text style={styles.meta}>
              {item.classe?.nom_classe ?? '—'} · {item.matiere?.nom_matiere ?? '—'} · {item.evaluation?.date_evaluation ?? ''}
            </Text>
            {item.validation_status === 'en_attente' ? <Text style={styles.pending}>En attente de validation</Text> : null}
          </Pressable>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/evaluations/new')} />
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
  pending: { color: '#b8860b', marginTop: 4, fontSize: 12 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  queuedSection: { marginBottom: 4 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
