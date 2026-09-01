"use client";

import * as React from "react";
import { api } from "@/lib/api";
import { getAdapter } from "@/lib/contingency/adapters";
import * as queueStore from "@/lib/contingency/queue";
import { ContingencyStatus, QueuedTx } from "@/lib/contingency/types";

type ContingencyValue = {
  status: ContingencyStatus | null;
  loading: boolean;
  isActive: boolean;
  enabledModules: string[];
  /** `resource` puede venir como "/attendances" o "attendances". */
  moduleEnabled: (resource: string) => boolean;
  queue: QueuedTx[];
  pendingCount: number;
  refreshStatus: () => Promise<void>;
  enqueue: (moduleKey: string, payload: Record<string, unknown>) => Promise<void>;
  syncOne: (id: string) => Promise<void>;
  discardOne: (id: string, reason: string) => Promise<void>;
  activate: (modules: string[]) => Promise<void>;
  deactivate: () => Promise<void>;
};

const ContingencyContext = React.createContext<ContingencyValue | null>(null);

const POLL_MS = 30_000;

function normalizeKey(resource: string) {
  return resource.replace(/^\//, "");
}

export function ContingencyProvider({ children }: { children: React.ReactNode }) {
  const [status, setStatus] = React.useState<ContingencyStatus | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [queue, setQueue] = React.useState<QueuedTx[]>([]);

  const reloadQueue = React.useCallback(async () => {
    try {
      setQueue(await queueStore.getAll());
    } catch {
      // IndexedDB no disponible (modo privado, etc.): la cola queda vacia.
    }
  }, []);

  const refreshStatus = React.useCallback(async () => {
    try {
      const { data } = await api.get<ContingencyStatus>("/contingency/status");
      setStatus(data);
    } catch {
      // Sin conexion o 401: no tocamos el ultimo estado conocido.
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => {
    void (async () => {
      await reloadQueue();
      await refreshStatus();
    })();
    const timer = window.setInterval(refreshStatus, POLL_MS);
    const onFocus = () => refreshStatus();
    window.addEventListener("focus", onFocus);
    return () => {
      window.clearInterval(timer);
      window.removeEventListener("focus", onFocus);
    };
  }, [reloadQueue, refreshStatus]);

  const isActive = status?.active ?? false;
  const enabledModules = React.useMemo(
    () => (isActive ? status?.session?.enabled_modules ?? [] : []),
    [isActive, status],
  );

  const moduleEnabled = React.useCallback(
    (resource: string) => enabledModules.includes(normalizeKey(resource)),
    [enabledModules],
  );

  const pendingCount = queue.filter((tx) => tx.status === "pending" || tx.status === "failed").length;

  const enqueue = React.useCallback(
    async (moduleKey: string, payload: Record<string, unknown>) => {
      const adapter = getAdapter(moduleKey);
      if (!adapter) throw new Error(`Modulo sin adaptador de contingencia: ${moduleKey}`);
      const tx: QueuedTx = {
        id: crypto.randomUUID(),
        module: moduleKey,
        payload,
        summary: adapter.summarize(payload),
        status: "pending",
        createdAt: new Date().toISOString(),
      };
      await queueStore.put(tx);
      await reloadQueue();
    },
    [reloadQueue],
  );

  const syncOne = React.useCallback(
    async (id: string) => {
      const tx = (await queueStore.getAll()).find((item) => item.id === id);
      if (!tx) return;
      const adapter = getAdapter(tx.module);
      if (!adapter) throw new Error(`Modulo sin adaptador: ${tx.module}`);
      try {
        await adapter.sync(tx.payload, tx.id);
        await queueStore.remove(id);
      } catch (error) {
        await queueStore.put({
          ...tx,
          status: "failed",
          error: error instanceof Error ? error.message : "Error al sincronizar.",
        });
        throw error;
      } finally {
        await reloadQueue();
      }
    },
    [reloadQueue],
  );

  const discardOne = React.useCallback(
    async (id: string, reason: string) => {
      const trimmed = reason.trim();
      if (!trimmed) throw new Error("El descarte requiere un motivo.");
      // El motivo se guarda como evidencia antes de borrar; no se elimina en silencio.
      const tx = (await queueStore.getAll()).find((item) => item.id === id);
      if (tx) {
        console.warn("[contingencia] transaccion descartada", { id, module: tx.module, reason: trimmed, summary: tx.summary });
      }
      await queueStore.remove(id);
      await reloadQueue();
    },
    [reloadQueue],
  );

  const activate = React.useCallback(
    async (modules: string[]) => {
      await api.post("/contingency/activate", { enabled_modules: modules });
      await refreshStatus();
    },
    [refreshStatus],
  );

  const deactivate = React.useCallback(async () => {
    // El servidor no puede saber la cola local: el bloqueo vive aqui.
    if (pendingCount > 0) {
      throw new Error("Hay transacciones sin sincronizar. Sincronizalas o descartalas antes de desactivar.");
    }
    await api.post("/contingency/deactivate");
    await refreshStatus();
  }, [pendingCount, refreshStatus]);

  const value: ContingencyValue = {
    status,
    loading,
    isActive,
    enabledModules,
    moduleEnabled,
    queue,
    pendingCount,
    refreshStatus,
    enqueue,
    syncOne,
    discardOne,
    activate,
    deactivate,
  };

  return <ContingencyContext.Provider value={value}>{children}</ContingencyContext.Provider>;
}

export function useContingency(): ContingencyValue {
  const ctx = React.useContext(ContingencyContext);
  if (!ctx) throw new Error("useContingency debe usarse dentro de ContingencyProvider");
  return ctx;
}
