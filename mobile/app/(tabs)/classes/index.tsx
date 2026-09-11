import { router } from 'expo-router';
import { FlatList, Pressable, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, FAB, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { removeQueueItem } from '@/lib/offlineQueue';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

export default function ClassesScreen() {
  const { user } = useAuth();
  const { queue } = useOffline();
  const { data, isLoading, error, reload } = useApiGet<{ data: Classe[] }>('/classes', [], { cacheKey: 'classes' });
  const canManage = hasPermission(user, 'classes_creation');
  const queuedClasses = queue.filter((item) => item.kind === 'classe');

  return (
    <>
      <OfflineBanner />
      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : (
        <FlatList
          data={data?.data ?? []}
          keyExtractor={(item) => String(item.id_classe)}
          contentContainerStyle={styles.content}
          refreshing={false}
          onRefresh={reload}
          ListHeaderComponent={
            queuedClasses.length > 0 ? (
              <View style={styles.queuedSection}>
                {queuedClasses.map((item) => (
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
  queuedSection: { marginBottom: 4 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
