"use client";

import { Badge } from "@/components/ui/badge";
import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";

type Shift = { id: number; name: string; start_time: string; end_time: string; break_minutes: number; status: string };

const columns: AppColumnDef<Shift>[] = [
  { accessorKey: "name", header: "Turno" },
  { accessorKey: "start_time", header: "Inicio" },
  { accessorKey: "end_time", header: "Fin" },
  { accessorKey: "break_minutes", header: "Descanso" },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

export default function ShiftsPage() {
  return <ModuleTablePage title="Turnos" description="Catalogo inicial de turnos y base para asignaciones semanales." resource="/shifts" columns={columns} actionLabel="Nuevo turno" />;
}
