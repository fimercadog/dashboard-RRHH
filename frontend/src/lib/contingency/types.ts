export type ContingencyModule = {
  key: string;
  label: string;
  description: string;
};

export type ContingencyStatus = {
  active: boolean;
  modules: ContingencyModule[];
  session: {
    id: number;
    enabled_modules: string[];
    activated_at: string;
    activated_by: { id: number; name: string } | null;
  } | null;
};

export type QueuedTxStatus = "pending" | "synced" | "failed";

export type QueuedTx = {
  /** client_uuid: idempotency key, enviado al API en el sync. */
  id: string;
  module: string;
  payload: Record<string, unknown>;
  summary: string;
  status: QueuedTxStatus;
  error?: string;
  discardReason?: string;
  createdAt: string;
};
