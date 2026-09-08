import AsyncStorage from '@react-native-async-storage/async-storage';

// Generic queue of writes made while offline. Each item is a plain
// "replay this HTTP call later" instruction, so adding offline support to a
// new module is just: enqueue() instead of api.post() when offline, using
// the same endpoint/payload the online path already sends.
export type QueueKind = 'presence' | 'emargement' | 'paiement_classe';

export type QueueStatus = 'pending' | 'conflict' | 'error';

export interface QueueItem {
  id: string;
  kind: QueueKind;
  label: string;
  endpoint: string;
  method: 'post' | 'put';
  payload: Record<string, unknown>;
  createdAt: string;
  status: QueueStatus;
  message?: string;
}

const STORAGE_KEY = 'kalannet_offline_queue';

type Listener = (items: QueueItem[]) => void;
const listeners = new Set<Listener>();
let cache: QueueItem[] | null = null;

async function readAll(): Promise<QueueItem[]> {
  if (cache) return cache;
  try {
    const raw = await AsyncStorage.getItem(STORAGE_KEY);
    cache = raw ? (JSON.parse(raw) as QueueItem[]) : [];
  } catch {
    cache = [];
  }
  return cache;
}

async function writeAll(items: QueueItem[]): Promise<void> {
  cache = items;
  await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  listeners.forEach((listener) => listener(items));
}

export function subscribeQueue(listener: Listener): () => void {
  listeners.add(listener);
  readAll().then(listener);
  return () => listeners.delete(listener);
}

export async function getQueue(): Promise<QueueItem[]> {
  return readAll();
}

export async function enqueue(item: Omit<QueueItem, 'id' | 'createdAt' | 'status'>): Promise<QueueItem> {
  const items = await readAll();
  const newItem: QueueItem = {
    ...item,
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    createdAt: new Date().toISOString(),
    status: 'pending',
  };
  await writeAll([...items, newItem]);
  return newItem;
}

export async function updateQueueItem(id: string, patch: Partial<QueueItem>): Promise<void> {
  const items = await readAll();
  await writeAll(items.map((item) => (item.id === id ? { ...item, ...patch } : item)));
}

export async function removeQueueItem(id: string): Promise<void> {
  const items = await readAll();
  await writeAll(items.filter((item) => item.id !== id));
}
