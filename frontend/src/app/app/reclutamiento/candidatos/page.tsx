"use client";

import { useEffect, useState } from "react";
import { Users, UserCheck, UserPlus } from "lucide-react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { CrudField } from "@/components/crud/crud-modal";
import { AppColumnDef } from "@/lib/table-types";
import { api } from "@/lib/api";

type JobCandidate = { id: number; first_name: string; last_name: string; identification_number?: string; email?: string; phone?: string; };
type CandidateStats = { total: number; active: number; hired: number };

const columns: AppColumnDef<JobCandidate>[] = [
  { header: "Nombre", cell: ({ row }) => `${row.original.first_name} ${row.original.last_name}` },
  { accessorKey: "identification_number", header: "Documento" },
  { accessorKey: "email", header: "Correo" },
  { accessorKey: "phone", header: "Telefono" },
];

const fields: CrudField[] = [
  { name: "first_name", label: "Nombres", required: true },
  { name: "last_name", label: "Apellidos", required: true },
  { name: "identification_number", label: "Numero de documento" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "resume_url", label: "URL hoja de vida" },
  { name: "observations", label: "Observaciones", type: "textarea" },
];

export default function CandidatosPage() {
  const [stats, setStats] = useState<CandidateStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<CandidateStats>("/job-candidates/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total candidatos" value={stats?.total ?? null} icon={Users} status={statsStatus} />
      <KpiCard label="Activos" value={stats?.active ?? null} icon={UserCheck} status={statsStatus} />
      <KpiCard label="Contratados" value={stats?.hired ?? null} icon={UserPlus} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage title="Candidatos"
      description="Registro de personas en proceso de seleccion."
      resource="/job-candidates" exportResource="job-candidates" columns={columns} fields={fields}
      actionLabel="Nuevo candidato" modalDescription="Registra los datos del candidato."
      statsSlot={statsSlot} />
  );
}
