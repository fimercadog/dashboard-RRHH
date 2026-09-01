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

const adapters: Record<string, ContingencyAdapter> = {
  [attendances.key]: attendances,
};

export function getAdapter(moduleKey: string): ContingencyAdapter | undefined {
  return adapters[moduleKey];
}
