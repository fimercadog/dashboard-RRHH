"use client";

import { useEffect, useState } from "react";
import { Tag, TagsIcon } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Category } from "@/lib/types";

type CategoryStats = { total: number; active: number };

const columns: AppColumnDef<Category>[] = [
  { accessorKey: "name", header: "Nombre" },
  { header: "Categoria padre", cell: ({ row }) => row.original.parent?.name ?? "—" },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "parent_id", label: "Categoria padre", type: "relation-select", endpoint: "/categories?per_page=100" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active", createHidden: true,
    options: STATUS_OPTIONS,
  },
];

export default function CategoriasPage() {
  const [stats, setStats] = useState<CategoryStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<CategoryStats>("/categories/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total categorias" value={stats?.total ?? null} icon={Tag} status={statsStatus} />
      <KpiCard label="Activas" value={stats?.active ?? null} icon={TagsIcon} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Category>
      title="Categorias"
      description="Categorias y subcategorias para organizar productos."
      resource="/categories"
      exportResource="categories"
      columns={columns}
      fields={fields}
      actionLabel="Nueva categoria"
      statsSlot={statsSlot}
    />
  );
}
