"use client";

import { useEffect, useState } from "react";
import { Warehouse, CheckCircle } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Warehouse as WarehouseType } from "@/lib/types";

type WarehouseStats = { total: number; active: number };

const columns: AppColumnDef<WarehouseType>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Ubicacion", cell: ({ row }) => row.original.location ?? "—" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "location", label: "Ubicacion" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active", createHidden: true,
    options: STATUS_OPTIONS,
  },
];

export default function BodegasPage() {
  const [stats, setStats] = useState<WarehouseStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<WarehouseStats>("/warehouses/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total bodegas" value={stats?.total ?? null} icon={Warehouse} status={statsStatus} />
      <KpiCard label="Activas" value={stats?.active ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<WarehouseType>
      title="Bodegas"
      description="Almacenes y ubicaciones fisicas del inventario."
      resource="/warehouses"
      exportResource="warehouses"
      columns={columns}
      fields={fields}
      actionLabel="Nueva bodega"
      statsSlot={statsSlot}
    />
  );
}
