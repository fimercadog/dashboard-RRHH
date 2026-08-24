"use client";

import { Badge } from "@/components/ui/badge";
import { CrudField } from "@/components/crud/crud-modal";
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

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "start_time", label: "Inicio", type: "time", required: true },
  { name: "end_time", label: "Fin", type: "time", required: true },
  { name: "break_minutes", label: "Minutos de descanso", type: "number" },
  {
    name: "status",
    label: "Estado",
    type: "select",
    required: true,
    options: [
      { label: "Activo", value: "active" },
      { label: "Inactivo", value: "inactive" },
    ],
  },
];

export default function ShiftsPage() {
  return <ModuleTablePage title="Turnos" description="Catalogo inicial de turnos y base para asignaciones semanales." resource="/shifts" columns={columns} fields={fields} actionLabel="Nuevo turno" />;
}
