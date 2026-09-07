import { router } from 'expo-router';
import { FlatList, Pressable, StyleSheet } from 'react-native';
import { ActivityIndicator, FAB, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

export default function ClassesScreen() {
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<{ data: Classe[] }>('/classes');
  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire'].includes(user.droit);

  return (
    <>
      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : (
        <FlatList
          data={data?.data ?? []}
          keyExtractor={(item) => String(item.id_classe)}
          contentContainerStyle={styles.content}
          refreshing={false}
          onRefresh={reload}
          ListEmptyComponent={
            error ? <Text style={styles.error}>{error}</Text> : <Text style={styles.empty}>Aucune classe.</Text>
          }
          renderItem={({ item }) => (
            <Pressable style={styles.row} onPress={() => router.push(`/classes/${item.id_classe}`)}>
              <Text style={styles.name}>{item.nom_classe}</Text>
              <Text style={styles.meta}>
                {item.ordreEnseignement} · {item.eleves_count ?? 0} élève(s)
              </Text>
            </Pressable>
          )}
        />
      )}
      {canManage ? <FAB icon="plus" style={styles.fab} onPress={() => router.push('/classes/new')} /> : null}
    </>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
    flexGrow: 1,
  },
  spinner: {
    marginTop: 40,
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
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
  },
});
