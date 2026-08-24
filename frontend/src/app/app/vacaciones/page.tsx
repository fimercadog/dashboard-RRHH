"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { RequestRow } from "@/lib/types";

const columns: AppColumnDef<RequestRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "start_date", header: "Inicio" },
  { accessorKey: "end_date", header: "Fin" },
  { accessorKey: "requested_days", header: "Dias" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "ID empleado", type: "number", required: true },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "requested_days", label: "Dias solicitados", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Pendiente", value: "pending" },
      { label: "Aprobada", value: "approved" },
      { label: "Rechazada", value: "rejected" },
      { label: "Cancelada", value: "cancelled" },
    ],
  },
  { name: "reason", label: "Motivo", type: "textarea", colSpan: "full" },
];

export default function VacationsPage() {
  return <ModuleTablePage title="Vacaciones" description="Solicitudes con estado pendiente, aprobada, rechazada o cancelada." resource="/vacation-requests" exportResource="vacation-requests" columns={columns} fields={fields} actionLabel="Nueva solicitud" />;
}
