"use client";

import { useEffect, useState } from "react";
import { Users } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { Contact } from "@/lib/types";

const columns: AppColumnDef<Contact>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Cliente", cell: ({ row }) => row.original.client?.company_name ?? row.original.client?.first_name ?? "—" },
  { header: "Cargo", cell: ({ row }) => row.original.job_title ?? "—" },
  { header: "Correo", cell: ({ row }) => row.original.email ?? "—" },
  { header: "Telefono", cell: ({ row }) => row.original.phone ?? "—" },
];

const fields: CrudField[] = [
  { name: "client_id", label: "Cliente", type: "relation-select", endpoint: "/clients?per_page=100", required: true },
  { name: "name", label: "Nombre", required: true },
  { name: "job_title", label: "Cargo" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

type ContactStats = { total: number };

export default function ContactosPage() {
  const [stats, setStats] = useState<ContactStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<ContactStats>("/contacts/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-1 gap-3">
      <KpiCard label="Total contactos" value={stats?.total ?? null} icon={Users} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Contact>
      title="Contactos"
      description="Personas de contacto asociadas a clientes."
      resource="/contacts"
      exportResource="contacts"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo contacto"
      statsSlot={statsSlot}
    />
  );
}
