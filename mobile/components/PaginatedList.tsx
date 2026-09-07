import { ReactElement } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Searchbar, Text } from 'react-native-paper';

interface Props<T> {
  items: T[];
  keyExtractor: (item: T) => string;
  renderItem: (item: T) => ReactElement;
  isLoading: boolean;
  isRefreshing: boolean;
  isLoadingMore: boolean;
  error: string | null;
  onRefresh: () => void;
  onLoadMore: () => void;
  search?: string;
  onSearchChange?: (value: string) => void;
  searchPlaceholder?: string;
  emptyLabel?: string;
  header?: ReactElement;
}

export default function PaginatedList<T>({
  items,
  keyExtractor,
  renderItem,
  isLoading,
  isRefreshing,
  isLoadingMore,
  error,
  onRefresh,
  onLoadMore,
  search,
  onSearchChange,
  searchPlaceholder = 'Rechercher…',
  emptyLabel = 'Aucun résultat.',
  header,
}: Props<T>) {
  const listHeader = (
    <View>
      {onSearchChange ? (
        <Searchbar
          placeholder={searchPlaceholder}
          value={search ?? ''}
          onChangeText={onSearchChange}
          style={styles.searchbar}
        />
      ) : null}
      {header}
      {error ? <Text style={styles.error}>{error}</Text> : null}
    </View>
  );

  if (isLoading) {
    return (
      <View style={styles.center}>
        {listHeader}
        <ActivityIndicator size="large" style={styles.spinner} />
      </View>
    );
  }

  return (
    <FlatList
      data={items}
      keyExtractor={keyExtractor}
      renderItem={({ item }) => renderItem(item)}
      contentContainerStyle={styles.content}
      ListHeaderComponent={listHeader}
      ListEmptyComponent={!error ? <Text style={styles.empty}>{emptyLabel}</Text> : null}
      ListFooterComponent={isLoadingMore ? <ActivityIndicator style={styles.spinner} /> : null}
      onEndReachedThreshold={0.4}
      onEndReached={onLoadMore}
      refreshing={isRefreshing}
      onRefresh={onRefresh}
    />
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 16,
    flexGrow: 1,
  },
  center: {
    flex: 1,
  },
  searchbar: {
    marginBottom: 12,
  },
  spinner: {
    marginVertical: 24,
  },
  empty: {
    textAlign: 'center',
    opacity: 0.6,
    marginTop: 32,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginBottom: 12,
  },
});
