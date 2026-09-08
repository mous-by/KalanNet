import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import NetInfo from '@react-native-community/netinfo';

import { QueueItem, enqueue, subscribeQueue } from '@/lib/offlineQueue';
import { syncQueue } from '@/lib/sync';

interface OfflineContextValue {
  isOnline: boolean;
  queue: QueueItem[];
  pendingCount: number;
  conflictCount: number;
  isSyncing: boolean;
  enqueueAction: typeof enqueue;
  syncNow: () => Promise<void>;
}

const OfflineContext = createContext<OfflineContextValue | undefined>(undefined);

export function OfflineProvider({ children }: { children: React.ReactNode }) {
  const [isOnline, setIsOnline] = useState(true);
  const [queue, setQueue] = useState<QueueItem[]>([]);
  const [isSyncing, setIsSyncing] = useState(false);
  const wasOffline = useRef(false);

  useEffect(() => subscribeQueue(setQueue), []);

  const syncNow = useCallback(async () => {
    setIsSyncing(true);
    try {
      await syncQueue();
    } finally {
      setIsSyncing(false);
    }
  }, []);

  useEffect(() => {
    const unsubscribe = NetInfo.addEventListener((state) => {
      const online = state.isConnected === true && state.isInternetReachable !== false;
      setIsOnline(online);
      if (online && wasOffline.current) {
        syncNow();
      }
      wasOffline.current = !online;
    });
    return unsubscribe;
  }, [syncNow]);

  const value: OfflineContextValue = {
    isOnline,
    queue,
    pendingCount: queue.filter((item) => item.status === 'pending').length,
    conflictCount: queue.filter((item) => item.status === 'conflict').length,
    isSyncing,
    enqueueAction: enqueue,
    syncNow,
  };

  return <OfflineContext.Provider value={value}>{children}</OfflineContext.Provider>;
}

export function useOffline(): OfflineContextValue {
  const context = useContext(OfflineContext);
  if (!context) throw new Error('useOffline must be used within an OfflineProvider');
  return context;
}
