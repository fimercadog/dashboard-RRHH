"use client";

import * as React from "react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { AppColumnDef } from "@/lib/table-types";
import { api } from "@/lib/api";
import { ClipboardList, Clock } from "lucide-react";

type Audit = { id: number; action: string; module?: string; entity: string; entity_id?: number; created_at: string };
type AuditStats = { total: number; today: number };

const columns: AppColumnDef<Audit>[] = [
  { accessorKey: "action", header: "Accion" },
  { accessorKey: "module", header: "Modulo" },
  { accessorKey: "entity", header: "Entidad" },
  { accessorKey: "entity_id", header: "ID" },
  { accessorKey: "created_at", header: "Fecha" },
];

export default function AuditPage() {
  const [stats, setStats] = React.useState<AuditStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<AuditStats>("/audit-logs/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total registros" value={stats?.total ?? null} icon={ClipboardList} status={statsStatus} />
      <KpiCard label="Hoy" value={stats?.today ?? null} icon={Clock} status={statsStatus} />
    </div>
  );

  return <ModuleTablePage title="Auditoria" description="Bitacora de acciones relevantes del sistema." resource="/audit-logs" exportResource="audit-logs" columns={columns} statsSlot={statsSlot} />;
}
