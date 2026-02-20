import { openDB } from 'idb';
import axios from 'axios';
import { useToast } from '@/composables/useToast';

const DB_NAME = 'coin-offline';
const STORE = 'pending-transactions';

interface OfflineTransaction {
    uuid: string;
    category_id: number;
    amount: number;
    type: 'income' | 'expense';
    title: string;
    transacted_at: string;
}

async function getDB() {
    return openDB(DB_NAME, 1, {
        upgrade(db) {
            if (!db.objectStoreNames.contains(STORE)) {
                db.createObjectStore(STORE, { keyPath: 'uuid' });
            }
        },
    });
}

export async function queueOfflineTransaction(tx: OfflineTransaction): Promise<void> {
    const db = await getDB();
    await db.put(STORE, tx);

    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        const reg = await navigator.serviceWorker.ready;
        await (reg as any).sync.register('sync-transactions');
    }
}

export async function syncOfflineQueue(): Promise<void> {
    const db = await getDB();
    const pending = await db.getAll(STORE);

    let synced = 0;
    for (const tx of pending) {
        try {
            await axios.post('/transactions', tx, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            await db.delete(STORE, tx.uuid);
            synced++;
        } catch {
            // Leave in queue if request fails
        }
    }

    if (synced > 0) {
        const { show } = useToast();
        show(synced === 1 ? '1 transaction synced' : `${synced} transactions synced`, 'synced');
    }
}

export async function getPendingCount(): Promise<number> {
    const db = await getDB();
    return db.count(STORE);
}

// Listen for sync message from service worker
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'SYNC_TRANSACTIONS') {
            syncOfflineQueue();
        }
    });

    window.addEventListener('online', () => {
        syncOfflineQueue();
    });

    // Fallback for iOS where Background Sync is unsupported
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            syncOfflineQueue();
        }
    });
}
