"use client";

import { Badge, badgeVariant } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { DocumentRow } from "@/lib/types";
import { statusLabel } from "@/lib/labels";

const columns: AppColumnDef<DocumentRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "document_type", header: "Tipo" },
  { accessorKey: "name", header: "Documento" },
  dateColumn<DocumentRow>("expiration_date", "Vence"),
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "employee_id", label: "Empleado", type: "relation-search", endpoint: "/employees/selector", required: true, editReadonly: true },
  {
    name: "document_type", label: "Tipo de documento", type: "select", required: true,
    options: [
      { label: "Contrato", value: "contrato" },
      { label: "Hoja de vida", value: "hoja_vida" },
      { label: "Diploma", value: "diploma" },
      { label: "Certificado", value: "certificado" },
      { label: "Soporte disciplinario", value: "soporte_disciplinario" },
      { label: "Otro", value: "otro" },
    ],
  },
  { name: "name", label: "Nombre del documento", required: true },
  { name: "file", label: "Archivo (PDF, imagen, Word)", type: "file", accept: ".pdf,.jpg,.jpeg,.png,.doc,.docx", required: true, createOnly: true, colSpan: "full" },
  { name: "issue_date", label: "Emision", type: "date" },
  { name: "expiration_date", label: "Vencimiento", type: "date" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "valid",
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
