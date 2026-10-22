"use client";

import { useEffect, useState } from "react";
import { Ruler, CheckCircle } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Unit } from "@/lib/types";

type UnitStats = { total: number; active: number };

const columns: AppColumnDef<Unit>[] = [
  { accessorKey: "name", header: "Nombre" },
  { accessorKey: "abbreviation", header: "Abreviatura" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "abbreviation", label: "Abreviatura", required: true },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active", createHidden: true,
    options: STATUS_OPTIONS,
  },
];

export default function UnidadesPage() {
  const [stats, setStats] = useState<UnitStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<UnitStats>("/units/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total unidades" value={stats?.total ?? null} icon={Ruler} status={statsStatus} />
      <KpiCard label="Activas" value={stats?.active ?? null} icon={CheckCircle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Unit>
      title="Unidades de medida"
      description="Unidades para cuantificar productos del inventario."
      resource="/units"
      exportResource="units"
      columns={columns}
      fields={fields}
      actionLabel="Nueva unidad"
      statsSlot={statsSlot}
    />
  );
}
