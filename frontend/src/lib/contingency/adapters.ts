import { api } from "@/lib/api";

type Payload = Record<string, unknown>;

// Un adaptador por modulo elegible. `sync` reproduce la transaccion contra el
// endpoint real del API (misma validacion y logica que un alta online), mas el
// client_uuid que la hace idempotente. Nunca una ruta de escritura paralela.
export type ContingencyAdapter = {
  key: string;
  summarize: (payload: Payload) => string;
  sync: (payload: Payload, clientUuid: string) => Promise<void>;
};

const attendances: ContingencyAdapter = {
  key: "attendances",
  summarize: (p) => {
    const emp = p.employee_id ? `Empleado #${p.employee_id}` : "Empleado";
    const date = typeof p.date === "string" ? p.date : "";
    return `${emp} — ${p.status ?? "asistencia"}${date ? ` (${date})` : ""}`;
  },
  sync: async (payload, clientUuid) => {
    await api.post("/attendances", { ...payload, client_uuid: clientUuid });
  },
};

const crm_clients: ContingencyAdapter = {
  key: "crm_clients",
  summarize: (p) => {
    const name = [p.first_name, p.last_name].filter(Boolean).join(" ") || "Cliente";
    return `${name}${p.email ? ` — ${p.email}` : ""}`;
  },
  sync: async (payload, clientUuid) => {
    await api.post("/clients", { ...payload, client_uuid: clientUuid });
  },
};

const quotes: ContingencyAdapter = {
  key: "quotes",
  summarize: (p) => {
    const num = p.number ?? "borrador";
    return `Cotización ${num}${p.total != null ? ` — $${p.total}` : ""}`;
  },
  sync: async (payload, clientUuid) => {
    await api.post("/quotes", { ...payload, client_uuid: clientUuid });
  },
};

const purchase_orders: ContingencyAdapter = {
  key: "purchase_orders",
  summarize: (p) => {
    const num = p.number ?? "borrador";
    return `OC ${num}${p.total != null ? ` — $${p.total}` : ""}`;
  },
  sync: async (payload, clientUuid) => {
    await api.post("/purchase-orders", { ...payload, client_uuid: clientUuid });
  },
};

const adapters: Record<string, ContingencyAdapter> = {
  [attendances.key]: attendances,
  [crm_clients.key]: crm_clients,
  [quotes.key]: quotes,
  [purchase_orders.key]: purchase_orders,
};

export function getAdapter(moduleKey: string): ContingencyAdapter | undefined {
  return adapters[moduleKey];
}
