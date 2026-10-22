"use client";

import { useEffect, useState } from "react";
import { FileText, Clock, CheckCircle } from "lucide-react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { AppColumnDef } from "@/lib/table-types";
import { api } from "@/lib/api";

type JobApplication = {
  id: number; status: string; applied_at: string;
  vacancy?: { id: number; title: string };
  candidate?: { id: number; first_name: string; last_name: string };
  hired_employee_id?: number;
};
type ApplicationStats = { total: number; pending: number; hired_this_month: number };

const STATUS_LABELS: Record<string, string> = {
  new: "Nuevo", reviewing: "En revision", interview: "Entrevista",
  selected: "Seleccionado", rejected: "Rechazado",
};

const columns: AppColumnDef<JobApplication>[] = [
  { header: "Candidato", cell: ({ row }) => row.original.candidate ? `${row.original.candidate.first_name} ${row.original.candidate.last_name}` : "-" },
  { header: "Vacante", cell: ({ row }) => row.original.vacancy?.title ?? "-" },
  { accessorKey: "applied_at", header: "Fecha" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge> },
  { header: "Contratado", cell: ({ row }) => row.original.hired_employee_id ? "Si" : "No" },
];

const fields: CrudField[] = [
  { name: "job_vacancy_id", label: "Vacante", type: "relation-select", endpoint: "/job-vacancies?per_page=100&status=open", required: true, editReadonly: true },
  { name: "job_candidate_id", label: "Candidato", type: "relation-select", endpoint: "/job-candidates?per_page=100", required: true, editReadonly: true },
  { name: "applied_at", label: "Fecha postulacion", type: "date", required: true },
  { name: "status", label: "Estado", type: "select", required: true, defaultValue: "new",
    options: [
      { label: "Nuevo", value: "new" }, { label: "En revision", value: "reviewing" },
      { label: "Entrevista", value: "interview" }, { label: "Seleccionado", value: "selected" },
      { label: "Rechazado", value: "rejected" },
    ] },
  { name: "notes", label: "Notas", type: "textarea" },
];

export default function PostulacionesPage() {
  const [stats, setStats] = useState<ApplicationStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<ApplicationStats>("/job-applications/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total postulaciones" value={stats?.total ?? null} icon={FileText} status={statsStatus} />
      <KpiCard label="En revision" value={stats?.pending ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Contratados este mes" value={stats?.hired_this_month ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage title="Postulaciones"
      description="Seguimiento de candidatos por vacante y estado del proceso."
      resource="/job-applications" exportResource="job-applications" columns={columns} fields={fields}
      actionLabel="Nueva postulacion" modalDescription="Vincula un candidato a una vacante."
      statsSlot={statsSlot} />
  );
}
