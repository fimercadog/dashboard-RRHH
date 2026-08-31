"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { RequestRow } from "@/lib/types";

const columns: AppColumnDef<RequestRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "type", header: "Tipo" },
  dateColumn<RequestRow>("start_date", "Inicio"),
  dateColumn<RequestRow>("end_date", "Fin"),
  { accessorKey: "days", header: "Dias" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "ID empleado", type: "number", required: true },
  { name: "type", label: "Tipo", required: true },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "days", label: "Dias", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Activa", value: "active" },
      { label: "Cerrada", value: "closed" },
      { label: "Rechazada", value: "rejected" },
    ],
  },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

export default function SickLeavesPage() {
  return <ModuleTablePage title="Incapacidades" description="Registro de incapacidades con soporte documental preparado." resource="/sick-leaves" exportResource="sick-leaves" columns={columns} fields={fields} actionLabel="Registrar incapacidad" />;
}
