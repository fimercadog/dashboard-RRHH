"use client";

import { useEffect, useState } from "react";
import { Briefcase, Users, UserCheck } from "lucide-react";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { api } from "@/lib/api";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";

type JobVacancy = {
  id: number; title: string; status: string; vacancies_count: number;
  opened_at: string; closed_at?: string;
  department?: { id: number; name: string };
};

const columns: AppColumnDef<JobVacancy>[] = [
  { accessorKey: "title", header: "Titulo" },
  { header: "Departamento", cell: ({ row }) => row.original.department?.name ?? "-" },
  { accessorKey: "vacancies_count", header: "Plazas" },
  { accessorKey: "opened_at", header: "Apertura" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "title", label: "Titulo del cargo", required: true },
  { name: "department_id", label: "Departamento", type: "relation-select", endpoint: "/departments?per_page=100" },
  { name: "description", label: "Descripcion", type: "textarea" },
  { name: "opened_at", label: "Fecha apertura", type: "date", required: true },
  { name: "closed_at", label: "Fecha cierre", type: "date" },
  { name: "vacancies_count", label: "Num Plazas", type: "number", min: 1, defaultValue: "1", required: true },
  { name: "status", label: "Estado", type: "select", required: true, defaultValue: "open",
    options: [{ label: "Abierta", value: "open" }, { label: "Cerrada", value: "closed" }] },
];

type RecruitmentStats = { open_vacancies: number; active_applications: number; hired_this_month: number };

export default function VacantesPage() {
  const [stats, setStats] = useState<RecruitmentStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<RecruitmentStats>("/recruitment/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <KpiCard label="Vacantes abiertas" value={stats?.open_vacancies ?? null} icon={Briefcase} status={statsStatus} />
      <KpiCard label="Postulaciones activas" value={stats?.active_applications ?? null} icon={Users} status={statsStatus} />
      <KpiCard label="Contratados este mes" value={stats?.hired_this_month ?? null} icon={UserCheck} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage title="Vacantes"
      description="Gestion de cargos disponibles para seleccion de personal."
      resource="/job-vacancies" exportResource="job-vacancies" columns={columns} fields={fields}
      actionLabel="Nueva vacante" modalDescription="Registra los datos de la posicion a cubrir."
      statsSlot={statsSlot} />
  );
}
