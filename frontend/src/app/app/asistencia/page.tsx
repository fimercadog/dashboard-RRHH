"use client";

import { useEffect, useState } from "react";
import { UserCheck, UserX, Clock, AlertCircle } from "lucide-react";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { Attendance, AttendanceStats } from "@/lib/types";
import { statusLabel } from "@/lib/labels";

const columns: AppColumnDef<Attendance>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "—" },
  dateColumn<Attendance>("date", "Fecha"),
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
  { accessorKey: "check_in", header: "Entrada" },
  { accessorKey: "check_out", header: "Salida" },
  { accessorKey: "late_minutes", header: "Min. tarde" },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  { name: "date", label: "Fecha", type: "date", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    defaultValue: "present",
    options: [
      { label: "Presente", value: "present" },
      { label: "Ausente", value: "absent" },
      { label: "Tarde", value: "late" },
      { label: "Permiso", value: "permission" },
      { label: "Vacaciones", value: "vacation" },
      { label: "Incapacidad", value: "sick_leave" },
    ],
  },
  { name: "check_in", label: "Entrada", type: "time" },
  { name: "check_out", label: "Salida", type: "time" },
  { name: "late_minutes", label: "Minutos tarde", type: "number" },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

export default function AttendancePage() {
  const [stats, setStats] = useState<AttendanceStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    const today = new Date().toISOString().slice(0, 10);
    const from = today.slice(0, 8) + "01";
    api.get<AttendanceStats>(`/attendance-stats?from=${from}&to=${today}`)
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const s = stats?.summary;

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <KpiCard label="Ausencias injustificadas" value={s?.absences.unjustified ?? null} icon={UserX} status={statsStatus} />
      <KpiCard label="Ausencias justificadas" value={s?.absences.justified ?? null} icon={UserCheck} status={statsStatus} />
      <KpiCard label="Tardanzas" value={s?.tardiness.count ?? null} icon={Clock} status={statsStatus} />
      <KpiCard label="Min. retraso (mes)" value={s?.tardiness.total_minutes ?? null} icon={AlertCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage
      title="Asistencia"
      description="Control diario con filtros por estado, empleado y fechas desde backend."
      resource="/attendances"
      exportResource="attendances"
      columns={columns}
      fields={fields}
      actionLabel="Registrar asistencia"
      statsSlot={statsSlot}
    />
  );
}
