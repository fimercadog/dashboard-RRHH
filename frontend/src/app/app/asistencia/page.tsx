"use client";

import { Badge } from "@/components/ui/badge";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { Attendance } from "@/lib/types";

const columns: AppColumnDef<Attendance>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "date", header: "Fecha" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
  { accessorKey: "check_in", header: "Entrada" },
  { accessorKey: "check_out", header: "Salida" },
  { accessorKey: "late_minutes", header: "Min. tarde" },
];

export default function AttendancePage() {
  return <ModuleTablePage title="Asistencia" description="Control diario con filtros por estado, empleado y fechas desde backend." resource="/attendances" exportResource="attendances" columns={columns} actionLabel="Registrar asistencia" />;
}
