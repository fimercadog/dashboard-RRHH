"use client";

import { QueuedTx } from "@/lib/contingency/types";

// Cola local, nunca se sincroniza sola. IndexedDB es el almacen durable del
// navegador; se envuelve tras getAll/put/remove para poder cambiar el motor
// sin tocar el resto de la logica de contingencia.
const DB_NAME = "dfc-contingency";
const STORE = "queue";

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, 1);
    request.onupgradeneeded = () => {
      if (!request.result.objectStoreNames.contains(STORE)) {
        request.result.createObjectStore(STORE, { keyPath: "id" });
      }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

function tx(db: IDBDatabase, mode: IDBTransactionMode) {
  return db.transaction(STORE, mode).objectStore(STORE);
}

export async function getAll(): Promise<QueuedTx[]> {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const request = tx(db, "readonly").getAll();
    request.onsuccess = () => resolve((request.result as QueuedTx[]).sort((a, b) => a.createdAt.localeCompare(b.createdAt)));
    request.onerror = () => reject(request.error);
  });
}

export async function put(item: QueuedTx): Promise<void> {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const request = tx(db, "readwrite").put(item);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}

export async function remove(id: string): Promise<void> {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const request = tx(db, "readwrite").delete(id);
    request.onsuccess = () => resolve();
    request.onerror = () => reject(request.error);
  });
}
