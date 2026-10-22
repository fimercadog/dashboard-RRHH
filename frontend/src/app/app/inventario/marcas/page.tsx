"use client";

import { useEffect, useState } from "react";
import { Award, CheckCircle } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Brand } from "@/lib/types";

type BrandStats = { total: number; active: number };

const columns: AppColumnDef<Brand>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active", createHidden: true,
    options: STATUS_OPTIONS,
  },
];

export default function MarcasPage() {
  const [stats, setStats] = useState<BrandStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<BrandStats>("/brands/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total marcas" value={stats?.total ?? null} icon={Award} status={statsStatus} />
      <KpiCard label="Activas" value={stats?.active ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Brand>
      title="Marcas"
      description="Marcas de productos del inventario."
      resource="/brands"
      exportResource="brands"
      columns={columns}
      fields={fields}
      actionLabel="Nueva marca"
      statsSlot={statsSlot}
    />
  );
}
