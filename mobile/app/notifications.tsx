import { router } from 'expo-router';
import { FlatList, Pressable, StyleSheet } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import { api } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { ApiNotification } from '@/types/api';

export default function NotificationsScreen() {
  const { data, isLoading, error, reload } = useApiGet<{ data: ApiNotification[] }>('/notifications');

  async function handlePress(notification: ApiNotification) {
    if (!notification.read_at) {
      try {
        await api.post(`/notifications/${notification.id}/read`);
        reload();
      } catch {
        // non-blocking: navigation should still happen even if marking as read fails
      }
    }
    if (notification.link) {
      router.push(notification.link as never);
    }
  }

  if (isLoading) {
    return <ActivityIndicator style={styles.spinner} size="large" />;
  }

  return (
    <FlatList
      data={data?.data ?? []}
      keyExtractor={(item) => String(item.id)}
      contentContainerStyle={styles.content}
      ListEmptyComponent={
        !error ? <Text style={styles.empty}>Aucune notification.</Text> : <Text style={styles.error}>{error}</Text>
      }
      renderItem={({ item }) => (
        <Pressable style={[styles.row, !item.read_at && styles.unread]} onPress={() => handlePress(item)}>
          <Text style={styles.title}>{item.title}</Text>
          <Text style={styles.message}>{item.message}</Text>
        </Pressable>
      )}
    />
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
  unread: {
    borderColor: '#1f8a4c',
    backgroundColor: 'rgba(31,138,76,0.06)',
  },
  title: {
    fontWeight: '600',
    marginBottom: 4,
  },
  message: {
    opacity: 0.75,
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
