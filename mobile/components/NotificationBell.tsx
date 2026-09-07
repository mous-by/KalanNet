import { useCallback, useEffect, useState } from 'react';
import { router, useFocusEffect } from 'expo-router';
import { View } from 'react-native';
import { Badge, IconButton } from 'react-native-paper';

import { api } from '@/lib/api';

export default function NotificationBell({ color }: { color?: string }) {
  const [count, setCount] = useState(0);

  const load = useCallback(() => {
    api
      .get<{ count: number }>('/notifications/unread-count')
      .then(({ data }) => setCount(data.count))
      .catch(() => {});
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useFocusEffect(load);

  return (
    <View style={{ marginRight: 4 }}>
      <IconButton icon="bell-outline" iconColor={color} onPress={() => router.push('/notifications')} />
      {count > 0 ? (
        <Badge style={{ position: 'absolute', top: 4, right: 4 }} size={16}>
          {count > 99 ? '99+' : count}
        </Badge>
      ) : null}
    </View>
  );
}
