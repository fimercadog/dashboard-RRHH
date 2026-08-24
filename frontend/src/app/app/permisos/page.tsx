"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { RequestRow } from "@/lib/types";

const columns: AppColumnDef<RequestRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "type", header: "Tipo" },
  { accessorKey: "start_date", header: "Inicio" },
  { accessorKey: "end_date", header: "Fin" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "ID empleado", type: "number", required: true },
  {
    name: "type",
    label: "Tipo",
    type: "select",
    required: true,
    options: [
      { label: "Pago", value: "paid" },
      { label: "No pago", value: "unpaid" },
      { label: "Personal", value: "personal" },
      { label: "Duelo", value: "bereavement" },
      { label: "Medico", value: "medical" },
      { label: "Estudio", value: "study" },
      { label: "Otro", value: "other" },
    ],
  },
  { name: "start_date", label: "Inicio", type: "date", required: true },
  { name: "end_date", label: "Fin", type: "date", required: true },
  { name: "requested_days", label: "Dias", type: "number", required: true },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Pendiente", value: "pending" },
      { label: "Aprobado", value: "approved" },
      { label: "Rechazado", value: "rejected" },
      { label: "Cancelado", value: "cancelled" },
    ],
  },
  { name: "reason", label: "Motivo", type: "textarea", colSpan: "full" },
];

export default function PermissionsPage() {
  return <ModuleTablePage title="Permisos" description="Flujo inicial de permisos con aprobacion y trazabilidad backend." resource="/permission-requests" exportResource="permission-requests" columns={columns} fields={fields} actionLabel="Nuevo permiso" />;
}
