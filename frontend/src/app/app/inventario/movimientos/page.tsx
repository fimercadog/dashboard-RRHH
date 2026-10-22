"use client";

import { useEffect, useState } from "react";
import { ArrowLeftRight, CalendarDays } from "lucide-react";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { AppColumnDef } from "@/lib/table-types";
import { StockMovement } from "@/lib/types";

type MovimientoStats = { total: number; this_month: number };

const TYPE_LABEL: Record<StockMovement["type"], string> = {
  entry: "Entrada", exit: "Salida",
  transfer_out: "Traslado salida", transfer_in: "Traslado entrada",
  adjustment: "Ajuste", opening: "Apertura",
};

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<StockMovement>[] = [
  { header: "Tipo", cell: ({ row }) => <Badge variant={badgeVariant(row.original.type)}>{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Producto", cell: ({ row }) => row.original.product?.name ?? "—" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "—" },
  { header: "Cantidad", cell: ({ row }) => row.original.quantity },
  { header: "Costo unit.", cell: ({ row }) => row.original.unit_cost ? fmt.format(row.original.unit_cost) : "—" },
  { header: "Total", cell: ({ row }) => row.original.total_cost ? fmt.format(row.original.total_cost) : "—" },
  { header: "Referencia", cell: ({ row }) => row.original.reference_type ? `${row.original.reference_type} #${row.original.reference_id}` : "—" },
  { header: "Registrado por", cell: ({ row }) => row.original.user?.name ?? "—" },
  { header: "Fecha", cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString("es-CO", { day: "2-digit", month: "short", year: "numeric" }) },
];

export default function MovimientosPage() {
  const [stats, setStats] = useState<MovimientoStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<MovimientoStats>("/stock-movements/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total movimientos" value={stats?.total ?? null} icon={ArrowLeftRight} status={statsStatus} />
      <KpiCard label="Este mes" value={stats?.this_month ?? null} icon={CalendarDays} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<StockMovement>
      title="Movimientos de inventario"
      description="Historial de entradas, salidas, traslados y ajustes."
      resource="/stock-movements"
      columns={columns}
      fields={[]}
      exportResource="stock-movements"
      statsSlot={statsSlot}
    />
  );
}
