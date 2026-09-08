import { api, apiErrorMessage, isNetworkError } from './api';
import { getQueue, removeQueueItem, updateQueueItem } from './offlineQueue';

let syncing = false;

export interface SyncResult {
  synced: number;
  conflicts: number;
  stoppedOffline: boolean;
}

// Replays queued writes in the order they were made. A validation error from
// the server (e.g. "reste à payer dépassé" because someone else already paid
// that student while this device was offline) means the world moved on
// while offline — that item is flagged as a conflict for a person to look at,
// it is never silently dropped or silently forced through. A pure network
// failure (still offline, or the server is unreachable) stops the run
// immediately so the rest of the queue is retried as-is next time.
export async function syncQueue(): Promise<SyncResult> {
  if (syncing) return { synced: 0, conflicts: 0, stoppedOffline: false };
  syncing = true;
  let synced = 0;
  let conflicts = 0;
  let stoppedOffline = false;

  try {
    const items = await getQueue();
    for (const item of items) {
      if (item.status === 'conflict') continue;

      try {
        await api[item.method](item.endpoint, item.payload);
        await removeQueueItem(item.id);
        synced += 1;
      } catch (err) {
        if (isNetworkError(err)) {
          stoppedOffline = true;
          break;
        }
        await updateQueueItem(item.id, { status: 'conflict', message: apiErrorMessage(err) });
        conflicts += 1;
      }
    }
  } finally {
    syncing = false;
  }

  return { synced, conflicts, stoppedOffline };
}
