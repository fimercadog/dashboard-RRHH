"use client";

import { Badge } from "@/components/ui/badge";
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

export default function PermissionsPage() {
  return <ModuleTablePage title="Permisos" description="Flujo inicial de permisos con aprobacion y trazabilidad backend." resource="/permission-requests" exportResource="permission-requests" columns={columns} actionLabel="Nuevo permiso" />;
}
