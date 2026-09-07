import { FlatList, StyleSheet } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import { usePaginatedApi } from '@/lib/useApi';

interface Ecole {
  idEcole: number;
  nomEcole: string;
  typeEcole: string;
}

export default function EcolesScreen() {
  const list = usePaginatedApi<Ecole>('/configuration/ecoles');

  if (list.isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;

  return (
    <FlatList
      data={list.items}
      keyExtractor={(item) => String(item.idEcole)}
      contentContainerStyle={styles.content}
      refreshing={list.isRefreshing}
      onRefresh={list.refresh}
      onEndReached={list.loadMore}
      ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune école.'}</Text>}
      renderItem={({ item }) => (
        <Text style={styles.row}>
          {item.nomEcole} — {item.typeEcole}
        </Text>
      )}
    />
  );
}

const styles = StyleSheet.create({
  spinner: { marginTop: 40 },
  content: { padding: 16, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
});
