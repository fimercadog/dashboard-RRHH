"use client";

import { useEffect, useState } from "react";
import { CalendarClock, Clock, CheckCircle } from "lucide-react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { AppColumnDef } from "@/lib/table-types";
import { api } from "@/lib/api";

type JobInterview = {
  id: number; type: string; result?: string; scheduled_at: string; notes?: string;
  application?: { id: number; candidate?: { first_name: string; last_name: string }; vacancy?: { title: string } };
};
type InterviewStats = { total: number; pending: number; completed: number };

const TYPE_LABELS: Record<string, string> = { presential: "Presencial", virtual: "Virtual", phone: "Telefono" };
const RESULT_LABELS: Record<string, string> = { pending: "Pendiente", passed: "Aprobo", failed: "No aprobo" };

const columns: AppColumnDef<JobInterview>[] = [
  { header: "Candidato", cell: ({ row }) => row.original.application?.candidate ? `${row.original.application.candidate.first_name} ${row.original.application.candidate.last_name}` : "-" },
  { header: "Vacante", cell: ({ row }) => row.original.application?.vacancy?.title ?? "-" },
  { accessorKey: "scheduled_at", header: "Fecha" },
  { header: "Tipo", cell: ({ row }) => TYPE_LABELS[row.original.type] ?? row.original.type },
  { header: "Resultado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.result ?? "pending")}>{RESULT_LABELS[row.original.result ?? "pending"] ?? row.original.result}</Badge> },
];

const fields: CrudField[] = [
  { name: "job_application_id", label: "Postulacion", type: "relation-select", endpoint: "/job-applications?per_page=100", required: true, editReadonly: true },
  { name: "scheduled_at", label: "Fecha y hora", type: "datetime-local", required: true },
  { name: "type", label: "Tipo", type: "select", required: true, defaultValue: "presential",
    options: [{ label: "Presencial", value: "presential" }, { label: "Virtual", value: "virtual" }, { label: "Telefono", value: "phone" }] },
  { name: "result", label: "Resultado", type: "select", defaultValue: "pending",
    options: [{ label: "Pendiente", value: "pending" }, { label: "Aprobo", value: "passed" }, { label: "No aprobo", value: "failed" }] },
  { name: "notes", label: "Notas", type: "textarea" },
];

export default function EntrevistasPage() {
  const [stats, setStats] = useState<InterviewStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<InterviewStats>("/job-interviews/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Total entrevistas" value={stats?.total ?? null} icon={CalendarClock} status={statsStatus} />
      <KpiCard label="Pendientes" value={stats?.pending ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Completadas" value={stats?.completed ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage title="Entrevistas"
      description="Agenda y resultados de entrevistas de seleccion."
      resource="/job-interviews" exportResource="job-interviews" columns={columns} fields={fields}
      actionLabel="Nueva entrevista" modalDescription="Programa una entrevista para una postulacion."
      statsSlot={statsSlot} />
  );
}
