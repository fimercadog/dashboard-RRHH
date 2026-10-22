"use client";

import { useEffect, useState } from "react";
import { Clock, CheckCircle } from "lucide-react";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";

type Shift = { id: number; name: string; start_time: string; end_time: string; break_minutes: number; status: string };
type ShiftStats = { total: number; active: number };

const columns: AppColumnDef<Shift>[] = [
  { accessorKey: "name", header: "Turno" },
  { accessorKey: "start_time", header: "Inicio" },
  { accessorKey: "end_time", header: "Fin" },
  { accessorKey: "break_minutes", header: "Descanso" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "start_time", label: "Inicio", type: "time", required: true },
  { name: "end_time", label: "Fin", type: "time", required: true },
  { name: "break_minutes", label: "Minutos de descanso", type: "number" },
  { name: "status", label: "Estado", type: "select", required: true, options: STATUS_OPTIONS, defaultValue: "active", createHidden: true },
];

export default function ShiftsPage() {
  const [stats, setStats] = useState<ShiftStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<ShiftStats>("/shifts/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total turnos" value={stats?.total ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Turnos activos" value={stats?.active ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage
      title="Turnos"
      description="Catalogo inicial de turnos y base para asignaciones semanales."
      resource="/shifts"
      exportResource="shifts"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo turno"
      statsSlot={statsSlot}
    />
  );
}
