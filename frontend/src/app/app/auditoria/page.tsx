"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";

type Audit = { id: number; action: string; module?: string; entity: string; entity_id?: number; created_at: string };

const columns: AppColumnDef<Audit>[] = [
  { accessorKey: "action", header: "Accion" },
  { accessorKey: "module", header: "Modulo" },
  { accessorKey: "entity", header: "Entidad" },
  { accessorKey: "entity_id", header: "ID" },
  { accessorKey: "created_at", header: "Fecha" },
];

export default function AuditPage() {
  return <ModuleTablePage title="Auditoria" description="Bitacora de acciones relevantes del sistema." resource="/audit-logs" exportResource="audit-logs" columns={columns} />;
}
