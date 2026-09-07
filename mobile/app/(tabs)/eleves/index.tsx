import { useMemo, useState } from 'react';
import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Chip, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
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
  const { data: filterOptions } = useApiGet<{ classes: Classe[] }>('/eleves/cartes-scolaires');
  const [selectedClasse, setSelectedClasse] = useState<number | null>(null);

  const params = useMemo(
    () => (selectedClasse ? { id_classe: selectedClasse } : {}),
    [selectedClasse]
  );

  const list = usePaginatedApi<Eleve>('/eleves', params);

  return (
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
        (filterOptions?.classes?.length ?? 0) > 0 ? (
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
        ) : undefined
      }
    />
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
  chip: {
    marginRight: 4,
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
