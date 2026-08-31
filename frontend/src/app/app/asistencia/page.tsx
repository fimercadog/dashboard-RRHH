"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { Attendance } from "@/lib/types";

const columns: AppColumnDef<Attendance>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  dateColumn<Attendance>("date", "Fecha"),
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
  { accessorKey: "check_in", header: "Entrada" },
  { accessorKey: "check_out", header: "Salida" },
  { accessorKey: "late_minutes", header: "Min. tarde" },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "ID empleado", type: "number", required: true },
  { name: "date", label: "Fecha", type: "date", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
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
  return <ModuleTablePage title="Asistencia" description="Control diario con filtros por estado, empleado y fechas desde backend." resource="/attendances" exportResource="attendances" columns={columns} fields={fields} actionLabel="Registrar asistencia" />;
}
