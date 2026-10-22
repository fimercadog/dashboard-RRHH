"use client";

import { useEffect, useState } from "react";
import { CalendarDays, CheckCircle, Clock, Sun } from "lucide-react";
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
  dateColumn<RequestRow>("start_date", "Inicio"),
  dateColumn<RequestRow>("end_date", "Fin"),
  { accessorKey: "requested_days", header: "Dias" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "requested_days", label: "Dias solicitados", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "pending",
    options: [
      { label: "Pendiente", value: "pending" },
      { label: "Aprobada", value: "approved" },
      { label: "Rechazada", value: "rejected" },
      { label: "Cancelada", value: "cancelled" },
    ],
  },
  { name: "reason", label: "Motivo", type: "textarea", colSpan: "full" },
];

type VacationStats = { total: number; pending: number; approved: number; days_this_year: number };

export default function VacationsPage() {
  const [stats, setStats] = useState<VacationStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<VacationStats>("/vacation-requests/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <KpiCard label="Total solicitudes" value={stats?.total ?? null} icon={CalendarDays} status={statsStatus} />
      <KpiCard label="Pendientes" value={stats?.pending ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Aprobadas" value={stats?.approved ?? null} icon={CheckCircle} status={statsStatus} />
      <KpiCard label="Dias este año" value={stats?.days_this_year ?? null} icon={Sun} status={statsStatus} unit="días" />
    </div>
  );

  return (
    <ModuleTablePage
      title="Vacaciones"
      description="Solicitudes con estado pendiente, aprobada, rechazada o cancelada."
      resource="/vacation-requests"
      exportResource="vacation-requests"
      columns={columns}
      fields={fields}
      actionLabel="Nueva solicitud"
      statsSlot={statsSlot}
    />
  );
}
