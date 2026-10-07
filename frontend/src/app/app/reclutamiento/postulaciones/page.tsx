"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { AppColumnDef } from "@/lib/table-types";

type JobApplication = {
  id: number; status: string; applied_at: string;
  vacancy?: { id: number; title: string };
  candidate?: { id: number; first_name: string; last_name: string };
  hired_employee_id?: number;
};

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
  { name: "job_vacancy_id", label: "Vacante", type: "relation-select", endpoint: "/job-vacancies?per_page=100&status=open", required: true },
  { name: "job_candidate_id", label: "Candidato", type: "relation-select", endpoint: "/job-candidates?per_page=100", required: true },
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
  return (
    <ModuleTablePage title="Postulaciones"
      description="Seguimiento de candidatos por vacante y estado del proceso."
      resource="/job-applications" columns={columns} fields={fields}
      actionLabel="Nueva postulacion" modalDescription="Vincula un candidato a una vacante." />
  );
}
