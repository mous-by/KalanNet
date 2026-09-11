import { useMemo, useState } from 'react';
import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Chip, FAB, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { removeQueueItem } from '@/lib/offlineQueue';
import { hasPermission } from '@/lib/permissions';
import { useApiGet, usePaginatedApi } from '@/lib/useApi';
import { Classe, Eleve } from '@/types/api';

function EleveRow({ eleve }: { eleve: Eleve }) {
  return (
    <Pressable style={styles.row} onPress={() => router.push(`/eleves/${eleve.id_eleve}`)}>
      <Text style={styles.name}>
        {eleve.prenom_eleve} {eleve.nom_eleve}
      </Text>
      <Text style={styles.meta}>
        {eleve.classe?.nom_classe ?? '—'} · {eleve.matricule ?? 'sans matricule'}
      </Text>
    </Pressable>
  );
}

function ParentChildrenList() {
  const { data, isLoading, error } = useApiGet<{ children?: Eleve[] }>('/dashboard');
  const children = data?.children ?? [];

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error) return <Text style={styles.error}>{error}</Text>;
  if (children.length === 0) return <Text style={styles.empty}>Aucun enfant rattaché à ce compte.</Text>;

  return (
    <View style={styles.content}>
      {children.map((child) => (
        <EleveRow key={child.id_eleve} eleve={child} />
      ))}
    </View>
  );
}

function StaffEleveList() {
  const { user } = useAuth();
  const { queue } = useOffline();
  const { data: filterOptions } = useApiGet<{ classes: Classe[] }>('/eleves/cartes-scolaires', [], {
    cacheKey: 'eleves-cartes-scolaires',
  });
  const [selectedClasse, setSelectedClasse] = useState<number | null>(null);
  const canManage = hasPermission(user, 'inscriptions_inscrire');
  const queuedEleves = queue.filter((item) => item.kind === 'eleve');

  const params = useMemo(
    () => (selectedClasse ? { id_classe: selectedClasse } : {}),
    [selectedClasse]
  );

  const list = usePaginatedApi<Eleve>('/eleves', params);

  return (
    <>
      <OfflineBanner />
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_eleve)}
        renderItem={(item) => <EleveRow eleve={item} />}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        search={list.search}
        onSearchChange={list.setSearch}
        searchPlaceholder="Nom, prénom ou matricule…"
        emptyLabel="Aucun élève trouvé."
        header={
          queuedEleves.length > 0 || (filterOptions?.classes?.length ?? 0) > 0 ? (
            <View>
              {queuedEleves.length > 0 ? (
                <View style={styles.queuedSection}>
                  {queuedEleves.map((item) => (
                    <View key={item.id} style={[styles.queuedRowBase, item.status === 'conflict' ? styles.conflictRow : styles.queuedRow]}>
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
              ) : null}
              {(filterOptions?.classes?.length ?? 0) > 0 ? (
                <View style={styles.chipsRow}>
                  <Chip selected={selectedClasse === null} onPress={() => setSelectedClasse(null)} style={styles.chip}>
                    Toutes les classes
                  </Chip>
                  {filterOptions!.classes.map((classe) => (
                    <Chip
                      key={classe.id_classe}
                      selected={selectedClasse === classe.id_classe}
                      onPress={() => setSelectedClasse(classe.id_classe)}
                      style={styles.chip}>
                      {classe.nom_classe}
                    </Chip>
                  ))}
                </View>
              ) : null}
            </View>
          ) : undefined
        }
      />
      {canManage ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/eleves/new')} /> : null}
    </>
  );
}

export default function ElevesScreen() {
  const { user } = useAuth();

  if (user?.droit === 'parent') {
    return <ParentChildrenList />;
  }

  return <StaffEleveList />;
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
  },
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
  chipsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 12,
  },
  queuedSection: {
    marginBottom: 12,
  },
  queuedRowBase: {
    borderWidth: 1,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  queuedRow: {
    borderColor: '#b8860b',
    backgroundColor: 'rgba(184,134,11,0.08)',
  },
  conflictRow: {
    borderColor: '#d33',
    backgroundColor: 'rgba(211,51,51,0.06)',
  },
  queuedText: {
    color: '#b8860b',
    marginTop: 6,
    fontSize: 12,
  },
  conflictText: {
    color: '#d33',
    marginTop: 6,
    fontSize: 12,
  },
  chip: {
    marginRight: 4,
  },
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
  },
  spinner: {
    marginTop: 40,
  },
  empty: {
    textAlign: 'center',
    opacity: 0.6,
    marginTop: 32,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginTop: 32,
  },
});
