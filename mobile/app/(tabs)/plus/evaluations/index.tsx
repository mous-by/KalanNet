import { router } from 'expo-router';
import { Pressable, StyleSheet } from 'react-native';
import { FAB, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
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
  const list = usePaginatedApi<EvaluationRow>('/evaluations', {}, 'evaluations');

  return (
    <>
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
});
