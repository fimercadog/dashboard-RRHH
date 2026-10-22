"use client";

import { useEffect, useState } from "react";
import { Activity, Calendar, HeartPulse } from "lucide-react";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { RequestRow } from "@/lib/types";
import { statusLabel } from "@/lib/labels";

const columns: AppColumnDef<RequestRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "type", header: "Tipo" },
  dateColumn<RequestRow>("start_date", "Inicio"),
  dateColumn<RequestRow>("end_date", "Fin"),
  { accessorKey: "days", header: "Dias" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  { name: "type", label: "Tipo", required: true },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "days", label: "Dias", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "active",
    options: [
      { label: "Activa", value: "active" },
      { label: "Cerrada", value: "closed" },
      { label: "Rechazada", value: "rejected" },
    ],
  },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

type SickLeaveStats = { total: number; active: number; days_this_year: number };

export default function SickLeavesPage() {
  const [stats, setStats] = useState<SickLeaveStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<SickLeaveStats>("/sick-leaves/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total incapacidades" value={stats?.total ?? null} icon={HeartPulse} status={statsStatus} />
      <KpiCard label="Activas" value={stats?.active ?? null} icon={Activity} status={statsStatus} />
      <KpiCard label="Dias este año" value={stats?.days_this_year ?? null} icon={Calendar} status={statsStatus} unit="días" />
    </div>
  );

  return (
    <ModuleTablePage
      title="Incapacidades"
      description="Registro de incapacidades con soporte documental preparado."
      resource="/sick-leaves"
      exportResource="sick-leaves"
      columns={columns}
      fields={fields}
      actionLabel="Registrar incapacidad"
      statsSlot={statsSlot}
    />
  );
}
