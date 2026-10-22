"use client";

import { useEffect, useState } from "react";
import { CheckCircle, Clock, ListTodo } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { Activity } from "@/lib/types";

const TYPE_LABEL: Record<Activity["type"], string> = {
  call: "Llamada", email: "Correo", meeting: "Reunion", note: "Nota", task: "Tarea",
};
const STATUS_LABEL: Record<Activity["status"], string> = {
  pending: "Pendiente", done: "Completada", cancelled: "Cancelada",
};

const columns: AppColumnDef<Activity>[] = [
  { accessorKey: "title", header: "Titulo" },
  { header: "Tipo", cell: ({ row }) => <Badge variant={badgeVariant(row.original.type)}>{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABEL[row.original.status]}</Badge> },
  { header: "Oportunidad", cell: ({ row }) => row.original.deal?.title ?? "—" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.company_name ?? row.original.client?.first_name ?? "—" },
  { header: "Vence", cell: ({ row }) => row.original.due_at ? new Date(row.original.due_at).toLocaleDateString("es-CO") : "—" },
  { header: "Responsable", cell: ({ row }) => row.original.user?.name ?? "—" },
];

const fields: CrudField[] = [
  { name: "title", label: "Titulo", required: true },
  {
    name: "type", label: "Tipo", type: "select", required: true,
    options: [
      { label: "Llamada", value: "call" }, { label: "Correo", value: "email" },
      { label: "Reunion", value: "meeting" }, { label: "Nota", value: "note" },
      { label: "Tarea", value: "task" },
    ],
  },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "pending",
    options: [
      { label: "Pendiente", value: "pending" }, { label: "Completada", value: "done" },
      { label: "Cancelada", value: "cancelled" },
    ],
  },
  { name: "client_id", label: "Cliente", type: "relation-select", endpoint: "/clients?per_page=100" },
  { name: "deal_id", label: "Oportunidad", type: "relation-select", endpoint: "/deals?per_page=100" },
  { name: "due_at", label: "Fecha vencimiento", type: "date" },
  { name: "body", label: "Descripcion", type: "textarea", colSpan: "full" },
];

type ActivityStats = { total: number; pending: number; done: number };

export default function ActividadesPage() {
  const [stats, setStats] = useState<ActivityStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<ActivityStats>("/activities/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-3 gap-3">
      <KpiCard label="Total" value={stats?.total ?? null} icon={ListTodo} status={statsStatus} />
      <KpiCard label="Pendientes" value={stats?.pending ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Completadas" value={stats?.done ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Activity>
      title="Actividades"
      description="Llamadas, reuniones, tareas y notas del CRM."
      resource="/activities"
      exportResource="activities"
      columns={columns}
      fields={fields}
      actionLabel="Nueva actividad"
      statsSlot={statsSlot}
    />
  );
}
