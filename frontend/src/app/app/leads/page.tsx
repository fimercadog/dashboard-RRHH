"use client";

import { useEffect, useState } from "react";
import { Inbox, MessageCircle, UserCheck, UserX } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";

type Lead = {
  id: number;
  name: string;
  company_name: string | null;
  email: string;
  phone: string | null;
  employee_count: string | null;
  priority_module: string | null;
  message: string | null;
  source: "contact" | "demo";
  status: "new" | "contacted" | "discarded";
  created_at: string;
};

const SOURCE_LABEL: Record<Lead["source"], string> = { contact: "Contacto", demo: "Demo" };
const STATUS_LABEL: Record<Lead["status"], string> = { new: "Nuevo", contacted: "Contactado", discarded: "Descartado" };

const columns: AppColumnDef<Lead>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Empresa", cell: ({ row }) => row.original.company_name ?? "—" },
  { accessorKey: "email", header: "Correo" },
  { header: "Telefono", cell: ({ row }) => row.original.phone ?? "—" },
  { header: "Origen", cell: ({ row }) => <Badge>{SOURCE_LABEL[row.original.source]}</Badge> },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABEL[row.original.status]}</Badge> },
  {
    header: "Recibido",
    cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString("es-CO", { day: "2-digit", month: "short", year: "numeric" }),
  },
];

const fields: CrudField[] = [
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Nuevo", value: "new" },
      { label: "Contactado", value: "contacted" },
      { label: "Descartado", value: "discarded" },
    ],
  },
];

type LeadStats = { total: number; new: number; contacted: number; discarded: number };

export default function LeadsPage() {
  const [stats, setStats] = useState<LeadStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<LeadStats>("/leads/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
      <KpiCard label="Total leads" value={stats?.total ?? null} icon={Inbox} status={statsStatus} />
      <KpiCard label="Nuevos" value={stats?.new ?? null} icon={MessageCircle} status={statsStatus} />
      <KpiCard label="Contactados" value={stats?.contacted ?? null} icon={UserCheck} status={statsStatus} />
      <KpiCard label="Descartados" value={stats?.discarded ?? null} icon={UserX} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Lead>
      title="Leads"
      description="Solicitudes de demo y mensajes de contacto del sitio publico."
      resource="/leads"
      columns={columns}
      fields={fields}
      modalDescription="Actualiza el estado del seguimiento comercial."
      statsSlot={statsSlot}
    />
  );
}
