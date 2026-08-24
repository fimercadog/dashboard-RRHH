"use client";

import { Badge } from "@/components/ui/badge";
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

export default function VacationsPage() {
  return <ModuleTablePage title="Vacaciones" description="Solicitudes con estado pendiente, aprobada, rechazada o cancelada." resource="/vacation-requests" exportResource="vacation-requests" columns={columns} actionLabel="Nueva solicitud" />;
}
