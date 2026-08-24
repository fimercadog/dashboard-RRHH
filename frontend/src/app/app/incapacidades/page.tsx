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
  { accessorKey: "days", header: "Dias" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

export default function SickLeavesPage() {
  return <ModuleTablePage title="Incapacidades" description="Registro de incapacidades con soporte documental preparado." resource="/sick-leaves" exportResource="sick-leaves" columns={columns} actionLabel="Registrar incapacidad" />;
}
