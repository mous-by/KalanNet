import { useState } from 'react';
import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { FAB, IconButton, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { hasPermission } from '@/lib/permissions';
import { usePaginatedApi } from '@/lib/useApi';
import { ParentEleve } from '@/types/api';

export default function ParentsScreen() {
  const { user } = useAuth();
  const list = usePaginatedApi<ParentEleve>('/parents', {}, 'parents');
  const canCreate = hasPermission(user, 'parents_creation');
  const canDelete = hasPermission(user, 'parents_supprimer');
  const [actionError, setActionError] = useState<string | null>(null);

  async function handleDelete(id: number) {
    setActionError(null);
    try {
      await api.delete(`/parents/${id}`);
      list.refresh();
    } catch (err) {
      setActionError(apiErrorMessage(err, 'Impossible de supprimer ce parent.'));
    }
  }

  return (
    <>
      <PaginatedList
        items={list.items}
        keyExtractor={(item) => String(item.id_parent)}
        isLoading={list.isLoading}
        isRefreshing={list.isRefreshing}
        isLoadingMore={list.isLoadingMore}
        error={list.error ?? actionError}
        onRefresh={list.refresh}
        onLoadMore={list.loadMore}
        search={list.search}
        onSearchChange={list.setSearch}
        searchPlaceholder="Nom, téléphone, email, élève…"
        emptyLabel="Aucun parent."
        renderItem={(item) => (
          <Pressable style={styles.row} onPress={() => router.push(`/plus/parents/${item.id_parent}/edit`)}>
            <View style={styles.rowInfo}>
              <Text style={styles.name}>{item.nom_prenom_parent}</Text>
              <Text style={styles.meta}>
                {item.telephone_parent ?? '—'} · {item.eleves_count ?? 0} élève(s)
              </Text>
            </View>
            {canDelete ? <IconButton icon="delete-outline" onPress={() => handleDelete(item.id_parent)} /> : null}
          </Pressable>
        )}
      />
      {canCreate ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/plus/parents/new')} /> : null}
    </>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  rowInfo: { flex: 1 },
  name: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
