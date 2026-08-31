"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { DocumentRow } from "@/lib/types";

const columns: AppColumnDef<DocumentRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "document_type", header: "Tipo" },
  { accessorKey: "name", header: "Documento" },
  dateColumn<DocumentRow>("expiration_date", "Vence"),
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "ID empleado", type: "number", required: true },
  { name: "document_type", label: "Tipo", required: true },
  { name: "name", label: "Nombre del documento", required: true },
  { name: "file_path", label: "Ruta del archivo", required: true, colSpan: "full" },
  { name: "issue_date", label: "Emision", type: "date" },
  { name: "expiration_date", label: "Vencimiento", type: "date" },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Valido", value: "valid" },
      { label: "Por vencer", value: "expiring" },
      { label: "Vencido", value: "expired" },
      { label: "Pendiente revision", value: "pending_review" },
    ],
  },
  { name: "notes", label: "Notas", type: "textarea", colSpan: "full" },
];

export default function DocumentsPage() {
  return <ModuleTablePage title="Documentos" description="Documentos de empleados con deteccion de vencimientos proximos." resource="/employee-documents" exportResource="employee-documents" columns={columns} fields={fields} actionLabel="Agregar documento" />;
}
