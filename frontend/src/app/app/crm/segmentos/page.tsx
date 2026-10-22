"use client";

import { useEffect, useState } from "react";
import { Tag } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { Segment } from "@/lib/types";

const columns: AppColumnDef<Segment>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Descripcion", cell: ({ row }) => row.original.description ?? "—" },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

type SegmentStats = { total: number };

export default function SegmentosPage() {
  const [stats, setStats] = useState<SegmentStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<SegmentStats>("/segments/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-1 gap-3">
      <KpiCard label="Total segmentos" value={stats?.total ?? null} icon={Tag} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Segment>
      title="Segmentos"
      description="Segmentos para agrupar y clasificar clientes."
      resource="/segments"
      exportResource="segments"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo segmento"
      statsSlot={statsSlot}
    />
  );
}
