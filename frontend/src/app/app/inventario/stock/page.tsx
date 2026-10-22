"use client";

import { useEffect, useState } from "react";
import { Archive, AlertTriangle } from "lucide-react";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { ProductStock } from "@/lib/types";

type StockStats = { total_items: number; out_of_stock: number };

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<ProductStock>[] = [
  { header: "Producto", cell: ({ row }) => row.original.product?.name ?? "—" },
  { header: "SKU", cell: ({ row }) => row.original.product?.sku ?? "—" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "—" },
  { header: "Cantidad", cell: ({ row }) => row.original.quantity },
  { header: "Stock min.", cell: ({ row }) => row.original.product?.min_stock ?? "—" },
  { header: "Costo prom.", cell: ({ row }) => row.original.avg_cost ? fmt.format(row.original.avg_cost) : "—" },
];

export default function StockPage() {
  const [stats, setStats] = useState<StockStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<StockStats>("/stock/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Registros de stock" value={stats?.total_items ?? null} icon={Archive} status={statsStatus} />
      <KpiCard label="Sin stock" value={stats?.out_of_stock ?? null} icon={AlertTriangle} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<ProductStock>
      title="Stock"
      description="Niveles de inventario por producto y bodega."
      resource="/stock"
      exportResource="product-stock"
      columns={columns}
      fields={[]}
      statsSlot={statsSlot}
    />
  );
}
