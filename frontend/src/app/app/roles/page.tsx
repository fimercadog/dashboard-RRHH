"use client";

import * as React from "react";
import { CrudField } from "@/components/crud/crud-modal";
import { ToggleStatusAction } from "@/components/crud/toggle-status-action";
import { ModuleTablePage } from "@/components/module-table-page";
import { KpiCard } from "@/components/kpi-card";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Role } from "@/lib/types";
import { api } from "@/lib/api";
import { Shield, ShieldCheck } from "lucide-react";

const columns: AppColumnDef<Role>[] = [
  { accessorKey: "name", header: "Rol" },
  { header: "Permisos", cell: ({ row }) => row.original.permissions_count ?? 0 },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre del rol", required: true },
  { name: "status", label: "Estado", type: "select", required: true, options: STATUS_OPTIONS, defaultValue: "active", createHidden: true },
];

type RoleStats = { total: number; active: number };

export default function AppRolesPage() {
  const [stats, setStats] = React.useState<RoleStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<RoleStats>("/roles/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total roles" value={stats?.total ?? null} icon={Shield} status={statsStatus} />
      <KpiCard label="Roles activos" value={stats?.active ?? null} icon={ShieldCheck} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage
      title="Roles y permisos"
      description="Arquitectura visual conectable a permisos backend."
      resource="/roles"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo rol"
      modalDescription="Los permisos finos por recurso se asignan luego desde el detalle del rol."
      statsSlot={statsSlot}
      extraRowActions={(row, refresh) => (
        <ToggleStatusAction resource="/roles" id={row.id} active={row.status === "active"} refresh={refresh} />
      )}
    />
  );
}
