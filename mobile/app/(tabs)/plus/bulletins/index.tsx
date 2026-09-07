import { router } from 'expo-router';
import { FlatList, Pressable, StyleSheet } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

export default function BulletinsClassesScreen() {
  const { data, isLoading, error } = useApiGet<{ data: Classe[] }>('/bulletins/classes');

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error) return <Text style={styles.error}>{error}</Text>;

  return (
    <FlatList
      data={data?.data ?? []}
      keyExtractor={(item) => String(item.id_classe)}
      contentContainerStyle={styles.content}
      ListEmptyComponent={<Text style={styles.empty}>Aucune classe.</Text>}
      renderItem={({ item }) => (
        <Pressable style={styles.row} onPress={() => router.push(`/plus/bulletins/${item.id_classe}`)}>
          <Text style={styles.title}>{item.nom_classe}</Text>
          <Text style={styles.meta}>{item.eleves_count ?? 0} élève(s)</Text>
        </Pressable>
      )}
    />
  );
}

const styles = StyleSheet.create({
  content: { padding: 16, flexGrow: 1 },
  spinner: { marginTop: 40 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  title: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
  error: { color: '#d33', textAlign: 'center', marginTop: 32 },
});
