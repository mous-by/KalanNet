import { router } from 'expo-router';
import { Pressable, StyleSheet } from 'react-native';
import { FAB, Text } from 'react-native-paper';

import PaginatedList from '@/components/PaginatedList';
import { useAuth } from '@/context/AuthContext';
import { usePaginatedApi } from '@/lib/useApi';
import { Enseignant } from '@/types/api';

export default function EnseignantsScreen() {
  const { user } = useAuth();
  const list = usePaginatedApi<Enseignant>('/enseignants');
  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire'].includes(user.droit);

  return (
    <>
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
});
