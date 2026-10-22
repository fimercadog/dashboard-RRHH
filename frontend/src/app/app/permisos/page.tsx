"use client";

import { useEffect, useState } from "react";
import { CheckCircle, ClipboardList, Clock } from "lucide-react";
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
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  {
    name: "type",
    label: "Tipo",
    type: "select",
    required: true,
    options: [
      { label: "Pago", value: "paid" },
      { label: "No pago", value: "unpaid" },
      { label: "Personal", value: "personal" },
      { label: "Duelo", value: "bereavement" },
      { label: "Medico", value: "medical" },
      { label: "Estudio", value: "study" },
      { label: "Otro", value: "other" },
    ],
  },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "requested_days", label: "Dias", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "pending",
    options: [
      { label: "Pendiente", value: "pending" },
      { label: "Aprobado", value: "approved" },
      { label: "Rechazado", value: "rejected" },
      { label: "Cancelado", value: "cancelled" },
    ],
  },
  { name: "reason", label: "Motivo", type: "textarea", colSpan: "full" },
];

type PermissionStats = { total: number; pending: number; approved_this_month: number };

export default function PermissionsPage() {
  const [stats, setStats] = useState<PermissionStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<PermissionStats>("/permission-requests/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total permisos" value={stats?.total ?? null} icon={ClipboardList} status={statsStatus} />
      <KpiCard label="Pendientes" value={stats?.pending ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Aprobados este mes" value={stats?.approved_this_month ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage
      title="Permisos"
      description="Flujo inicial de permisos con aprobacion y trazabilidad backend."
      resource="/permission-requests"
      exportResource="permission-requests"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo permiso"
      statsSlot={statsSlot}
    />
  );
}
