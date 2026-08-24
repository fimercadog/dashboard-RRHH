"use client";

import { Badge } from "@/components/ui/badge";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { DocumentRow } from "@/lib/types";

const columns: AppColumnDef<DocumentRow>[] = [
  { header: "Empleado", cell: ({ row }) => row.original.employee?.full_name ?? row.original.employee?.first_name ?? "Empleado" },
  { accessorKey: "document_type", header: "Tipo" },
  { accessorKey: "name", header: "Documento" },
  { accessorKey: "expiration_date", header: "Vence" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

export default function DocumentsPage() {
  return <ModuleTablePage title="Documentos" description="Documentos de empleados con deteccion de vencimientos proximos." resource="/employee-documents" exportResource="employee-documents" columns={columns} actionLabel="Agregar documento" />;
}
